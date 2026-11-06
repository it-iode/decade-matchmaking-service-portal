<?php

declare(strict_types=1);

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
        // Drop user_id if it exists (from previous implementation)
        if (Schema::hasColumn('user_invitations', 'user_id')) {
            Schema::table('user_invitations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        // Add name column to store invitee name
        Schema::table('user_invitations', function (Blueprint $table) {
            $table->string('name')->after('email');
        });
    }
};
