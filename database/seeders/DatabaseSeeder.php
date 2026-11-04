<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ms_user')->insert([
            [
                'nama_lengkap' => 'Mochi Madiun',
                'email'        => 'admin@mochi.test',
                'password'     => Hash::make('secret-password'),
                'status'       => 'Aktif',
                'role'         => 'SuperAdmin',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'nama_lengkap' => 'Kasir',
                'email'        => 'kasir@example.com',
                'password'     => Hash::make('secret-password'),
                'status'       => 'Aktif',
                'role'         => 'Kasir',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        $this->call(ProductCatalogSeeder::class);
    }
}