<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Mahasiswa;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->query('tanggal');

        $q = Absensi::with(['mahasiswa.kelas','pertemuan.kelas','pertemuan.mataKuliah'])
            ->orderByDesc('id');

        if ($tanggal) {
            $q->whereHas('pertemuan', fn($x) => $x->where('tanggal', $tanggal));
        }

        $data = $q->get();
        return response()->json(['status'=>'success','message'=>'Daftar absensi','data'=>$data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pertemuan_id' => ['required','integer'],
            'nim' => ['required','regex:/^[0-9]{8,12}$/'],
            'status' => ['required','in:HADIR,IZIN,SAKIT,ALPA'],
            'keterangan' => ['nullable','string','max:200'],
        ]);

        $m = Mahasiswa::where('nim', $validated['nim'])->first();
        if (!$m) {
            return response()->json(['status'=>'error','message'=>'Mahasiswa tidak ditemukan','errors'=>['nim'=>'NIM tidak terdaftar']], 404);
        }

        try {
            $a = Absensi::create([
                'pertemuan_id' => $validated['pertemuan_id'],
                'mahasiswa_id' => $m->id,
                'status' => $validated['status'],
                'jam_input' => now()->format('H:i:s'),
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        } catch (QueryException $e) {
            return response()->json(['status'=>'error','message'=>'Absensi ganda','errors'=>['unique'=>'Mahasiswa sudah absen pada pertemuan ini']], 409);
        }

        return response()->json(['status'=>'success','message'=>'Absensi berhasil ditambahkan','data'=>$a->load(['mahasiswa','pertemuan'])], 201);
    }

    public function destroy(int $id)
    {
        $a = Absensi::find($id);
        if (!$a) {
            return response()->json(['status'=>'error','message'=>'Data tidak ditemukan','errors'=>['id'=>'ID tidak terdaftar']], 404);
        }
        $a->delete();
        return response()->json(['status'=>'success','message'=>'Absensi berhasil dihapus','data'=>['id'=>$id]]);
    }
}
