<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_notification_preferences', function (Blueprint $table) {
            // Add entity_type column with default value 'request' for backward compatibility
            $table->string('entity_type')->default('request')->after('user_id');
            
            // Drop existing unique constraint
            $table->dropUnique('unique_user_attribute_preference');
            
            // Add new unique constraint including entity_type
            $table->unique(['user_id', 'entity_type', 'attribute_type', 'attribute_value'], 'unique_user_entity_attribute_preference');
            
            // Add index for entity_type for performance
            $table->index(['entity_type'], 'idx_entity_type');
            $table->index(['entity_type', 'attribute_type', 'notification_enabled'], 'idx_entity_attribute_notification');
        });
        
        // Set all existing records to 'request' entity type
        DB::table('user_notification_preferences')
            ->whereNull('entity_type')
            ->orWhere('entity_type', '')
            ->update(['entity_type' => 'request']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notification_preferences', function (Blueprint $table) {
            // Drop new indexes
            $table->dropIndex('idx_entity_type');
            $table->dropIndex('idx_entity_attribute_notification');
            
            // Drop new unique constraint
            $table->dropUnique('unique_user_entity_attribute_preference');
            
            // Restore original unique constraint
            $table->unique(['user_id', 'attribute_type', 'attribute_value'], 'unique_user_attribute_preference');
            
            // Drop entity_type column
            $table->dropColumn('entity_type');
        });
    }
};
