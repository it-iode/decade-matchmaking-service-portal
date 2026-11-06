<?php

namespace App\Domains\Request\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $table = 'request_statuses';

    const DRAFT_STATUS_CODE = 'draft';

    const UNDER_REVIEW_STATUS_CODE = 'under_review';

    const VALIDATED_STATUS_CODE = 'validated';

    protected $fillable = [
        'status_label',
        'status_code',
    ];

    public static function getDraftStatus()
    {
        return static::where('status_code', 'draft')->first();
    }

    public static function getUnderReviewStatus()
    {
        return static::where('status_code', 'under_review')->first();
    }
}
