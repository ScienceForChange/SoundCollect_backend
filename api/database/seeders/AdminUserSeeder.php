<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // DB::table('admin_users')->insert([
        //     'id' => Str::uuid(),
        //     'avatar_id' => '1',
        //     'email' => 'admin@scienceforchange.eu',
        //     'password' => Hash::make('password'),
        //     'remember_token' => Str::random(10),
        //     'email_verified_at' => now(),
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);
    }
}
