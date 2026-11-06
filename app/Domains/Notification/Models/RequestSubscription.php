<?php

namespace App\Domains\Notification\Models;

use App\Domains\Request\Models\Request;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'request_id',
        'subscribed_by_admin',
        'admin_user_id',
    ];

    protected $casts = [
        'subscribed_by_admin' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
