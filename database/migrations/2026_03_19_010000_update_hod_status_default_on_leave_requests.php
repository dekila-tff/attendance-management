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

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE leave_requests ALTER COLUMN hod_status TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE leave_requests ALTER COLUMN hod_status SET NOT NULL");
            DB::statement("ALTER TABLE leave_requests ALTER COLUMN hod_status SET DEFAULT 'Pending'");
        } else {
            DB::statement("ALTER TABLE leave_requests MODIFY hod_status VARCHAR(255) NOT NULL DEFAULT 'Pending'");
        }
    }

    /**
     * Reverse the migrations.
     */ 
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE leave_requests ALTER COLUMN hod_status TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE leave_requests ALTER COLUMN hod_status SET NOT NULL");
            DB::statement("ALTER TABLE leave_requests ALTER COLUMN hod_status SET DEFAULT 'Forwarded'");
        } else {
            DB::statement("ALTER TABLE leave_requests MODIFY hod_status VARCHAR(255) NOT NULL DEFAULT 'Forwarded'");
        }

        DB::table('leave_requests')
            ->where('submit_to', 'HoD')
            ->whereRaw('LOWER(hod_status) = ?', ['pending'])
            ->update(['hod_status' => 'Forwarded']);
    }
};
