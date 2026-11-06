<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the legacy opt-in `user_notification_preferences` table.
     *
     * The feature it backed was removed in issue #206 and its data was
     * converted to the opt-out model at that time. Notification state now
     * lives in `user_notification_settings`. The reverse migration restores
     * the final schema of the table only; the old rows are not recoverable.
     */
    public function up(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }

    public function down(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('entity_type')->default('request');
            $table->string('attribute_type');
            $table->string('attribute_value');
            $table->boolean('email_notification_enabled')->default(false);
            $table->timestamps();

            $table->unique(
                ['user_id', 'entity_type', 'attribute_type', 'attribute_value'],
                'unique_user_entity_attribute_preference'
            );
            $table->index(['entity_type'], 'idx_entity_type');
        });
    }
};
