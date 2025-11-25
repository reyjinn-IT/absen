<?php
// database/seeders/KelasSeeder.php
namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Matakuliah;
use App\Models\User;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = User::where('role', 'dosen')->first();
        $mahasiswa = User::where('role', 'mahasiswa')->first();
        $pemrogramanWeb = Matakuliah::where('kode_mk', 'MK001')->first();

        $kelas = Kelas::firstOrCreate(
            ['kode_kelas' => 'K001'],
            [
                'nama_kelas' => 'Pemrograman Web A',
                'matakuliah_id' => $pemrogramanWeb->id,
                'dosen_id' => $dosen->id
            ]
        );

        if (!$kelas->mahasiswa()->where('mahasiswa_id', $mahasiswa->id)->exists()) {
            $kelas->mahasiswa()->attach($mahasiswa->id);
        }
    }
}