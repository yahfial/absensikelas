package com.example.absensikelas

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import kotlinx.coroutines.*

class MainActivity : AppCompatActivity() {

    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.Main)

    private val adapter = StudentAdapter { s ->
        DetailActivity.start(this, s)
    }

    private lateinit var db: StudentDbHelper

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // ✅ cek login dulu
        if (!SessionManager.isLoggedIn(this)) {
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
            return
        }

        setContentView(R.layout.activity_main)

        db = StudentDbHelper(this)

        val rv = findViewById<RecyclerView>(R.id.rvStudents)
        val tv = findViewById<TextView>(R.id.tvStatus)
        val btnSync = findViewById<Button>(R.id.btnSync)
        val btnLoad = findViewById<Button>(R.id.btnLoadCache)
        val btnGo = findViewById<Button>(R.id.btnGoAttendance)
        val btnLogout = findViewById<Button>(R.id.btnLogout)

        btnGo.setOnClickListener {
            startActivity(Intent(this, AttendanceActivity::class.java))
        }

        rv.layoutManager = LinearLayoutManager(this)
        rv.adapter = adapter

        tv.text = "Status: cache ${db.count()} data"

        btnSync.setOnClickListener {
            scope.launch {
                tv.text = "Status: sync API..."
                try {
                    val res = withContext(Dispatchers.IO) { ApiClient.api.getStudents() }
                    val data = res.data ?: emptyList()

                    val savedCount = withContext(Dispatchers.IO) { db.replaceAll(data) }

                    adapter.submit(data)
                    tv.text = "Status: sync sukses ($savedCount data tersimpan)"
                } catch (e: Exception) {
                    tv.text = "Status: sync gagal (${e.message})"
                }
            }
        }

        btnLogout.setOnClickListener {
            SessionManager.logout(this)
            startActivity(Intent(this, LoginActivity::class.java))
            finishAffinity() // penting: biar tidak bisa back ke main
        } // ✅ ini penutup yang hilang

        btnLoad.setOnClickListener {
            scope.launch {
                tv.text = "Status: load cache..."
                val cached = withContext(Dispatchers.IO) { db.getAll() }
                adapter.submit(cached)
                tv.text = "Status: cache tampil (${cached.size} data)"
            }
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        scope.cancel()
        db.close()
    }
}