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
            if (!Schema::hasColumn('users', 'device_id')) {
                $table->string('device_id', 120)->nullable()->after('remember_token');
            }

            if (!Schema::hasColumn('users', 'verification_code')) {
                $table->string('verification_code')->nullable()->after('device_id');
            }

            if (!Schema::hasColumn('users', 'verification_code_expires_at')) {
                $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'verification_code_expires_at')) {
                $table->dropColumn('verification_code_expires_at');
            }

            if (Schema::hasColumn('users', 'verification_code')) {
                $table->dropColumn('verification_code');
            }

            if (Schema::hasColumn('users', 'device_id')) {
                $table->dropColumn('device_id');
            }
        });
    }
};
