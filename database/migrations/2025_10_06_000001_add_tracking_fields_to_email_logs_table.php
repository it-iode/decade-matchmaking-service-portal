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
        Schema::table('email_logs', function (Blueprint $table) {
            // Add mandrill_code for API response codes
            $table->string('mandrill_code', 50)->nullable()->after('mandrill_id');

            // Add tracking timestamps
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('opened_at')->nullable()->after('delivered_at');
            $table->timestamp('clicked_at')->nullable()->after('opened_at');
            $table->timestamp('bounced_at')->nullable()->after('clicked_at');

            // Add tracking counters
            $table->unsignedInteger('open_count')->default(0)->after('bounced_at');
            $table->unsignedInteger('click_count')->default(0)->after('open_count');

            // Update status enum to include more statuses
            $table->dropColumn('status');
        });

        // Re-add status with more options
        Schema::table('email_logs', function (Blueprint $table) {
            $table->enum('status', [
                'queued',
                'sending',
                'sent',
                'delivered',
                'opened',
                'clicked',
                'bounced',
                'rejected',
                'failed',
                'spam'
            ])->default('queued')->after('template_name');

            // Add additional indexes for tracking queries
            $table->index('delivered_at');
            $table->index('opened_at');
            $table->index(['event_name', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            // Drop new indexes
            $table->dropIndex(['event_name', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['delivered_at']);
            $table->dropIndex(['opened_at']);

            // Drop new columns
            $table->dropColumn('mandrill_code');
            $table->dropColumn('delivered_at');
            $table->dropColumn('opened_at');
            $table->dropColumn('clicked_at');
            $table->dropColumn('bounced_at');
            $table->dropColumn('open_count');
            $table->dropColumn('click_count');

            // Drop and recreate status with original values
            $table->dropColumn('status');
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->enum('status', ['queued', 'sent', 'delivered', 'bounced', 'rejected', 'failed'])
                ->default('queued')
                ->after('template_name');
        });
    }
};