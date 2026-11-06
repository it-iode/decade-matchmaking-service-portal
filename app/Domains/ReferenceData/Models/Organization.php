<?php

namespace App\Domains\ReferenceData\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $table = 'organizations';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
        'link',
    ];
}
