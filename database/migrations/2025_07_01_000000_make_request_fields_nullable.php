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
            $table->string('capacity_development_title')->nullable()->change();
            $table->string('is_related_decade_action')->nullable()->change();
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('related_activity')->nullable()->change();
            $table->string('delivery_format')->nullable()->change();
            $table->string('gap_description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_details', function (Blueprint $table) {
            $table->string('capacity_development_title')->nullable(false)->change();
            $table->string('is_related_decade_action')->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('related_activity')->nullable(false)->change();
            $table->string('delivery_format')->nullable(false)->change();
            $table->string('gap_description')->nullable(false)->change();
        });
    }
};
