<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the default administrator when it does not exist yet.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'Administrator',
                'password' => 'admin',
                'email_verified_at' => now(),
            ],
        );
    }
}
