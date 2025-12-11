<?php

namespace App\Http\Controllers\Api;

use App\Models\Kelas;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MahasiswaController extends Controller
{
    public function getDashboard(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total_kelas' => $user->kelasMahasiswa()->count(),
            'absensi_hari_ini' => $user->absensi()->whereDate('tanggal', today())->count(),
            'kelas_aktif' => $user->kelasMahasiswa()->with('matakuliah')->get()
        ];

        return $this->success($stats);
    }

    public function getKelasMahasiswa(Request $request)
    {
        $user = $request->user();
        $kelas = $user->kelasMahasiswa()->with(['matakuliah', 'dosen'])->get();

        return $this->success($kelas);
    }

    public function absenMasuk(Request $request)
    {
        $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'latitude' => 'required|string',
            'longitude' => 'required|string'
        ]);

        $user = $request->user();
        $kelas = Kelas::findOrFail($request->kelas_id);

        // Check if mahasiswa is enrolled in the class
        if (!$user->kelasMahasiswa()->where('kelas_id', $request->kelas_id)->exists()) {
            return $this->error('Anda tidak terdaftar di kelas ini', 403);
        }

        // Check if already absen today
        $existingAbsensi = Absensi::where('mahasiswa_id', $user->id)
            ->where('kelas_id', $request->kelas_id)
            ->whereDate('tanggal', today())
            ->first();

        if ($existingAbsensi) {
            return $this->error('Anda sudah melakukan absen hari ini', 400);
        }

        $absensi = Absensi::create([
            'mahasiswa_id' => $user->id,
            'kelas_id' => $request->kelas_id,
            'tanggal' => today(),
            'status' => 'hadir',
            'waktu_masuk' => now()->format('H:i:s'),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude
        ]);

        return $this->success($absensi, 'Absensi masuk berhasil');
    }

    public function getRiwayatAbsensi(Request $request)
    {
        $user = $request->user();
        
        $absensi = $user->absensi()
            ->with(['kelas.matakuliah'])
            ->orderBy('tanggal', 'desc')
            ->get();

        return $this->success($absensi);
    }

    public function getRekapAbsensiMahasiswa(Request $request)
    {
        $user = $request->user();

        $rekap = DB::table('absensi')
            ->where('mahasiswa_id', $user->id)
            ->select('kelas_id')
            ->selectRaw('COUNT(*) as total_pertemuan')
            ->selectRaw('SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as hadir')
            ->selectRaw('SUM(CASE WHEN status = "izin" THEN 1 ELSE 0 END) as izin')
            ->selectRaw('SUM(CASE WHEN status = "sakit" THEN 1 ELSE 0 END) as sakit')
            ->selectRaw('SUM(CASE WHEN status = "alpha" THEN 1 ELSE 0 END) as alpha')
            ->groupBy('kelas_id')
            ->get();

        return $this->success($rekap);
    }

    public function ajukanIzin(Request $request)
    {
        $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:500'
        ]);

        $user = $request->user();

        // Check if mahasiswa is enrolled in the class
        if (!$user->kelasMahasiswa()->where('kelas_id', $request->kelas_id)->exists()) {
            return $this->error('Anda tidak terdaftar di kelas ini', 403);
        }

        $absensi = Absensi::create([
            'mahasiswa_id' => $user->id,
            'kelas_id' => $request->kelas_id,
            'tanggal' => $request->tanggal,
            'status' => 'izin',
            'keterangan' => $request->keterangan
        ]);

        return $this->success($absensi, 'Izin berhasil diajukan');
    }
    public function gantiPassword(Request $request)
    {
        $request->validate([
            'password_lama' => 'required|string',
            'password_baru' => 'required|string|min:8|confirmed',
            'password_baru_confirmation' => 'required|string|min:8'
        ]);

        $user = $request->user();
        if (!Hash::check($request->password_lama, $user->password)) {
            return $this->error('Password lama tidak sesuai', 400);
        }
        if ($request->password_baru !== $request->password_baru_confirmation) {
            return $this->error('Konfirmasi password baru tidak sesuai', 400);
        }else{
        $user->password = Hash::make($request->password_baru);
        $user->save();
        }
        return $this->success(null, 'Password berhasil diubah');
    }
}