<?php

namespace App\Domains\User\Models;

use App\Domains\Notification\Models\RequestSubscription;
use App\Domains\Notification\Models\SystemNotification;
use App\Domains\Notification\Models\UserNotificationSetting;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Request\Enums\DecadeChallenge;
use App\Domains\Request\Models\Request;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasOneTimePasswords, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'country',
        'city',
        'provider',
        'provider_id',
        'avatar',
        'is_blocked',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['is_partner', 'is_admin'];

    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole('administrator');
    }

    public function getIsPartnerAttribute(): bool
    {
        return $this->hasRole('partner');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    /**
     * @toDo remove this and remove column 'matched_partner_id' from requests table since it's now handled by the request offer
     */
    public function matchedRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'matched_partner_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function requestSubscriptions(): HasMany
    {
        return $this->hasMany(RequestSubscription::class);
    }

    public function notificationSetting(): HasOne
    {
        return $this->hasOne(UserNotificationSetting::class);
    }

    public function subscribedRequests(): BelongsToMany
    {
        return $this->belongsToMany(Request::class, 'request_subscriptions')
            ->withPivot(['subscribed_by_admin', 'admin_user_id'])
            ->withTimestamps();
    }

    /**
     * Check if user is authenticated via social provider
     */
    public function isSocialUser(): bool
    {
        return ! empty($this->provider) && ! empty($this->provider_id);
    }

    /**
     * Get the user's avatar URL with fallback
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return $this->avatar;
        }

        // Fallback to Gravatar or default avatar
        $hash = md5(strtolower(trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150";
    }

    /**
     * Check if user is blocked
     */
    public function isBlocked(): bool
    {
        return $this->is_blocked === true;
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return ! $this->isBlocked() && $this->hasVerifiedEmail();
    }

    /**
     * Whether the user receives email notifications at all (master switch).
     * Defaults to true (opt-out model) when the column is unset.
     */
    public function isSubscribedToEmails(): bool
    {
        return $this->notificationSetting?->email_notifications_enabled ?? true;
    }

    /**
     * Whether a given taxonomy value for an entity is enabled for this user.
     * A value is enabled unless the master switch is off or it is listed in
     * the user's opt-outs for that entity.
     *
     * @param  string  $entity  'opportunity' | 'request'
     */
    public function notificationEnabledFor(string $entity, string $value): bool
    {
        if (! $this->isSubscribedToEmails()) {
            return false;
        }

        $optOuts = $this->notificationSetting?->{$entity} ?? [];

        return ! in_array($value, $optOuts, true);
    }

    /**
     * The opportunity type values this user currently receives.
     *
     * @return array<int, string>
     */
    public function enabledOpportunityTypes(): array
    {
        if (! $this->isSubscribedToEmails()) {
            return [];
        }

        $optOuts = $this->notificationSetting?->opportunity ?? [];

        return array_values(array_filter(
            array_map(fn ($case) => $case->value, Type::cases()),
            fn ($value) => ! in_array($value, $optOuts, true)
        ));
    }

    /**
     * The request Decade Challenge values this user currently receives.
     *
     * @return array<int, string>
     */
    public function enabledRequestChallenges(): array
    {
        if (! $this->isSubscribedToEmails()) {
            return [];
        }

        $optOuts = $this->notificationSetting?->request ?? [];

        return array_values(array_filter(
            array_map(fn ($case) => $case->value, DecadeChallenge::cases()),
            fn ($value) => ! in_array($value, $optOuts, true)
        ));
    }
}
