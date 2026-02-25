<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    protected $table = 'mahasiswa';
    protected $fillable = ['nim', 'nama', 'angkatan', 'kelas_id'];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }
}
