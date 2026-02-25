package com.example.absensikelas

import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import kotlinx.coroutines.*

class AttendanceActivity : AppCompatActivity() {

    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.Main)
    private lateinit var db: AttendanceDbHelper
    private val adapter = AttendanceAdapter()

    private val reTanggal = Regex("^\\d{4}-\\d{2}-\\d{2}$")
    private val reNim = Regex("^\\d{8,12}$")
    private val allowedStatus = setOf("HADIR", "IZIN", "SAKIT", "ALPA")

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_attendance)

        db = AttendanceDbHelper(this)

        val etTanggal = findViewById<EditText>(R.id.etTanggal)
        val etNim = findViewById<EditText>(R.id.etNim)
        val etStatus = findViewById<EditText>(R.id.etStatus)
        val etKet = findViewById<EditText>(R.id.etKet)

        val tvStatus = findViewById<TextView>(R.id.tvStatus)
        val btnPost = findViewById<Button>(R.id.btnPost)
        val btnLoad = findViewById<Button>(R.id.btnLoadAttendanceCache)

        val rv = findViewById<RecyclerView>(R.id.rvAttendance)
        rv.layoutManager = LinearLayoutManager(this)
        rv.adapter = adapter

        fun resetAttendanceDb() {
            try { db.close() } catch (_: Exception) {}
            // hapus file db lama yang sering jadi penyebab crash
            deleteDatabase("absensikelas_attendance.db")
            db = AttendanceDbHelper(this)
        }

        suspend fun loadCache(prefix: String) {
            try {
                val cached = withContext(Dispatchers.IO) { db.getAll() }
                adapter.submit(cached)
                tvStatus.text = "$prefix (${cached.size})"
            } catch (e: Exception) {
                // auto-fix kalau schema lama bikin crash
                resetAttendanceDb()
                val cached = withContext(Dispatchers.IO) { db.getAll() }
                adapter.submit(cached)
                tvStatus.text = "Status: DB reset (schema baru) (${cached.size})"
            }
        }

        suspend fun syncFromApi(tanggal: String?) {
            val res = withContext(Dispatchers.IO) { ApiClient.api.getAttendance(tanggal) }
            if (res.status != "success") throw Exception(res.message ?: "unknown error")
            val list = res.data ?: emptyList()
            withContext(Dispatchers.IO) { db.replaceAll(list) }
            loadCache("Status: sync sukses")
        }

        // Load cache awal (aman, gak akan force close)
        scope.launch { loadCache("Status: cache absensi") }

        btnPost.setOnClickListener {
            scope.launch {
                val tanggal = etTanggal.text.toString().trim()
                val nim = etNim.text.toString().trim()
                val status = etStatus.text.toString().trim().uppercase()
                val ket = etKet.text.toString().trim().ifEmpty { null }

                if (!reTanggal.matches(tanggal)) {
                    tvStatus.text = "Status: tanggal harus format YYYY-MM-DD"
                    return@launch
                }
                if (!reNim.matches(nim)) {
                    tvStatus.text = "Status: NIM harus angka 8–12 digit"
                    return@launch
                }
                if (status !in allowedStatus) {
                    tvStatus.text = "Status: status harus HADIR/IZIN/SAKIT/ALPA"
                    return@launch
                }

                tvStatus.text = "Status: POST absensi..."
                try {
                    val body = AttendancePostDto(
                        tanggal = tanggal,
                        nim = nim,
                        status = status,
                        keterangan = ket
                    )

                    val postRes = withContext(Dispatchers.IO) {
                        ApiClient.api.createAttendance(body)
                    }
                    if (postRes.status != "success") throw Exception(postRes.message)

                    // setelah POST: GET lagi supaya dapat data lengkap (nim, tanggal, nama, jam_input)
                    syncFromApi(tanggal)

                } catch (e: Exception) {
                    tvStatus.text = "Status: POST gagal (${e.message})"
                }
            }
        }

        btnLoad.setOnClickListener {
            scope.launch { loadCache("Status: cache absensi tampil") }
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        scope.cancel()
        try { db.close() } catch (_: Exception) {}
    }
}