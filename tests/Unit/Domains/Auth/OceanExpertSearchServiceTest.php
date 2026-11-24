<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth;

use App\Domains\Auth\Services\OceanExpertSearchService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OceanExpertSearchServiceTest extends TestCase
{
    private OceanExpertSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.oceanexpert.search_url', 'https://oceanexpert.test/search');
        $this->service = new OceanExpertSearchService;
    }

    public function test_search_expert_by_email_returns_profile_when_found(): void
    {
        Http::fake([
            'oceanexpert.test/*' => Http::response([
                'results' => ['data' => [[
                    'name' => 'Jane Doe',
                    'fname' => 'Jane',
                    'sname' => 'Doe',
                    'country' => 'France',
                    'city' => 'Paris',
                ]]],
            ], 200),
        ]);

        $profile = $this->service->searchExpertByEmail('jane@example.com');

        $this->assertEquals('Jane Doe', $profile['name']);
        $this->assertEquals('Jane', $profile['first_name']);
        $this->assertEquals('Doe', $profile['last_name']);
        $this->assertEquals('France', $profile['country']);
        $this->assertEquals('Paris', $profile['city']);
    }

    public function test_search_expert_by_email_throws_when_no_results(): void
    {
        Http::fake([
            'oceanexpert.test/*' => Http::response(['results' => ['data' => []]], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OceanExpert Profile Not found.');

        $this->service->searchExpertByEmail('nobody@example.com');
    }

    public function test_search_user_by_email_falls_back_to_any_search_when_expert_not_found(): void
    {
        $call = 0;
        Http::fake(function () use (&$call) {
            $call++;

            // First call (expert search) returns nothing, second (fallback) returns a match.
            if ($call === 1) {
                return Http::response(['results' => ['data' => []]], 200);
            }

            return Http::response(['results' => ['data' => [['name' => 'Someone']]]], 200);
        });

        $profile = $this->service->searchUserByEmail('fallback@example.com');

        $this->assertEquals('fallback@example.com', $profile['name']);
        $this->assertEquals('fallback@example.com', $profile['first_name']);
        $this->assertNull($profile['last_name']);
    }

    public function test_search_any_by_email_throws_when_not_found(): void
    {
        Http::fake([
            'oceanexpert.test/*' => Http::response(['results' => ['data' => []]], 200),
        ]);

        $this->expectException(RuntimeException::class);

        $this->service->searchAnyByEmail('nobody@example.com');
    }
}
