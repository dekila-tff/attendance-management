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
        $hasStartTime = Schema::hasColumn('attendances', 'shift_start_time');
        $hasEndTime = Schema::hasColumn('attendances', 'shift_end_time');

        if (!$hasStartTime && !$hasEndTime) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) use ($hasStartTime, $hasEndTime) {
            if ($hasStartTime) {
                $table->dropColumn('shift_start_time');
            }

            if ($hasEndTime) {
                $table->dropColumn('shift_end_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasStartTime = Schema::hasColumn('attendances', 'shift_start_time');
        $hasEndTime = Schema::hasColumn('attendances', 'shift_end_time');

        if ($hasStartTime && $hasEndTime) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) use ($hasStartTime, $hasEndTime) {
            if (!$hasStartTime) {
                $table->time('shift_start_time')->nullable()->after('shift_name');
            }

            if (!$hasEndTime) {
                $table->time('shift_end_time')->nullable()->after('shift_start_time');
            }
        });
    }
};
