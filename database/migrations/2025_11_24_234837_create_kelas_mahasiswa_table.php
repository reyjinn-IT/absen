<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kelas_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->foreignId('mahasiswa_id')->constrained('users');
            $table->timestamps();
            
            $table->unique(['kelas_id', 'mahasiswa_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('kelas_mahasiswa');
    }
};