<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pertemuan extends Model
{
    protected $table = 'pertemuan';
    protected $fillable = ['tanggal', 'topik', 'kelas_id', 'mata_kuliah_id', 'dosen_id'];
    protected $casts = ['tanggal' => 'date'];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }
}
