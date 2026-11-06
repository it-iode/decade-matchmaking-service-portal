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
        Schema::table('opportunities', function (Blueprint $table) {
            // Add closure tracking fields
            $table->timestamp('closed_at')->nullable()->after('updated_at');
            $table->text('closed_reason')->nullable()->after('closed_at');
            $table->string('previous_status')->nullable()->after('closed_reason');

            // Add index for performance on closure queries
            $table->index(['status', 'closing_date'], 'idx_opportunities_closure');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex('idx_opportunities_closure');

            // Drop the columns
            $table->dropColumn(['closed_at', 'closed_reason', 'previous_status']);
        });
    }
};
