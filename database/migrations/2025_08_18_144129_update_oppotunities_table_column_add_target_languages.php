<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->json('target_languages')->nullable();
            $table->text('target_languages_other')->nullable();
            $table->json('implementation_location')->nullable()->change();
            $table->json('target_audience')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn(['target_languages', 'target_languages_other']);
            $table->string('implementation_location')->nullable()->change();
            $table->string('target_audience')->nullable()->change();
        });
    }
};
