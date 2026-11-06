<?php

namespace App\Domains\Opportunity\Models;

use App\Domains\Opportunity\Casts\DynamicLocationCast;
use App\Domains\Opportunity\Enums\CoverageActivity;
use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Enums\ThematicAreas;
use App\Domains\Opportunity\Enums\TargetAudience;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\User\Models\User;
use App\Shared\Casts\UrlNormalizerCast;
use App\Shared\Enums\Language;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Opportunity extends Model
{
    use HasFactory;

    protected $table = 'opportunities';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'type' => Type::class,
            'status' => Status::class,
            'target_audience' => AsEnumArrayObject::of(TargetAudience::class),
            'coverage_activity' => CoverageActivity::class,
            'implementation_location' => DynamicLocationCast::class,
            'target_languages' => AsEnumArrayObject::of(Language::class),
            'thematic_areas' => AsEnumCollection::of(ThematicAreas::class),
            'url' => UrlNormalizerCast::class,
            'closing_date' => 'datetime:Y-m-d',
            'key_words' => 'array',
            'co_organizers' => 'array',
        ];
    }

    public static function getTypeOptions(): array
    {
        return Type::getOptions();
    }

    protected $fillable = [
        'public_id',
        'title',
        'type',
        'closing_date',
        'coverage_activity',
        'implementation_location',
        'target_audience',
        'target_audience_other',
        'thematic_areas',
        'thematic_areas_other',
        'target_languages',
        'target_languages_other',
        'summary',
        'url',
        'key_words',
        'co_organizers',
        'user_id',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $opportunity): void {
            if (empty($opportunity->public_id)) {
                $opportunity->public_id = (string) Str::ulid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get implementation location as array.
     * Helper method for consistent array handling.
     *
     * @return array<mixed>
     */
    public function getImplementationLocationAsArray(): array
    {
        $location = $this->implementation_location;

        if ($location === null) {
            return [];
        }

        if ($location === 'Global') {
            return ['Global'];
        }

        if (is_array($location)) {
            return $location;
        }

        return [$location];
    }

    /**
     * Check if opportunity has multiple locations.
     */
    public function hasMultipleLocations(): bool
    {
        return count($this->getImplementationLocationAsArray()) > 1;
    }
}
