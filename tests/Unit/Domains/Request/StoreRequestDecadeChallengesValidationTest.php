<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Request;

use App\Domains\Request\Enums\DecadeChallenge;
use App\Domains\Request\Requests\StoreRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Database-free tests for the ranked Decade Challenges validation rules
 * on StoreRequest (issue #205). Exercises rules() + withValidator()
 * directly so the ranked-rank invariants are verifiable without MySQL.
 */
class StoreRequestDecadeChallengesValidationTest extends TestCase
{
    /**
     * Build a validator for the given decade_challenges input in submit mode.
     *
     * @param  array<string, mixed>|null  $challenges
     */
    private function validateChallenges(?array $challenges): ValidationValidator
    {
        $payload = [
            'mode' => 'submit',
            'decade_challenges' => $challenges,
        ];

        $storeRequest = StoreRequest::create('/request/submit', 'POST', $payload);

        $validator = Validator::make($payload, $storeRequest->rules());
        $storeRequest->withValidator($validator);
        $validator->passes();

        return $validator;
    }

    #[Test]
    public function a_missing_primary_challenge_fails_and_surfaces_on_the_base_key(): void
    {
        $validator = $this->validateChallenges([
            'primary' => null,
            'secondary' => DecadeChallenge::ECOSYSTEMS_BIODIVERSITY->value,
            'tertiary' => null,
        ]);

        $this->assertTrue($validator->errors()->has('decade_challenges.primary'));
        $this->assertTrue($validator->errors()->has('decade_challenges'));
    }

    #[Test]
    public function a_primary_only_selection_passes_the_challenge_rules(): void
    {
        $validator = $this->validateChallenges([
            'primary' => DecadeChallenge::MARINE_POLLUTION->value,
            'secondary' => null,
            'tertiary' => null,
        ]);

        $this->assertFalse($validator->errors()->hasAny([
            'decade_challenges',
            'decade_challenges.primary',
            'decade_challenges.secondary',
            'decade_challenges.tertiary',
        ]));
    }

    #[Test]
    public function a_full_distinct_ranking_passes_the_challenge_rules(): void
    {
        $validator = $this->validateChallenges([
            'primary' => DecadeChallenge::MARINE_POLLUTION->value,
            'secondary' => DecadeChallenge::ECOSYSTEMS_BIODIVERSITY->value,
            'tertiary' => DecadeChallenge::CLIMATE_CHANGE_SOLUTIONS->value,
        ]);

        $this->assertFalse($validator->errors()->hasAny([
            'decade_challenges',
            'decade_challenges.primary',
            'decade_challenges.secondary',
            'decade_challenges.tertiary',
        ]));
    }

    #[Test]
    public function duplicate_ranks_fail_with_a_base_key_error(): void
    {
        $validator = $this->validateChallenges([
            'primary' => DecadeChallenge::MARINE_POLLUTION->value,
            'secondary' => DecadeChallenge::MARINE_POLLUTION->value,
            'tertiary' => null,
        ]);

        $this->assertTrue($validator->errors()->has('decade_challenges'));
    }

    #[Test]
    public function a_tertiary_without_a_secondary_fails_contiguity(): void
    {
        $validator = $this->validateChallenges([
            'primary' => DecadeChallenge::MARINE_POLLUTION->value,
            'secondary' => null,
            'tertiary' => DecadeChallenge::CLIMATE_CHANGE_SOLUTIONS->value,
        ]);

        $this->assertTrue($validator->errors()->has('decade_challenges.tertiary'));
        $this->assertTrue($validator->errors()->has('decade_challenges'));
    }

    #[Test]
    public function an_unknown_challenge_value_fails_the_enum_rule(): void
    {
        $validator = $this->validateChallenges([
            'primary' => 'not-a-challenge',
            'secondary' => null,
            'tertiary' => null,
        ]);

        $this->assertTrue($validator->errors()->has('decade_challenges.primary'));
        $this->assertTrue($validator->errors()->has('decade_challenges'));
    }

    #[Test]
    public function draft_mode_skips_the_ranked_challenge_rules(): void
    {
        $payload = [
            'mode' => 'draft',
            'decade_challenges' => [
                'primary' => null,
                'secondary' => DecadeChallenge::NOURISH_POPULATION->value,
                'tertiary' => null,
            ],
        ];

        $storeRequest = StoreRequest::create('/request/submit', 'POST', $payload);

        $validator = Validator::make($payload, $storeRequest->rules());
        $storeRequest->withValidator($validator);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function the_enum_exposes_the_ten_official_challenges(): void
    {
        $options = DecadeChallenge::getOptions();

        $this->assertCount(10, $options);
        $this->assertSame('challenge-1', $options[0]['value']);
        $this->assertSame('Understand and beat marine pollution', $options[0]['label']);
        $this->assertSame('challenge-10', $options[9]['value']);
        $this->assertSame("Restore society's relationship with the ocean", $options[9]['label']);
    }
}
