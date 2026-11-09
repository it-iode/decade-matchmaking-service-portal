<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Opportunity;

use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Services\OpportunityQueryBuilder;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityKeywordSearchTest extends TestCase
{
    use RefreshDatabase;

    private OpportunityQueryBuilder $queryBuilder;

    private Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilder = app(OpportunityQueryBuilder::class);

        $this->opportunity = Opportunity::factory()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Marine capacity workshop',
            'summary' => 'A summary without the term.',
            'key_words' => ['Ocean', 'Research'],
        ]);
    }

    public function test_title_filter_matches_key_words_case_insensitively(): void
    {
        $results = $this->queryBuilder
            ->applySearchFilters($this->queryBuilder->buildBaseQuery(), ['title' => 'ocean'])
            ->get();

        $this->assertTrue($results->contains('id', $this->opportunity->id));
    }

    public function test_general_search_matches_key_words_case_insensitively(): void
    {
        $results = $this->queryBuilder
            ->applySearchFilters($this->queryBuilder->buildBaseQuery(), ['search' => 'OCEAN'])
            ->get();

        $this->assertTrue($results->contains('id', $this->opportunity->id));
    }

    public function test_non_matching_term_returns_nothing(): void
    {
        $results = $this->queryBuilder
            ->applySearchFilters($this->queryBuilder->buildBaseQuery(), ['search' => 'volcano'])
            ->get();

        $this->assertCount(0, $results);
    }
}
