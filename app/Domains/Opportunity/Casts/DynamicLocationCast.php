<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Casts;

use App\Domains\Opportunity\Enums\CoverageActivity;
use App\Shared\Enums\Country;
use App\Shared\Enums\Ocean;
use App\Shared\Enums\Region;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Dynamic cast for implementation_location based on coverage_activity value.
 *
 * Handles both single and array values with automatic enum casting.
 */
class DynamicLocationCast implements CastsAttributes
{
    /**
     * Cast the given value from database.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $coverageActivity = $this->getCoverageActivity($attributes);

        if ($coverageActivity === null) {
            return $this->decodeJsonIfNeeded($value);
        }

        return $this->castFromDatabase($value, $coverageActivity);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $coverageActivity = $this->getCoverageActivity($attributes);

        if ($coverageActivity === null) {
            return json_encode($value);
        }

        return $this->prepareForStorage($value, $coverageActivity);
    }

    /**
     * Cast value from database based on coverage activity.
     */
    private function castFromDatabase(mixed $value, CoverageActivity $coverageActivity): mixed
    {
        // Handle global coverage
        if ($coverageActivity === CoverageActivity::GLOBAL) {
            return [CoverageActivity::GLOBAL];
        }

        // Decode JSON if necessary
        $decodedValue = $this->decodeJsonIfNeeded($value);

        // Handle arrays
        if (is_array($decodedValue)) {
            return array_map(
                fn ($val) => $this->castSingleValue($val, $coverageActivity),
                $decodedValue
            );
        }

        // Handle single values
        return $this->castSingleValue($decodedValue, $coverageActivity);
    }

    /**
     * Cast single value to appropriate enum.
     */
    private function castSingleValue(mixed $value, CoverageActivity $coverageActivity): mixed
    {
        if ($value === null || ! is_string($value)) {
            return $value;
        }

        return match ($coverageActivity) {
            CoverageActivity::COUNTRY => Country::tryFrom($value) ?? $value,
            CoverageActivity::REGIONS => Region::tryFrom($value) ?? $value,
            CoverageActivity::OCEANBASED => Ocean::tryFrom($value) ?? $value,
            default => $value,
        };
    }

    /**
     * Prepare value for storage based on coverage activity.
     */
    private function prepareForStorage(mixed $value, CoverageActivity $coverageActivity): ?string
    {
        // Handle global coverage
        if ($coverageActivity === CoverageActivity::GLOBAL) {
            return json_encode('Global');
        }

        // Handle arrays
        if (is_array($value)) {
            $preparedValues = array_map(
                fn ($val) => $this->extractEnumValue($val),
                $value
            );

            return json_encode($preparedValues);
        }

        // Handle single values
        $extractedValue = $this->extractEnumValue($value);

        return json_encode($extractedValue);
    }

    /**
     * Extract value from enum or return as-is.
     */
    private function extractEnumValue(mixed $value): ?string
    {
        if ($value instanceof Country || $value instanceof Region || $value instanceof Ocean) {
            return $value->value;
        }

        if (is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Get coverage activity from attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function getCoverageActivity(array $attributes): ?CoverageActivity
    {
        $coverageValue = $attributes['coverage_activity'] ?? null;

        if ($coverageValue === null) {
            return null;
        }

        // Handle both string and enum values
        if ($coverageValue instanceof CoverageActivity) {
            return $coverageValue;
        }

        return CoverageActivity::tryFrom($coverageValue);
    }

    /**
     * Decode JSON if needed.
     */
    private function decodeJsonIfNeeded(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }
}
