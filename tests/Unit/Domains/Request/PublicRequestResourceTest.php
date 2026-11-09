<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Request;

use App\Domains\Request\Resources\PublicRequestResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Test case for PublicRequestResource.
 *
 * This test focuses on testing the status transformation logic
 * without requiring database connections or Laravel features.
 */
class PublicRequestResourceTest extends TestCase
{
    /**
     * Test that validated status is transformed to "Matching Ongoing".
     */
    public function test_validated_status_transforms_to_matching_ongoing(): void
    {
        $resource = $this->createResourceWithStatus('validated', 'Validated');
        $data = $resource->toArray(new Request);

        $this->assertEquals('Matching Ongoing', $data['status']->status_label);
        $this->assertEquals('validated', $data['status']->status_code);
    }

    /**
     * Test that offer_made status is transformed to "Matching Closed".
     */
    public function test_offer_made_status_transforms_to_matching_closed(): void
    {
        $resource = $this->createResourceWithStatus('offer_made', 'Offer Made');
        $data = $resource->toArray(new Request);

        $this->assertEquals('Matching Closed', $data['status']->status_label);
        $this->assertEquals('offer_made', $data['status']->status_code);
    }

    /**
     * Test that in_implementation status is transformed to "Matching Closed".
     */
    public function test_in_implementation_status_transforms_to_matching_closed(): void
    {
        $resource = $this->createResourceWithStatus('in_implementation', 'In Implementation');
        $data = $resource->toArray(new Request);

        $this->assertEquals('Matching Closed', $data['status']->status_label);
        $this->assertEquals('in_implementation', $data['status']->status_code);
    }

    /**
     * Test that closed status is transformed to "Matching Closed".
     */
    public function test_closed_status_transforms_to_matching_closed(): void
    {
        $resource = $this->createResourceWithStatus('closed', 'Closed');
        $data = $resource->toArray(new Request);

        $this->assertEquals('Matching Closed', $data['status']->status_label);
        $this->assertEquals('closed', $data['status']->status_code);
    }

    /**
     * Test that unmapped statuses retain their original labels.
     */
    public function test_unmapped_status_retains_original_label(): void
    {
        $resource = $this->createResourceWithStatus('draft', 'Draft');
        $data = $resource->toArray(new Request);

        // Should keep the original label since it's not mapped
        $this->assertEquals('Draft', $data['status']->status_label);
        $this->assertEquals('draft', $data['status']->status_code);
    }

    /**
     * Test that status with missing properties is handled gracefully.
     */
    public function test_status_with_missing_properties_is_handled(): void
    {
        // Create a mock object with status relation
        $mockRequest = $this->createMock(\stdClass::class);
        $mockRequest->id = 1;
        $mockRequest->title = 'Test Request';

        // Create a status object with missing properties
        $status = new \stdClass;
        $mockRequest->status = $status;

        $resource = new PublicRequestResource($mockRequest);
        $data = $resource->toArray(new Request);

        // Should handle missing properties gracefully
        $this->assertIsObject($data['status']);
        $this->assertEquals('', $data['status']->status_label);
    }

    /**
     * Helper method to create a resource with a specific status.
     */
    private function createResourceWithStatus(string $statusCode, string $statusLabel): PublicRequestResource
    {
        // Create a simple mock object
        $mockRequest = new \stdClass;
        $mockRequest->id = 1;
        $mockRequest->title = 'Test Request';

        // Create a status object
        $status = new \stdClass;
        $status->status_code = $statusCode;
        $status->status_label = $statusLabel;

        $mockRequest->status = $status;

        return new PublicRequestResource($mockRequest);
    }
}
