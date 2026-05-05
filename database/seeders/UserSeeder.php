<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            [
                'name' => 'Admin Library',
                'email' => 'admin@example.com',
            ],
            [
                'name' => 'Petugas Library',
                'email' => 'petugas@example.com',
            ],
        ])->each(fn (array $admin): Admin => Admin::query()->updateOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'password' => Hash::make('password'),
            ]
        ));

        collect([
            [
                'member_code' => 'AG-DEMO-20260505-0001',
                'name' => 'Anggota Demo',
                'email' => 'anggota@example.com',
                'phone' => '081234567890',
                'address' => 'Jakarta',
            ],
            [
                'member_code' => 'AG-SR-20260505-0002',
                'name' => 'Sari Rahma',
                'email' => 'sari@example.com',
                'phone' => '082233445566',
                'address' => 'Bandung',
            ],
            [
                'member_code' => 'AG-BW-20260505-0003',
                'name' => 'Bima Wicaksono',
                'email' => 'bima@example.com',
                'phone' => '083344556677',
                'address' => 'Surabaya',
            ],
        ])->each(fn (array $member): Member => Member::query()->updateOrCreate(
            ['email' => $member['email']],
            [
                'member_code' => $member['member_code'],
                'name' => $member['name'],
                'password' => Hash::make('password'),
                'phone' => $member['phone'],
                'address' => $member['address'],
            ]
        ));
    }
}
