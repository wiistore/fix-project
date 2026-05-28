<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin utama (protected, gak bisa dihapus/diedit dari menu user)
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@kopsis.local',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_protected' => true,
                'status' => 'aktif',
            ]
        );

        // Sample kasir (boleh dihapus)
        User::updateOrCreate(
            ['username' => 'kasir1'],
            [
                'email' => 'kasir1@kopsis.local',
                'password' => Hash::make('kasir123'),
                'role' => 'kasir',
                'is_protected' => false,
                'status' => 'aktif',
            ]
        );

        // Kategori awal
        DB::table('kategori')->updateOrInsert(
            ['nama' => 'ATK'],
            ['deskripsi' => 'Alat tulis kantor', 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('kategori')->updateOrInsert(
            ['nama' => 'Sembako'],
            ['deskripsi' => 'Sembilan bahan pokok', 'created_at' => now(), 'updated_at' => now()]
        );
    }
}
