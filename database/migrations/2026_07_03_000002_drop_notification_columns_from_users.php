<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the notification columns now that state lives in
     * `user_notification_settings`. The previous per-user opt-out data was
     * intentionally discarded, so the reverse migration only restores the
     * schema (with defaults), not the old values.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_notifications_enabled', 'notification_opt_outs']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_notifications_enabled')
                ->default(true)
                ->after('last_login_at');
            $table->json('notification_opt_outs')
                ->nullable()
                ->after('email_notifications_enabled');
        });
    }
};
