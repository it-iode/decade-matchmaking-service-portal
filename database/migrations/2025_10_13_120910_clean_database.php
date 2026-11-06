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
        if(!app()->environment('production')) {
            return ;
        }
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Get all table names
        $tables = [
            'documents','notifications','requests','request_offers','request_details','opportunities','user_notification_preferences','notifications'
        ];

        // Truncate each table
        foreach ($tables as $tableName) {
            DB::statement("TRUNCATE TABLE `{$tableName}`");
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
