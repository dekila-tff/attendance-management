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
        Schema::table('users', function (Blueprint $table) {
            $table->string('eid')->nullable()->unique()->after('id');
            $table->string('designation')->nullable()->after('name');
            $table->string('department')->nullable()->after('designation');
            $table->string('role')->nullable()->after('is_admin');
            $table->string('status')->default('Active')->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['eid', 'designation', 'department', 'role', 'status']);
        });
    }
};
