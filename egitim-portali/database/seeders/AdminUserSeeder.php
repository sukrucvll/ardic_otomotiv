<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Portala giriş yapacak ilk eğitim sorumlusu.
     */
    public function run(): void
    {
        $admin = config('training.admin');

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
