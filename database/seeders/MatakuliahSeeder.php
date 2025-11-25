<?php
// database/seeders/MatakuliahSeeder.php
namespace Database\Seeders;

use App\Models\Matakuliah;
use App\Models\User;
use Illuminate\Database\Seeder;

class MatakuliahSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = User::where('role', 'dosen')->first();

        Matakuliah::firstOrCreate(
            ['kode_mk' => 'MK001'],
            [
                'nama_mk' => 'Pemrograman Web Lanjut',
                'sks' => 3,
                'dosen_id' => $dosen->id
            ]
        );

        Matakuliah::firstOrCreate(
            ['kode_mk' => 'MK002'],
            [
                'nama_mk' => 'Basis Data Query dan Non Query',
                'sks' => 3,
                'dosen_id' => $dosen->id
            ]
        );

        Matakuliah::firstOrCreate(
            ['kode_mk' => 'MK003'],
            [
                'nama_mk' => 'Algoritma dan Pemrograman',
                'sks' => 4,
                'dosen_id' => $dosen->id
            ]
        );
    }
}