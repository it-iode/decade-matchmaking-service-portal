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
        Schema::table('opportunities', function (Blueprint $table) {
            $table->json('thematic_areas')->nullable()->after('implementation_location');
            $table->string('thematic_areas_other')->nullable()->after('thematic_areas');
            $table->json('co_organizers')->nullable()->after('thematic_areas_other');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn(['thematic_areas', 'thematic_areas_other', 'co_organizers']);
        });
    }
};
