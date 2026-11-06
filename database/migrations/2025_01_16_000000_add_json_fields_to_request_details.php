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
        Schema::table('request_details', function (Blueprint $table) {
            // Add JSON fields for arrays
            $table->json('subthemes')->nullable()->after('delivery_country');
            $table->json('support_types')->nullable()->after('subthemes');
            $table->json('target_audience')->nullable()->after('support_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            $table->dropColumn(['subthemes', 'support_types', 'target_audience']);
        });
    }
};
