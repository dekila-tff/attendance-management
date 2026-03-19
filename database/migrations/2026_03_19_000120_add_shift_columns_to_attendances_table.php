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
            $table->string('shift_name')->nullable()->after('date');
            $table->time('shift_start_time')->nullable()->after('shift_name');
            $table->time('shift_end_time')->nullable()->after('shift_start_time');
            $table->time('shift_on_time_until')->nullable()->after('shift_end_time');
            $table->time('shift_clock_out_after')->nullable()->after('shift_on_time_until');
            $table->boolean('shift_is_overnight')->default(false)->after('shift_clock_out_after');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'shift_name',
                'shift_start_time',
                'shift_end_time',
                'shift_on_time_until',
                'shift_clock_out_after',
                'shift_is_overnight',
            ]);
        });
    }
};
