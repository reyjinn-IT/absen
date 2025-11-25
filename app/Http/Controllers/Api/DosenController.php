<?php

namespace App\Http\Controllers\Api;

use App\Models\Kelas;
use App\Models\Absensi;
use App\Models\Matakuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DosenController extends Controller
{
    public function getDashboard(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total_kelas' => $user->kelasDosen()->count(),
            'total_matakuliah' => $user->matakuliahDosen()->count(),
            'kelas_aktif' => $user->kelasDosen()->withCount('mahasiswa')->get()
        ];

        return $this->success($stats);
    }

    public function getKelasDosen(Request $request)
    {
        $user = $request->user();
        $kelas = $user->kelasDosen()->with(['matakuliah', 'mahasiswa'])->get();

        return $this->success($kelas);
    }

    public function getAbsensiKelas(Request $request, $kelasId)
    {
        $user = $request->user();
        $kelas = Kelas::where('dosen_id', $user->id)->findOrFail($kelasId);

        $absensi = Absensi::with('mahasiswa')
            ->where('kelas_id', $kelasId)
            ->whereDate('tanggal', $request->get('tanggal', today()))
            ->get();

        return $this->success([
            'kelas' => $kelas,
            'absensi' => $absensi
        ]);
    }

    public function inputAbsensiManual(Request $request)
    {
        $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'mahasiswa_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:hadir,izin,sakit,alpha',
            'keterangan' => 'nullable|string'
        ]);

        // Check if dosen owns the class
        $kelas = Kelas::where('dosen_id', $request->user()->id)
            ->findOrFail($request->kelas_id);

        $absensi = Absensi::updateOrCreate(
            [
                'mahasiswa_id' => $request->mahasiswa_id,
                'kelas_id' => $request->kelas_id,
                'tanggal' => $request->tanggal
            ],
            [
                'status' => $request->status,
                'keterangan' => $request->keterangan,
                'waktu_masuk' => now()->format('H:i:s')
            ]
        );

        return $this->success($absensi, 'Absensi berhasil dicatat');
    }

    public function getRekapAbsensi(Request $request, $kelasId)
    {
        $user = $request->user();
        $kelas = Kelas::where('dosen_id', $user->id)
            ->with('matakuliah')
            ->findOrFail($kelasId);

        $rekap = DB::table('absensi')
            ->where('kelas_id', $kelasId)
            ->select('mahasiswa_id')
            ->selectRaw('COUNT(*) as total_pertemuan')
            ->selectRaw('SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as hadir')
            ->selectRaw('SUM(CASE WHEN status = "izin" THEN 1 ELSE 0 END) as izin')
            ->selectRaw('SUM(CASE WHEN status = "sakit" THEN 1 ELSE 0 END) as sakit')
            ->selectRaw('SUM(CASE WHEN status = "alpha" THEN 1 ELSE 0 END) as alpha')
            ->groupBy('mahasiswa_id')
            ->get();

        return $this->success([
            'kelas' => $kelas,
            'rekap_absensi' => $rekap
        ]);
    }
}