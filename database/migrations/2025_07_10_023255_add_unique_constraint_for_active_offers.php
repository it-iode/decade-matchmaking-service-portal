<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('request_offers', function (Blueprint $table) {
            // Add a unique constraint for active offers per request
            // This ensures only one active offer per request at the database level
//            $table->unique(['request_id', 'status'], 'unique_active_offer_per_request')
//                ->where('status', 1); // 1 = ACTIVE status
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
//        Schema::table('request_offers', function (Blueprint $table) {
//            $table->dropUnique('unique_active_offer_per_request');
//        });
    }
};
