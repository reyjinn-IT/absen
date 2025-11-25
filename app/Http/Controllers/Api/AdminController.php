<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Kelas;
use App\Models\Absensi;
use App\Models\Matakuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // User Management
    public function getUsers(Request $request)
    {
        $role = $request->query('role');
        $query = User::query();

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->get();
        return $this->success($users);
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,dosen,mahasiswa',
            'nim' => 'required_if:role,mahasiswa|unique:users',
            'nidn' => 'required_if:role,dosen|unique:users'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'nim' => $request->nim,
            'nidn' => $request->nidn,
            'phone' => $request->phone
        ]);

        return $this->success($user, 'User created successfully', 201);
    }

    // Matakuliah Management
    public function getMatakuliah()
    {
        $matakuliah = Matakuliah::with('dosen')->get();
        return $this->success($matakuliah);
    }

    public function createMatakuliah(Request $request)
    {
        $request->validate([
            'kode_mk' => 'required|unique:matakuliah',
            'nama_mk' => 'required|string|max:255',
            'sks' => 'required|integer',
            'dosen_id' => 'required|exists:users,id'
        ]);

        $matakuliah = Matakuliah::create($request->all());
        return $this->success($matakuliah, 'Mata kuliah created successfully', 201);
    }

    // Kelas Management
    public function getKelas()
    {
        $kelas = Kelas::with(['matakuliah', 'dosen', 'mahasiswa'])->get();
        return $this->success($kelas);
    }

    public function createKelas(Request $request)
    {
        $request->validate([
            'kode_kelas' => 'required|unique:kelas',
            'nama_kelas' => 'required|string|max:255',
            'matakuliah_id' => 'required|exists:matakuliah,id',
            'dosen_id' => 'required|exists:users,id'
        ]);

        $kelas = Kelas::create($request->all());
        return $this->success($kelas, 'Kelas created successfully', 201);
    }

    public function addMahasiswaToKelas(Request $request, $kelasId)
    {
        $request->validate([
            'mahasiswa_ids' => 'required|array',
            'mahasiswa_ids.*' => 'exists:users,id'
        ]);

        $kelas = Kelas::findOrFail($kelasId);
        $kelas->mahasiswa()->syncWithoutDetaching($request->mahasiswa_ids);

        return $this->success(null, 'Mahasiswa added to kelas successfully');
    }

    // Dashboard Statistics
    public function getDashboardStats()
    {
        $stats = [
            'total_mahasiswa' => User::mahasiswa()->count(),
            'total_dosen' => User::dosen()->count(),
            'total_matakuliah' => Matakuliah::count(),
            'total_kelas' => Kelas::count(),
            'absensi_hari_ini' => Absensi::whereDate('tanggal', today())->count(),
            'recent_absensi' => Absensi::with(['mahasiswa', 'kelas'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
        ];

        return $this->success($stats);
    }

    // Laporan Absensi
    public function getLaporanAbsensi(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'kelas_id' => 'nullable|exists:kelas,id'
        ]);

        $query = Absensi::with(['mahasiswa', 'kelas.matakuliah'])
            ->whereBetween('tanggal', [$request->start_date, $request->end_date]);

        if ($request->kelas_id) {
            $query->where('kelas_id', $request->kelas_id);
        }

        $absensi = $query->get();

        return $this->success($absensi);
    }
}