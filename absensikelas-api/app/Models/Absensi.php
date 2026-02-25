<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $table = 'absensi';
    protected $fillable = ['pertemuan_id', 'mahasiswa_id', 'status', 'jam_input', 'keterangan'];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    public function pertemuan()
    {
        return $this->belongsTo(Pertemuan::class, 'pertemuan_id');
    }
}
