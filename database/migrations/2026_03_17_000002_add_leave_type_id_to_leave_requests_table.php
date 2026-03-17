<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('leave_type_id')
                ->nullable()
                ->after('user_id')
                ->constrained('leave_types')
                ->nullOnDelete();
        });

        $leaveTypeMap = DB::table('leave_types')->pluck('id', 'name');

        foreach ($leaveTypeMap as $name => $id) {
            DB::table('leave_requests')
                ->whereNull('leave_type_id')
                ->where('leave_type', $name)
                ->update(['leave_type_id' => $id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leave_type_id');
        });
    }
};
