<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('opportunity_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('ip_hash', 64);
            $table->string('user_agent', 512);
            $table->string('referer', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('opportunity_id');
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['opportunity_id', 'created_at']);
        });
    }

    public function down(): void
    {
        //
    }
};
