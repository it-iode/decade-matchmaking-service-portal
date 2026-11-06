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
            $table->dropIndex(['delivery_country']);
            $table->json('delivery_country')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            $table->string('delivery_country')->nullable()->change();
            $table->index(['delivery_country']);
        });
    }
};
