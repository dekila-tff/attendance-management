<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('role_id')->nullable()->after('is_admin');
        });

        // Map existing role values to role_id
        // 1 = MS, 2 = HoD, 3 = Employee
        DB::statement("UPDATE users SET role_id = CASE 
            WHEN role = 'MS' OR role = 'Medical superintendent' THEN 1
            WHEN role = 'HoD' THEN 2
            WHEN role = 'Employee' THEN 3
            ELSE 3
        END");

        // Drop the old role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('Employee');
        });

        // Map role_id back to role
        DB::statement("UPDATE users SET role = CASE 
            WHEN role_id = 1 THEN 'MS'
            WHEN role_id = 2 THEN 'HoD'
            WHEN role_id = 3 THEN 'Employee'
            ELSE 'Employee'
        END");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_id');
        });
    }
};
