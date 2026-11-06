<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move notification opt-out state off the `users` table into a dedicated
     * 1:1 table so future notification-schema changes never touch `users`.
     *
     *  - email_notifications_enabled: master switch (default on = opt-out model)
     *  - opportunity: opted-out Opportunity\Type values (null = all enabled)
     *  - request:     opted-out Request\DecadeChallenge values (null = all enabled)
     *
     * Existing per-user opt-out preferences are intentionally NOT migrated;
     * every user is reset to defaults (subscribed, no opt-outs). A default row
     * is created for each existing user so the audience `whereHas` queries work
     * without a "row missing = subscribed" branch.
     */
    public function up(): void
    {
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('email_notifications_enabled')->default(true);
            $table->json('opportunity')->nullable();
            $table->json('request')->nullable();
            $table->timestamps();
        });

        DB::table('users')->orderBy('id')->chunkById(500, function ($users): void {
            $now = now();
            $rows = [];

            foreach ($users as $user) {
                $rows[] = [
                    'user_id' => $user->id,
                    'email_notifications_enabled' => true,
                    'opportunity' => null,
                    'request' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                DB::table('user_notification_settings')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_settings');
    }
};
