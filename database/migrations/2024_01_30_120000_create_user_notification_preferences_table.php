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
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('attribute_type'); // e.g., 'subtheme', 'coverage_activity', 'implementation_location'
            $table->string('attribute_value'); // e.g., 'Ocean acidification', 'Research', 'Pacific Ocean'
            $table->boolean('notification_enabled')->default(true);
            $table->boolean('email_notification_enabled')->default(false);
            $table->timestamps();

            // Prevent duplicate preferences for the same user-attribute combination
            $table->unique(['user_id', 'attribute_type', 'attribute_value'], 'unique_user_attribute_preference');
            
            // Indexes for performance
            $table->index(['attribute_type', 'attribute_value', 'notification_enabled'], 'idx_attribute_notification_enabled');
            $table->index(['attribute_type', 'attribute_value', 'email_notification_enabled'], 'idx_attribute_email_enabled');
            $table->index(['user_id', 'notification_enabled'], 'idx_user_notification_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};