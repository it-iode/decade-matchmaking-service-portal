<?php

namespace App\Domains\ReferenceData\Models;

use Illuminate\Database\Eloquent\Model;

class IOCPlatform extends Model
{
    protected $table = 'ioc_platforms';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
        'link',
        'contact',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
