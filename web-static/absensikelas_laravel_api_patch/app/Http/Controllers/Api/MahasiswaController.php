<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class MahasiswaController extends Controller
{
    public function index()
    {
        $data = Mahasiswa::with('kelas')->orderBy('nim')->get();
        return response()->json(['status' => 'success', 'message' => 'Daftar mahasiswa', 'data' => $data]);
    }

    public function show(string $nim)
    {
        $m = Mahasiswa::with('kelas')->where('nim', $nim)->first();
        if (!$m) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan', 'errors' => ['nim' => 'NIM tidak terdaftar']], 404);
        }
        return response()->json(['status' => 'success', 'message' => 'Data ditemukan', 'data' => $m]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nim' => ['required','regex:/^[0-9]{8,12}$/'],
            'nama' => ['required','string','max:120'],
            'kelas' => ['required','string','max:30'],
            'angkatan' => ['required','regex:/^[0-9]{4}$/'],
        ]);

        $kelas = Kelas::firstOrCreate(['nama_kelas' => $validated['kelas']]);

        try {
            $m = Mahasiswa::create([
                'nim' => $validated['nim'],
                'nama' => $validated['nama'],
                'angkatan' => $validated['angkatan'],
                'kelas_id' => $kelas->id,
            ]);
        } catch (QueryException $e) {
            return response()->json(['status' => 'error', 'message' => 'NIM sudah terdaftar', 'errors' => ['nim' => 'NIM sudah ada']], 409);
        }

        return response()->json(['status' => 'success', 'message' => 'Mahasiswa berhasil ditambahkan', 'data' => $m->load('kelas')], 201);
    }

    public function update(Request $request, string $nim)
    {
        $validated = $request->validate([
            'nama' => ['required','string','max:120'],
            'kelas' => ['required','string','max:30'],
            'angkatan' => ['required','regex:/^[0-9]{4}$/'],
        ]);

        $m = Mahasiswa::where('nim', $nim)->first();
        if (!$m) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan', 'errors' => ['nim' => 'NIM tidak terdaftar']], 404);
        }

        $kelas = Kelas::firstOrCreate(['nama_kelas' => $validated['kelas']]);
        $m->update([
            'nama' => $validated['nama'],
            'angkatan' => $validated['angkatan'],
            'kelas_id' => $kelas->id,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Mahasiswa berhasil diperbarui', 'data' => $m->load('kelas')]);
    }

    public function destroy(string $nim)
    {
        $m = Mahasiswa::where('nim', $nim)->first();
        if (!$m) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan', 'errors' => ['nim' => 'NIM tidak terdaftar']], 404);
        }
        $m->delete();
        return response()->json(['status' => 'success', 'message' => 'Mahasiswa berhasil dihapus', 'data' => ['nim' => $nim]]);
    }
}
