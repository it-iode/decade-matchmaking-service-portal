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
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn(['status_id']);
            $table->unsignedBigInteger('status_id')->nullable(false);
            $table->foreign('status_id')->references('id')->on('request_statuses');
        });
        Schema::table('request_statuses', function (Blueprint $table) {
            $table->unique('status_code')->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_statuses', function (Blueprint $table) {
            $table->dropUnique(['status_code']);
        });
        
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn(['status_id']);
            $table->unsignedBigInteger('status_id')->nullable();
            $table->foreign('status_id')->references('id')->on('request_statuses');
        });
    }
};
