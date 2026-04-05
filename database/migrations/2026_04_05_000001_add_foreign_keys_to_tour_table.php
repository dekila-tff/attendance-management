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
        if (!Schema::hasTable('tour')) {
            return;
        }

        $database = DB::getDatabaseName();

        $hasUsersForeign = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', 'tour')
            ->where('CONSTRAINT_NAME', 'tour_users_id_foreign')
            ->exists();

        $hasDepartmentsForeign = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', 'tour')
            ->where('CONSTRAINT_NAME', 'tour_department_id_foreign')
            ->exists();

        Schema::table('tour', function (Blueprint $table) use ($hasUsersForeign, $hasDepartmentsForeign) {
            if (!$hasUsersForeign) {
                $table->foreign('users_id', 'tour_users_id_foreign')
                    ->references('users_id')
                    ->on('users')
                    ->cascadeOnDelete();
            }

            if (!$hasDepartmentsForeign) {
                $table->foreign('department_id', 'tour_department_id_foreign')
                    ->references('department_id')
                    ->on('departments')
                    ->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tour')) {
            return;
        }

        $database = DB::getDatabaseName();

        $hasUsersForeign = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', 'tour')
            ->where('CONSTRAINT_NAME', 'tour_users_id_foreign')
            ->exists();

        $hasDepartmentsForeign = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', 'tour')
            ->where('CONSTRAINT_NAME', 'tour_department_id_foreign')
            ->exists();

        Schema::table('tour', function (Blueprint $table) use ($hasUsersForeign, $hasDepartmentsForeign) {
            if ($hasUsersForeign) {
                $table->dropForeign('tour_users_id_foreign');
            }

            if ($hasDepartmentsForeign) {
                $table->dropForeign('tour_department_id_foreign');
            }
        });
    }
};
