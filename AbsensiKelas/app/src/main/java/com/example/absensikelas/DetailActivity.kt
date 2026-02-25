package com.example.absensikelas

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity

class DetailActivity : AppCompatActivity() {

    companion object {
        private const val EXTRA_NIM = "extra_nim"
        private const val EXTRA_NAMA = "extra_nama"
        private const val EXTRA_ANGKATAN = "extra_angkatan"
        private const val EXTRA_NAMA_KELAS = "extra_nama_kelas"
        private const val EXTRA_KELAS_ID = "extra_kelas_id"

        fun start(ctx: Context, s: StudentDto) {
            val i = Intent(ctx, DetailActivity::class.java).apply {
                putExtra(EXTRA_NIM, s.nim)
                putExtra(EXTRA_NAMA, s.nama)
                putExtra(EXTRA_ANGKATAN, s.angkatan)
                putExtra(EXTRA_NAMA_KELAS, s.namaKelas ?: "-")
                putExtra(EXTRA_KELAS_ID, s.kelasId ?: -1)
            }
            ctx.startActivity(i)
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_detail)

        val nim = intent.getStringExtra(EXTRA_NIM) ?: "-"
        val nama = intent.getStringExtra(EXTRA_NAMA) ?: "-"
        val angkatan = intent.getStringExtra(EXTRA_ANGKATAN) ?: "-"
        val namaKelas = intent.getStringExtra("extra_nama_kelas") ?: "-"
        val kelasId = intent.getIntExtra("extra_kelas_id", -1)

        findViewById<TextView>(R.id.tvNim).text = nim
        findViewById<TextView>(R.id.tvNama).text = nama
        findViewById<TextView>(R.id.tvAngkatan).text = angkatan
        findViewById<TextView>(R.id.tvKelas).text = namaKelas
        findViewById<TextView>(R.id.tvKelasId).text = kelasId.toString()
    }
}