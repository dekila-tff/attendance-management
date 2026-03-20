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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        DB::table('permissions')->insert([
            ['name' => 'Apply_leave', 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Approve_leave', 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Forward_leave', 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Reject_leave', 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
