<?php

namespace Database\Seeders;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@stock.test',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        $member = User::updateOrCreate(
            ['username' => 'member'],
            [
                'name' => 'Stock Member',
                'email' => 'member@stock.test',
                'password' => Hash::make('member'),
                'role' => 'member',
            ]
        );

        if (! Stock::where('user_id', $member->id)->exists()) {
            Stock::factory()
                ->count(10)
                ->create([
                    'user_id' => $member->id,
                ]);
        }
    }
}
