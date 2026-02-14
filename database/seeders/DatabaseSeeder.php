<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@pos.test',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@pos.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@pos.test',
                'role' => 'manager',
            ],
            [
                'name' => 'Kasir',
                'email' => 'kasir@pos.test',
                'role' => 'kasir',
            ],
        ];

        $this->call(ShieldSeeder::class);

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            $role = Role::where('name', $userData['role'])->first();
            if ($role && ! $user->hasRole($userData['role'])) {
                $user->assignRole($role);
            }
        }
    }
}
