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
        Schema::create('request_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');
            $table->boolean('subscribed_by_admin')->default(false);
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Prevent duplicate subscriptions
            $table->unique(['user_id', 'request_id']);
            
            // Add indexes for performance
            $table->index('user_id');
            $table->index('request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_subscriptions');
    }
};
