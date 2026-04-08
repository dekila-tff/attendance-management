<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('adhoc_requests', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');
        });

        DB::statement('UPDATE adhoc_requests ar INNER JOIN users u ON u.user_id = ar.user_id SET ar.name = u.name WHERE ar.name IS NULL');
    }

    public function down(): void
    {
        Schema::table('adhoc_requests', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
