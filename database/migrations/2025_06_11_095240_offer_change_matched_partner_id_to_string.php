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
        Schema::table('request_offers', function (Blueprint $table) {
            $table->dropForeign('request_offers_matched_partner_id_foreign');
            $table->string('matched_partner_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_offers', function (Blueprint $table) {
            //
        });
    }
};
