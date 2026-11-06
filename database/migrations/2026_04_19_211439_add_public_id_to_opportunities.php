<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // 1. Add column nullable + unique.
        Schema::table('opportunities', function (Blueprint $table) {
            $table->ulid('public_id')->nullable()->unique()->after('id');
        });

        // 2. Backfill using raw DB::update loop to avoid firing OpportunityObserver.
        //    We can't use a single SQL UPDATE because each row needs a different ULID.
        $ids = DB::table('opportunities')->whereNull('public_id')->pluck('id');
        foreach ($ids as $id) {
            DB::table('opportunities')
                ->where('id', $id)
                ->update(['public_id' => (string) Str::ulid()]);
        }

        // 3. Enforce NOT NULL now that every row has a value.
        Schema::table('opportunities', function (Blueprint $table) {
            $table->ulid('public_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
