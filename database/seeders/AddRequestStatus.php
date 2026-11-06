<?php

namespace Database\Seeders;

use App\Domains\Request\Models\Status;
use Illuminate\Database\Seeder;

class AddRequestStatus extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Status::create([
            'status_label' => 'Draft',
            'status_code' => 'draft',
        ]);
        Status::create([
            'status_label' => 'Under Review',
            'status_code' => 'under_review',
        ]);
        Status::create([
            'status_label' => 'Validated',
            'status_code' => 'validated',
        ]);
        Status::create([
            'status_label' => 'Offer made',
            'status_code' => 'offer_made',
        ]);
        Status::create([
            'status_label' => 'In Implementation',
            'status_code' => 'in_implementation',
        ]);
        Status::create([
            'status_label' => 'Rejected',
            'status_code' => 'rejected',
        ]);
        Status::create([
            'status_label' => 'Unmatched',
            'status_code' => 'unmatched',
        ]);
        Status::create([
            'status_label' => 'Closed',
            'status_code' => 'closed',
        ]);
    }
}
