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
        if (!Schema::hasColumn('leave_types', 'description')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->string('description', 1000)->nullable()->after('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('leave_types', 'description')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
