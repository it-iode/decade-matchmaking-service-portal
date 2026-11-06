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
            $table->dropColumn('delivery_country');
            $table->json('delivery_countries')->nullable()->after('delivery_format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            $table->dropColumn('delivery_countries');
            $table->text('delivery_country')->nullable()->after('delivery_format');
        });
    }
};
