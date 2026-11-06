<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the ranked Decade Challenges storage to request details.
     *
     * Shape: {"primary": "challenge-x", "secondary": "challenge-y"|null, "tertiary": "challenge-z"|null}.
     * The legacy `subthemes` / `subthemes_other` columns are intentionally kept
     * so historical requests continue to render (issue #205 decision).
     */
    public function up(): void
    {
        Schema::table('request_details', function (Blueprint $table): void {
            $table->json('decade_challenges')->nullable()->after('subthemes');
        });
    }
};
