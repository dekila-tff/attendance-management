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
        if (!Schema::hasTable('tour')) {
            Schema::create('tour', function (Blueprint $table) {
                $table->bigIncrements('tour_id');
                $table->unsignedBigInteger('users_id');
                $table->unsignedBigInteger('department_id');
                $table->string('place');
                $table->date('start_date');
                $table->date('end_date');
                $table->text('purpose')->nullable();
                $table->string('office_order_pdf')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('users_id')->references('users_id')->on('users')->cascadeOnDelete();
                $table->foreign('department_id')->references('department_id')->on('departments')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour');
    }
};
