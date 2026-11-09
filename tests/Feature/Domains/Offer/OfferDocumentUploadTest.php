<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Offer;

use App\Domains\Document\Enums\DocumentType;
use App\Domains\Document\Models\Document;
use App\Domains\Offer\Models\Offer;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfferDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_authenticated_user_can_upload_document_to_offer(): void
    {
        $user = User::factory()->create();
        $offer = Offer::factory()->create();

        $file = UploadedFile::fake()->create('financial.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('offer.documents.upload', [
                'id' => $offer->id,
                'type' => 'financial_breakdown',
            ]), [
                'document' => $file,
            ]);

        // Check response
        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'document' => ['id', 'name', 'type', 'uploaded_at'],
                'offer' => ['id', 'has_financial_breakdown'],
            ],
        ]);

        // Check file was stored
        Storage::disk('public')->assertExists('documents/'.$file->hashName());

        // Check database record
        $this->assertDatabaseHas('documents', [
            'parent_id' => $offer->id,
            'parent_type' => (new Offer)->getMorphClass(),
            'document_type' => DocumentType::FINANCIAL_BREAKDOWN_REPORT->value,
            'uploader_id' => $user->id,
        ]);
    }

    public function test_upload_requires_authentication(): void
    {
        $offer = Offer::factory()->create();
        $file = UploadedFile::fake()->create('financial.pdf', 1000, 'application/pdf');

        $response = $this->post(route('offer.documents.upload', [
            'id' => $offer->id,
            'type' => 'financial_breakdown',
        ]), [
            'document' => $file,
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_upload_validates_file_type(): void
    {
        $user = User::factory()->create();
        $offer = Offer::factory()->create();

        // Try to upload an exe file (not allowed)
        $file = UploadedFile::fake()->create('virus.exe', 1000);

        $response = $this->actingAs($user)
            ->post(route('offer.documents.upload', [
                'id' => $offer->id,
                'type' => 'financial_breakdown',
            ]), [
                'document' => $file,
            ]);

        $response->assertSessionHasErrors('document');
    }

    public function test_upload_validates_file_size(): void
    {
        $user = User::factory()->create();
        $offer = Offer::factory()->create();

        // Try to upload a file larger than 10MB
        $file = UploadedFile::fake()->create('large.pdf', 11 * 1024, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('offer.documents.upload', [
                'id' => $offer->id,
                'type' => 'financial_breakdown',
            ]), [
                'document' => $file,
            ]);

        $response->assertSessionHasErrors('document');
    }

    public function test_uploading_new_financial_breakdown_replaces_existing(): void
    {
        $user = User::factory()->create();
        $offer = Offer::factory()->create();

        // Upload first document
        $file1 = UploadedFile::fake()->create('financial1.pdf', 1000, 'application/pdf');
        $this->actingAs($user)
            ->post(route('offer.documents.upload', [
                'id' => $offer->id,
                'type' => 'financial_breakdown',
            ]), [
                'document' => $file1,
            ]);

        // Upload second document
        $file2 = UploadedFile::fake()->create('financial2.pdf', 1000, 'application/pdf');
        $this->actingAs($user)
            ->post(route('offer.documents.upload', [
                'id' => $offer->id,
                'type' => 'financial_breakdown',
            ]), [
                'document' => $file2,
            ]);

        // Should only have one financial breakdown document
        $documentsCount = Document::where('parent_id', $offer->id)
            ->where('parent_type', (new Offer)->getMorphClass())
            ->where('document_type', DocumentType::FINANCIAL_BREAKDOWN_REPORT->value)
            ->count();

        $this->assertEquals(1, $documentsCount);
    }

    public function test_user_can_download_document(): void
    {
        $user = User::factory()->create();
        $offer = Offer::factory()->create();

        $file = UploadedFile::fake()->create('financial.pdf', 1000, 'application/pdf');
        $file->store('documents', 'public');

        $document = Document::create([
            'name' => $file->getClientOriginalName(),
            'path' => 'documents/'.$file->hashName(),
            'file_type' => $file->getMimeType(),
            'document_type' => DocumentType::FINANCIAL_BREAKDOWN_REPORT->value,
            'parent_id' => $offer->id,
            'parent_type' => (new Offer)->getMorphClass(),
            'uploader_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('offer.documents.download', [
                'id' => $offer->id,
                'document' => $document->id,
            ]));

        $response->assertSuccessful();
        $response->assertDownload($file->getClientOriginalName());
    }

    public function test_user_can_delete_document(): void
    {
        $user = User::factory()->create();
        $offer = Offer::factory()->create();

        $file = UploadedFile::fake()->create('financial.pdf', 1000, 'application/pdf');
        $file->store('documents', 'public');

        $document = Document::create([
            'name' => $file->getClientOriginalName(),
            'path' => 'documents/'.$file->hashName(),
            'file_type' => $file->getMimeType(),
            'document_type' => DocumentType::FINANCIAL_BREAKDOWN_REPORT->value,
            'parent_id' => $offer->id,
            'parent_type' => (new Offer)->getMorphClass(),
            'uploader_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('offer.documents.destroy', [
                'id' => $offer->id,
                'document' => $document->id,
            ]));

        $response->assertSuccessful();

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);
    }
}
