<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Request;

use App\Domains\Request\Enums\PublicRequestStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test case for PublicRequestStatus enum.
 */
class PublicRequestStatusTest extends TestCase
{
    /**
     * Test that the enum has the expected cases.
     */
    public function test_enum_cases(): void
    {
        $cases = PublicRequestStatus::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(PublicRequestStatus::MATCHING_ONGOING, $cases);
        $this->assertContains(PublicRequestStatus::MATCHING_CLOSED, $cases);
    }

    /**
     * Test that labels are correctly returned.
     */
    public function test_labels(): void
    {
        $this->assertEquals('Matching Ongoing', PublicRequestStatus::MATCHING_ONGOING->label());
        $this->assertEquals('Matching Closed', PublicRequestStatus::MATCHING_CLOSED->label());
    }

    /**
     * Test conversion from technical status codes to public status.
     */
    #[DataProvider('technicalStatusProvider')]
    public function test_from_technical_status(string $technicalStatus, ?PublicRequestStatus $expected): void
    {
        $result = PublicRequestStatus::fromTechnicalStatus($technicalStatus);

        if ($expected === null) {
            $this->assertNull($result);
        } else {
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Data provider for technical status conversion tests.
     *
     * @return array<int, array{0: string, 1: PublicRequestStatus|null}>
     */
    public static function technicalStatusProvider(): array
    {
        return [
            // Matching Ongoing statuses
            ['validated', PublicRequestStatus::MATCHING_ONGOING],

            // Matching Closed statuses
            ['offer_made', PublicRequestStatus::MATCHING_CLOSED],
            ['in_implementation', PublicRequestStatus::MATCHING_CLOSED],
            ['closed', PublicRequestStatus::MATCHING_CLOSED],

            // Unmapped statuses (not publicly visible)
            ['draft', null],
            ['under_review', null],
            ['rejected', null],
            ['unknown_status', null],
        ];
    }

    /**
     * Test public visibility check for technical status codes.
     */
    #[DataProvider('visibilityProvider')]
    public function test_is_publicly_visible(string $technicalStatus, bool $expectedVisibility): void
    {
        $result = PublicRequestStatus::isPubliclyVisible($technicalStatus);
        $this->assertEquals($expectedVisibility, $result);
    }

    /**
     * Data provider for visibility tests.
     *
     * @return array<int, array{0: string, 1: bool}>
     */
    public static function visibilityProvider(): array
    {
        return [
            // Publicly visible statuses
            ['validated', true],
            ['offer_made', true],
            ['in_implementation', true],
            ['closed', true],

            // Non-public statuses
            ['draft', false],
            ['under_review', false],
            ['rejected', false],
            ['unknown_status', false],
        ];
    }

    /**
     * Test the getOptions method returns correct structure.
     */
    public function test_get_options(): void
    {
        $options = PublicRequestStatus::getOptions();

        $this->assertCount(2, $options);

        // Check structure of first option
        $this->assertArrayHasKey('value', $options[0]);
        $this->assertArrayHasKey('label', $options[0]);

        // Check values
        $values = array_column($options, 'value');
        $labels = array_column($options, 'label');

        $this->assertContains('matching_ongoing', $values);
        $this->assertContains('matching_closed', $values);
        $this->assertContains('Matching Ongoing', $labels);
        $this->assertContains('Matching Closed', $labels);
    }
}
