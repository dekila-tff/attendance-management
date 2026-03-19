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
        Schema::create('department_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('department');
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->time('on_time_until');
            $table->time('clock_out_after');
            $table->boolean('is_overnight')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['department', 'is_active']);
            $table->unique(['department', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_shifts');
    }
};
