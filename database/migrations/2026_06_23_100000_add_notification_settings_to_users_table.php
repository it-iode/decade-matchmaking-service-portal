<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Replaces the opt-in `user_notification_preferences` rows with opt-out
     * state stored directly on the user:
     *  - email_notifications_enabled: master switch (default on = opt-out model)
     *  - notification_opt_outs: only the taxonomy values the user disabled,
     *    grouped by entity, e.g. {"opportunity":["webinar"],"request":["other"]}.
     *    Empty/null means every notification type is enabled.
     */
    public function up(): void
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_notifications_enabled', 'notification_opt_outs']);
        });
    }
};
