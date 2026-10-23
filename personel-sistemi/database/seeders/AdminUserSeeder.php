<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Sisteme giriş yapacak ilk İK yöneticisi.
     */
    public function run(): void
    {
        $admin = config('personnel.admin');

        if (blank($admin['password'])) {
            $this->command?->error('ADMIN_PASSWORD boş. .env dosyasında bir parola belirleyip seeder\'ı tekrar çalıştırın.');

            return;
        }

        User::updateOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'password' => $admin['password'], // User modelinde 'hashed' cast'i ile bcrypt'lenir
                'email_verified_at' => now(),
            ],
        );
    }
}
