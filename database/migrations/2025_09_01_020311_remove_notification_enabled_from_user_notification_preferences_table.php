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
        Schema::table('user_notification_preferences', function (Blueprint $table) {
            // Drop indexes that reference notification_enabled
            $table->dropIndex('idx_attribute_notification_enabled');
            $table->dropIndex('idx_user_notification_enabled');
            $table->dropIndex('idx_entity_attribute_notification');
            
            // Drop the notification_enabled column
            $table->dropColumn('notification_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notification_preferences', function (Blueprint $table) {
            // Add back the notification_enabled column
            $table->boolean('notification_enabled')->default(true)->after('attribute_value');
            
            // Recreate the indexes
            $table->index(['attribute_type', 'attribute_value', 'notification_enabled'], 'idx_attribute_notification_enabled');
            $table->index(['user_id', 'notification_enabled'], 'idx_user_notification_enabled');
            $table->index(['entity_type', 'attribute_type', 'notification_enabled'], 'idx_entity_attribute_notification');
        });
    }
};
