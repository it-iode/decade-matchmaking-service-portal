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
        Schema::dropIfExists('request_statuses');
        Schema::create('request_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status_label')->nullable();
            $table->string('status_code')->nullable(false);
            $table->timestamps();
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->text('request_data')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->unsignedBigInteger('matched_partner_id')->nullable();
            $table->foreign('matched_partner_id')->references('id')->on('users');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_statuses');
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['request_data', 'status', 'city', 'country']);
        });
    }
};
