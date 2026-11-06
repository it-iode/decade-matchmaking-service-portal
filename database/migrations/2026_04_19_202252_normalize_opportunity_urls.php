<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('url', 2048)->nullable()->change();
        });

        DB::transaction(function () {
            // 1. Protocol-relative: //host → https://host
            DB::update("
                UPDATE opportunities
                SET url = CONCAT('https:', url)
                WHERE url LIKE '//%'
                  AND url NOT LIKE '///%'
                  AND deleted_at IS NULL
            ");

            // 2. Schemeless: no scheme at all, not protocol-relative
            DB::update("
                UPDATE opportunities
                SET url = CONCAT('https://', url)
                WHERE url IS NOT NULL
                  AND url <> ''
                  AND url NOT LIKE '//%'
                  AND url NOT REGEXP '^[A-Za-z][A-Za-z0-9+.-]*:'
                  AND deleted_at IS NULL
            ");

            // 3. Lowercase uppercase schemes (HTTP://, HttpS://)
            DB::update("
                UPDATE opportunities
                SET url = CONCAT(
                    LOWER(SUBSTRING_INDEX(url, '://', 1)),
                    '://',
                    SUBSTRING(url, LOCATE('://', url) + 3)
                )
                WHERE url REGEXP '^[A-Za-z]*[A-Z][A-Za-z]*://'
                  AND deleted_at IS NULL
            ");
        });
    }

    public function down(): void
    {
        // Intentional no-op: malformed original values are unrecoverable.
        // VARCHAR(2048) stays — strict superset of VARCHAR(255).
    }
};
