
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            ->update([
                'username' => DB::raw("CONCAT('user_', id)"),
            ]);

        // Tambahkan unique index.
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });
    }
};
