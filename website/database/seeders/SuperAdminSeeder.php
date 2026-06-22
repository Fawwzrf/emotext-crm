<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@emotext.id'],
            [
                'name'                => 'Emotext Admin',
                'company_name'        => 'PT Emotext Indonesia',
                'password'            => Hash::make('emotext@Admin2026!'),
                'is_superadmin'       => true,
                'subscription_status' => 'active',
                'api_token'           => User::generateApiToken(),
                'email_verified_at'   => now(),
                'remember_token'      => Str::random(10),
            ]
        );

        $this->command->info('✅ Super Admin berhasil dibuat!');
        $this->command->info('   Email   : admin@emotext.id');
        $this->command->info('   Password: emotext@Admin2026!');
        $this->command->warn('   ⚠️  Segera ubah password setelah login pertama!');
    }
}
