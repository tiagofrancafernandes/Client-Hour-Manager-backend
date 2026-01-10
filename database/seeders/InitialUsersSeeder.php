<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class InitialUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@mail.com',
                'password' => 'power@123',
                'active' => true,
                'role' => 'admin',
            ],
            [
                'name' => 'Staff',
                'email' => 'staff@mail.com',
                'password' => 'power@123',
                'active' => true,
                'role' => 'staff',
            ],
            [
                'name' => 'Customer',
                'email' => 'customer@mail.com',
                'password' => 'power@123',
                'active' => true,
                'role' => 'customer',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate([
                'email' => $userData['email'],
            ], [
                ...\Arr::except($userData, ['role']),
                'password' => \Hash::make($userData['password'] ?? 'password'),
            ]);

            if (!class_exists(Role::class)) {
                continue;
            }

            $roles = Role::where('name', $userData['role'] ?? 'user')->select(['name'])->get();

            if ($roles?->count()) {
                $user->syncRoles($roles->pluck('name')->toArray());
            }
        }
    }
}
