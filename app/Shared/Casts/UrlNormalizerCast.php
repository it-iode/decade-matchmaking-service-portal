<?php

declare(strict_types=1);

namespace App\Shared\Casts;

use App\Shared\Support\UrlNormalizer;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class UrlNormalizerCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return $value;
        }

        return UrlNormalizer::normalize($value);
    }
}
