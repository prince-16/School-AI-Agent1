<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $admin = User::query()->firstOrNew([
            'email' => 'admin@school.test',
        ]);

        $admin->name = 'Platform Admin';
        $admin->email = 'admin@school.test';
        $admin->password = 'password';
        $admin->is_platform_admin = true;
        $admin->email_verified_at = now();
        $admin->save();

        $this->call(DemoDataSeeder::class);
    }
}
