<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pertemuan;
use Illuminate\Http\Request;

class PertemuanController extends Controller
{
    public function index()
    {
        $data = Pertemuan::with(['kelas','mataKuliah'])->orderByDesc('tanggal')->get();
        return response()->json(['status'=>'success','message'=>'Daftar pertemuan','data'=>$data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => ['required','date'],
            'topik' => ['nullable','string','max:200'],
            'kelas_id' => ['required','integer'],
            'mata_kuliah_id' => ['required','integer'],
            'dosen_id' => ['required','integer'],
        ]);

        $p = Pertemuan::create($validated);
        return response()->json(['status'=>'success','message'=>'Pertemuan berhasil ditambahkan','data'=>$p], 201);
    }
}
