<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('leave_requests')
            ->where('submit_to', 'HoD')
            ->whereRaw('LOWER(hod_status) = ?', ['forwarded'])
            ->update(['hod_status' => 'Pending']);

        DB::statement("ALTER TABLE leave_requests MODIFY hod_status VARCHAR(255) NOT NULL DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE leave_requests MODIFY hod_status VARCHAR(255) NOT NULL DEFAULT 'Forwarded'");

        DB::table('leave_requests')
            ->where('submit_to', 'HoD')
            ->whereRaw('LOWER(hod_status) = ?', ['pending'])
            ->update(['hod_status' => 'Forwarded']);
    }
};
