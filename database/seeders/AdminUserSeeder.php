<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the default administrator when it does not exist yet.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'Administrator',
                'password' => 'admin',
                'account_type' => 'superadmin',
                'email_verified_at' => now(),
            ],
        );

        $user->update(['account_type' => 'superadmin']);

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'tenant-utama'],
            ['name' => 'Tenant Utama', 'is_active' => true],
        );

        $tenant->users()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);
    }
}
