<?php

declare(strict_types=1);

namespace App\Domains\Notification\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's notification settings under the opt-out model.
 *
 * One row per user (1:1 with {@see User}). Holds the master email switch plus,
 * per entity, the taxonomy values the user has opted OUT of. A null/empty
 * column means every value for that entity is enabled.
 */
class UserNotificationSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'email_notifications_enabled',
        'opportunity',
        'request',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_notifications_enabled' => 'boolean',
            'opportunity' => 'array',
            'request' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
