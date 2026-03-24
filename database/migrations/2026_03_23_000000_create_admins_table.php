<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->bigIncrements('admin_id');
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->timestamps();
        });

        $adminUser = DB::table('users')
            ->where('email', env('ADMIN_EMAIL', 'admin@ntmh.bt'))
            ->first();

        if (! $adminUser) {
            $adminUsername = trim((string) env('ADMIN_USERNAME', ''));

            if ($adminUsername !== '') {
                $adminUser = DB::table('users')
                    ->where('eid', $adminUsername)
                    ->orWhere('email', $adminUsername)
                    ->first();
            }
        }

        if (! $adminUser) {
            return;
        }

        DB::table('admins')->insert([
            'name' => $adminUser->name ?? env('ADMIN_NAME', 'System Admin'),
            'username' => $adminUser->eid ?? env('ADMIN_USERNAME', 'admin'),
            'password' => $adminUser->password ?? Hash::make(env('ADMIN_PASSWORD', 'Admin@123')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
