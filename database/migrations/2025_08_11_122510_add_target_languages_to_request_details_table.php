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
            $table->json('target_languages')->nullable();
            $table->text('target_languages_other')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            $table->dropColumn(['target_languages', 'target_languages_other']);
        });
    }
};
