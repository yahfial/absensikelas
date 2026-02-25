package com.example.absensikelas

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity

class LoginActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Kalau sudah login, langsung masuk MainActivity
        if (SessionManager.isLoggedIn(this)) {
            startActivity(Intent(this, MainActivity::class.java))
            finish()
            return
        }

        setContentView(R.layout.activity_login)

        val etUser = findViewById<EditText>(R.id.etUsername)
        val etPass = findViewById<EditText>(R.id.etPassword)
        val btn = findViewById<Button>(R.id.btnLogin)
        val tvStatus = findViewById<TextView>(R.id.tvStatus)

        btn.setOnClickListener {
            val u = etUser.text.toString().trim()
            val p = etPass.text.toString().trim()

            if (u.isEmpty() || p.isEmpty()) {
                tvStatus.text = "Username dan password wajib diisi"
                return@setOnClickListener
            }

            // ✅ Login demo (tanpa ubah API/SQLite)
            val role = when {
                u == "admin" && p == "admin123" -> "ADMIN"
                u == "dosen" && p == "dosen123" -> "DOSEN"
                else -> null
            }

            if (role == null) {
                tvStatus.text = "Login gagal: username/password salah"
                return@setOnClickListener
            }

            SessionManager.login(this, u, role)
            tvStatus.text = "Login sukses sebagai $role"

            startActivity(Intent(this, MainActivity::class.java))
            finish()
        }
    }
}