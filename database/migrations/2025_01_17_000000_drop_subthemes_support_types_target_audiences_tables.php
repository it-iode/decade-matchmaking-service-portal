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
        Schema::dropIfExists('request_target_audience');
        Schema::dropIfExists('request_support_type');
        Schema::dropIfExists('request_subtheme');
        Schema::dropIfExists('target_audiences');
        Schema::dropIfExists('support_types');
        Schema::dropIfExists('subthemes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration, as these tables are being removed permanently
    }
}; 