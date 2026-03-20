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
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'shift_on_time_until')) {
                $table->time('shift_on_time_until')->nullable()->after('shift_name');
            }

            if (!Schema::hasColumn('attendances', 'shift_clock_out_after')) {
                $table->time('shift_clock_out_after')->nullable()->after('shift_on_time_until');
            }

            if (!Schema::hasColumn('attendances', 'shift_is_overnight')) {
                $table->boolean('shift_is_overnight')->default(false)->after('shift_clock_out_after');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('attendances', 'shift_on_time_until')) {
                $columnsToDrop[] = 'shift_on_time_until';
            }

            if (Schema::hasColumn('attendances', 'shift_clock_out_after')) {
                $columnsToDrop[] = 'shift_clock_out_after';
            }

            if (Schema::hasColumn('attendances', 'shift_is_overnight')) {
                $columnsToDrop[] = 'shift_is_overnight';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
