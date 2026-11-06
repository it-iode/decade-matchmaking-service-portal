<?php

namespace App\Domains\Request\Models;

use App\Domains\Notification\Models\RequestSubscription;
use App\Domains\Offer\Enums\RequestOfferStatus;
use App\Domains\Offer\Models\Offer;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Request extends Model
{
    protected $table = 'requests';

    protected $primaryKey = 'id';

    protected $fillable = [
        'status_id',
        'user_id',
        'matched_partner_id',
    ];

    protected $hidden = ['updated_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchedPartner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    // New normalized relationships
    public function detail(): HasOne
    {
        return $this->hasOne(Detail::class);
    }

    /**
     * Relationship: Get the active offer for this request
     */
    public function activeOffer(): HasOne
    {
        return $this->hasOne(Offer::class)
            ->where('status', RequestOfferStatus::ACTIVE);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(RequestSubscription::class);
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'request_subscriptions')
            ->withPivot(['subscribed_by_admin', 'admin_user_id'])
            ->withTimestamps();
    }
}
