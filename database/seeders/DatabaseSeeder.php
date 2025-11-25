<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@poltek-gt.ac.id'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin'
            ]
        );

        $dosen = User::firstOrCreate(
            ['email' => 'dosen@poltek-gt.ac.id'],
            [
                'name' => 'Dosen',
                'password' => Hash::make('dosen123'),
                'role' => 'dosen',
                'nidn' => '12345678',
                'phone' => '081234567890'
            ]
        );

        User::firstOrCreate(
            ['email' => 'mahasiswa@student.poltek-gt.ac.id'],
            [
                'name' => 'Mahasiswa',
                'password' => Hash::make('mahasiswa123'),
                'role' => 'mahasiswa',
                'nim' => '202401001',
                'phone' => '081234567891'
            ]
        );

        $this->call([
            MatakuliahSeeder::class,
            KelasSeeder::class,
        ]);
    }
}