<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('emailcv.admin.email');

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->fill([
                'name' => (string) config('emailcv.admin.name'),
                'password' => Hash::make((string) config('emailcv.admin.password')),
            ]);
        }

        $user->forceFill(['role' => 'admin', 'email_verified_at' => now()])->save();
    }
}
