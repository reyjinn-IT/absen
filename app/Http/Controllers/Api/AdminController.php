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
    // Validasi dasar
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:8',
        'role' => 'required|in:admin,dosen,mahasiswa',
        'phone' => 'nullable|string|max:20',
        'nim' => 'nullable|string|max:20',
        'nidn' => 'nullable|string|max:20',
    ]);

    // Validasi conditional
    if ($request->role == 'mahasiswa' && empty($request->nim)) {
        return response()->json([
            'success' => false,
            'message' => 'NIM wajib diisi untuk mahasiswa'
        ], 422);
    }

    if ($request->role == 'dosen' && empty($request->nidn)) {
        return response()->json([
            'success' => false,
            'message' => 'NIDN wajib diisi untuk dosen'
        ], 422);
    }

    // Buat user
    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => bcrypt($validated['password']),
        'role' => $validated['role'],
        'phone' => $validated['phone'] ?? null,
        'nim' => $validated['nim'] ?? null,
        'nidn' => $validated['nidn'] ?? null,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'User created successfully',
        'data' => $user
    ], 201);
}

// AdminController.php - TAMBAH METHOD INI

public function updateUser(Request $request, $id)
{
    $user = User::findOrFail($id);

    // Buat rules dasar
    $rules = [
        'name' => 'sometimes|required|string|max:255',
        'email' => 'sometimes|required|email|unique:users,email,' . $id,
        'password' => 'nullable|string|min:8',
        'role' => 'sometimes|required|in:admin,dosen,mahasiswa',
        'phone' => 'nullable|string|max:20',
    ];

    // Tambahkan rules conditional
    if ($request->has('role') && $request->role == 'mahasiswa') {
        $rules['nim'] = 'required|string|max:20|unique:users,nim,' . $id;
    }

    if ($request->has('role') && $request->role == 'dosen') {
        $rules['nidn'] = 'required|string|max:20|unique:users,nidn,' . $id;
    }

    // Validasi
    $validated = $request->validate($rules);

    // Update data
    $user->name = $validated['name'] ?? $user->name;
    $user->email = $validated['email'] ?? $user->email;
    $user->role = $validated['role'] ?? $user->role;

    if (isset($validated['password'])) {
        $user->password = bcrypt($validated['password']);
    }

    $user->phone = $validated['phone'] ?? $user->phone;

    // Update nim/nidn berdasarkan role
    if (isset($validated['role'])) {
        if ($validated['role'] == 'mahasiswa') {
            $user->nim = $validated['nim'] ?? $user->nim;
            $user->nidn = null; // Reset nidn jika berubah role
        } elseif ($validated['role'] == 'dosen') {
            $user->nidn = $validated['nidn'] ?? $user->nidn;
            $user->nim = null; // Reset nim jika berubah role
        } else {
            // Admin - reset nim dan nidn
            $user->nim = null;
            $user->nidn = null;
        }
    }

    $user->save();

    return $this->success($user, 'User updated successfully');
}

// Juga tambahkan method delete
public function deleteUser($id)
{
    try {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete user'
        ], 500);
    }
}


    // Matakuliah Management - GET ALL
    public function getMatakuliah()
    {
        try {
            $matakuliah = Matakuliah::with(['dosen'])->get();
            return response()->json([
                'success' => true,
                'data' => $matakuliah
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data mata kuliah'
            ], 500);
        }
    }

    // Matakuliah Management - GET SINGLE
    public function getMatakuliahDetail($id)
    {
        try {
            $matakuliah = Matakuliah::with(['dosen'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $matakuliah
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mata kuliah tidak ditemukan'
            ], 404);
        }
    }

    // Matakuliah Management - CREATE
    public function createMatakuliah(Request $request)
    {
        try {
            $validated = $request->validate([
                'kode_mk' => 'required|string|max:20|unique:matakuliah',
                'nama_mk' => 'required|string|max:255',
                'sks' => 'required|integer|min:1|max:6',
                // 'semester' => 'required|integer|min:1|max:8',
                'dosen_id' => 'required|exists:users,id',
                // 'deskripsi' => 'nullable|string',
                // 'jenis' => 'nullable|in:teori,praktikum',
            ]);

            $matakuliah = Matakuliah::create($validated);

            // Load relasi dosen
            $matakuliah->load('dosen');

            return response()->json([
                'success' => true,
                'message' => 'Mata kuliah berhasil dibuat',
                'data' => $matakuliah
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat mata kuliah: ' . $e->getMessage()
            ], 500);
        }
    }

    // Matakuliah Management - UPDATE
    public function updateMatakuliah(Request $request, $id)
    {
        try {
            $matakuliah = Matakuliah::findOrFail($id);

            $validated = $request->validate([
                'kode_mk' => 'sometimes|required|string|max:20|unique:matakuliah,kode_mk,' . $id,
                'nama_mk' => 'sometimes|required|string|max:255',
                'sks' => 'sometimes|required|integer|min:1|max:6',
                'dosen_id' => 'sometimes|required|exists:users,id',
            ]);

            $matakuliah->update($validated);

            // Refresh data dengan relasi
            $matakuliah->load('dosen');

            return response()->json([
                'success' => true,
                'message' => 'Mata kuliah berhasil diperbarui',
                'data' => $matakuliah
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui mata kuliah: ' . $e->getMessage()
            ], 500);
        }
    }

    // Matakuliah Management - DELETE
    public function deleteMatakuliah($id)
    {
        try {
            $matakuliah = Matakuliah::findOrFail($id);

            // Cek apakah mata kuliah digunakan di kelas
            $usedInKelas = $matakuliah->kelas()->exists();

            if ($usedInKelas) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mata kuliah tidak dapat dihapus karena masih digunakan di kelas'
                ], 400);
            }

            $matakuliah->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mata kuliah berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus mata kuliah: ' . $e->getMessage()
            ], 500);
        }
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

    // App\Http\Controllers\Api\AdminController.php
    public function updateKelas(Request $request, $id)
    {
        try {
            $kelas = Kelas::findOrFail($id);

            $validated = $request->validate([
                'kode_kelas' => 'sometimes|required|string|unique:kelas,kode_kelas,' . $id,
                'nama_kelas' => 'sometimes|required|string|max:255',
                'matakuliah_id' => 'sometimes|required|exists:matakuliah,id',
                'dosen_id' => 'sometimes|required|exists:users,id', // ← PERBAIKI: users bukan dosen
            ]);

            // Update kelas
            $kelas->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Kelas berhasil diperbarui',
                'data' => $kelas
            ]);
        } catch (\Exception $e) {
            // Tampilkan error detail untuk debugging
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui kelas: ' . $e->getMessage(),
                'error' => $e->getTraceAsString() // Hapus ini di production
            ], 500);
        }
    }

    public function deleteKelas($id)
    {
        try {
            $kelas = Kelas::findOrFail($id);
            $kelas->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kelas deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete kelas'
            ], 500);
        }
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
