package com.example.absensikelas

import android.content.ContentValues
import android.content.Context
import android.database.sqlite.SQLiteDatabase
import android.database.sqlite.SQLiteOpenHelper

class StudentDbHelper(context: Context) :
    SQLiteOpenHelper(context, DB_NAME, null, DB_VERSION) {

    companion object {
        private const val DB_NAME = "absensikelas.db"
        private const val DB_VERSION = 1

        const val TABLE = "students"
        const val COL_ID = "id"
        const val COL_NIM = "nim"
        const val COL_NAMA = "nama"
        const val COL_ANGKATAN = "angkatan"
        const val COL_KELAS_ID = "kelas_id"
        const val COL_NAMA_KELAS = "nama_kelas"
    }

    override fun onCreate(db: SQLiteDatabase) {
        db.execSQL(
            """
            CREATE TABLE $TABLE (
              $COL_ID INTEGER PRIMARY KEY,
              $COL_NIM TEXT UNIQUE NOT NULL,
              $COL_NAMA TEXT NOT NULL,
              $COL_ANGKATAN TEXT NOT NULL,
              $COL_KELAS_ID INTEGER,
              $COL_NAMA_KELAS TEXT
            )
            """.trimIndent()
        )
    }

    override fun onUpgrade(db: SQLiteDatabase, oldVersion: Int, newVersion: Int) {
        db.execSQL("DROP TABLE IF EXISTS $TABLE")
        onCreate(db)
    }

    fun replaceAll(students: List<StudentDto>): Int {
        val db = writableDatabase
        db.beginTransaction()
        return try {
            db.delete(TABLE, null, null)

            for (s in students) {
                val cv = ContentValues().apply {
                    put(COL_ID, s.id)
                    put(COL_NIM, s.nim)
                    put(COL_NAMA, s.nama)
                    put(COL_ANGKATAN, s.angkatan)
                    put(COL_KELAS_ID, s.kelasId)
                    put(COL_NAMA_KELAS, s.namaKelas)
                }
                db.insertWithOnConflict(TABLE, null, cv, SQLiteDatabase.CONFLICT_REPLACE)
            }

            db.setTransactionSuccessful()
            students.size
        } finally {
            db.endTransaction()
        }
    }

    fun getAll(): List<StudentDto> {
        val db = readableDatabase
        val list = mutableListOf<StudentDto>()

        val cursor = db.rawQuery(
            "SELECT $COL_ID, $COL_NIM, $COL_NAMA, $COL_ANGKATAN, $COL_KELAS_ID, $COL_NAMA_KELAS FROM $TABLE ORDER BY $COL_ID ASC",
            null
        )
        cursor.use { c ->
            val idxId = c.getColumnIndexOrThrow(COL_ID)
            val idxNim = c.getColumnIndexOrThrow(COL_NIM)
            val idxNama = c.getColumnIndexOrThrow(COL_NAMA)
            val idxAng = c.getColumnIndexOrThrow(COL_ANGKATAN)
            val idxKelasId = c.getColumnIndexOrThrow(COL_KELAS_ID)
            val idxNamaKelas = c.getColumnIndexOrThrow(COL_NAMA_KELAS)

            while (c.moveToNext()) {
                list.add(
                    StudentDto(
                        id = c.getLong(idxId),
                        nim = c.getString(idxNim),
                        nama = c.getString(idxNama),
                        angkatan = c.getString(idxAng),
                        kelasId = if (c.isNull(idxKelasId)) null else c.getInt(idxKelasId),
                        namaKelas = if (c.isNull(idxNamaKelas)) null else c.getString(idxNamaKelas)
                    )
                )
            }
        }
        return list
    }

    fun count(): Int {
        val db = readableDatabase
        val cursor = db.rawQuery("SELECT COUNT(*) FROM $TABLE", null)
        cursor.use { c ->
            return if (c.moveToFirst()) c.getInt(0) else 0
        }
    }
}