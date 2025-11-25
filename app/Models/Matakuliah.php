<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matakuliah extends Model
{
    use HasFactory;
    protected $table = 'matakuliah'; 
    protected $fillable = [
        'kode_mk',
        'nama_mk',
        'sks',
        'dosen_id'
    ];

    public function dosen()
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function kelas()
    {
        return $this->hasMany(Kelas::class);
    }
}