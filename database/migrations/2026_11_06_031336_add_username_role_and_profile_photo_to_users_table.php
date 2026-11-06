
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('name');
            $table->string('role')->default('member')->after('password');
            $table->string('profile_photo_path')->nullable()->after('role');
        });

        // Perbaiki akun Admin.
        DB::table('users')
            ->where('email', 'admin@stock.test')
            ->update([
                'username' => 'admin',
                'role' => 'admin',
            ]);

        // Perbaiki akun Member.
        DB::table('users')
            ->where('email', 'member@stock.test')
            ->update([
                'username' => 'member',
                'role' => 'member',
            ]);

        // Beri username unik untuk akun lain yang masih kosong.
        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('username')
                    ->orWhere('username', '');
            })
            ->orderBy('id')
            ->get(['id'])
            ->each(fn ($user) => DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => 'user_'.$user->id]));

        // Tambahkan unique index.
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role', 'profile_photo_path']);
        });
    }
};
