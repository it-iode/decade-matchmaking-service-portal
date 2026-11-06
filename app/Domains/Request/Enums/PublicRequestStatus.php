<?php

declare(strict_types=1);

namespace App\Domains\Request\Enums;

/**
 * Public Request Status Enum
 *
 * Represents the simplified public-facing status labels for capacity development requests.
 * Maps technical internal status codes to user-friendly labels for public display.
 */
enum PublicRequestStatus: string
{
    /**
     * Indicates that matching is currently in progress for the request.
     * The request is actively being reviewed for potential partners.
     */
    case MATCHING_ONGOING = 'matching_ongoing';

    /**
     * Indicates that the matching process has been completed.
     * Either a partner has been found or the request has been closed.
     */
    case MATCHING_CLOSED = 'matching_closed';

    /**
     * Get the human-readable label for the status.
     *
     * @return string The user-friendly label
     */
    public function label(): string
    {
        return match ($this) {
            self::MATCHING_ONGOING => 'Matching Ongoing',
            self::MATCHING_CLOSED => 'Matching Closed',
        };
    }

    /**
     * Convert a technical status code to the appropriate public status.
     *
     * Maps internal technical status codes to simplified public-facing statuses:
     * - 'validated' → MATCHING_ONGOING
     * - 'offer_made', 'in_implementation', 'closed' → MATCHING_CLOSED
     * - Others → null (not publicly visible)
     *
     * @param  string  $technicalStatusCode  The internal technical status code
     * @return self|null The corresponding public status or null if not mapped
     */
    public static function fromTechnicalStatus(string $technicalStatusCode): ?self
    {
        return match ($technicalStatusCode) {
            'validated' => self::MATCHING_ONGOING,
            'offer_made', 'in_implementation', 'closed' => self::MATCHING_CLOSED,
            default => null,
        };
    }

    /**
     * Check if a technical status code should be displayed publicly.
     *
     * @param  string  $technicalStatusCode  The internal technical status code
     * @return bool True if the status should be shown publicly, false otherwise
     */
    public static function isPubliclyVisible(string $technicalStatusCode): bool
    {
        return self::fromTechnicalStatus($technicalStatusCode) !== null;
    }

    /**
     * Get the technical status codes that map to this public status.
     *
     * @return string[]
     */
    public static function getTechnicalStatusCodes(string $publicStatusCode): array
    {
        return match ($publicStatusCode) {
            self::MATCHING_ONGOING->value => ['validated'],
            self::MATCHING_CLOSED->value => ['offer_made', 'in_implementation', 'closed'],
        };
    }

    /**
     * Get all public status options for frontend use.
     *
     * @return array<int, array{value: string, label: string}> Array of status options
     */
    public static function getOptions(): array
    {
        return array_map(
            fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }
}
