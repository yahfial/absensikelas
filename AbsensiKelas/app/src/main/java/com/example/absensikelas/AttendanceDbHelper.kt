package com.example.absensikelas

import android.content.ContentValues
import android.content.Context
import android.database.sqlite.SQLiteDatabase
import android.database.sqlite.SQLiteOpenHelper

class AttendanceDbHelper(context: Context) :
    SQLiteOpenHelper(context, DB_NAME, null, DB_VERSION) {

    companion object {
        private const val DB_NAME = "absensikelas_attendance.db"
        private const val DB_VERSION = 3 // <-- penting: naikkan versi supaya onUpgrade jalan

        const val TABLE = "attendance"
        const val COL_ID = "id"
        const val COL_TANGGAL = "tanggal"
        const val COL_NIM = "nim"
        const val COL_NAMA = "nama_mahasiswa"
        const val COL_STATUS = "status"
        const val COL_JAM = "jam_input"
        const val COL_KET = "keterangan"
    }

    override fun onCreate(db: SQLiteDatabase) {
        db.execSQL(
            """
            CREATE TABLE IF NOT EXISTS $TABLE (
              $COL_ID INTEGER PRIMARY KEY,
              $COL_TANGGAL TEXT NOT NULL,
              $COL_NIM TEXT NOT NULL,
              $COL_NAMA TEXT,
              $COL_STATUS TEXT NOT NULL,
              $COL_JAM TEXT,
              $COL_KET TEXT
            )
            """.trimIndent()
        )
        db.execSQL("CREATE INDEX IF NOT EXISTS idx_att_tanggal ON $TABLE($COL_TANGGAL)")
        db.execSQL("CREATE INDEX IF NOT EXISTS idx_att_nim ON $TABLE($COL_NIM)")
    }

    override fun onUpgrade(db: SQLiteDatabase, oldVersion: Int, newVersion: Int) {
        db.execSQL("DROP TABLE IF EXISTS $TABLE")
        onCreate(db)
    }

    fun replaceAll(list: List<AttendanceDto>): Int {
        val db = writableDatabase
        db.beginTransaction()
        return try {
            db.delete(TABLE, null, null)

            list.forEach { a ->
                val cv = ContentValues().apply {
                    put(COL_ID, a.id)
                    put(COL_TANGGAL, a.tanggal)
                    put(COL_NIM, a.nim)
                    put(COL_NAMA, a.namaMahasiswa)
                    put(COL_STATUS, a.status)
                    put(COL_JAM, a.jamInput)
                    put(COL_KET, a.keterangan)
                }
                db.insertWithOnConflict(TABLE, null, cv, SQLiteDatabase.CONFLICT_REPLACE)
            }

            db.setTransactionSuccessful()
            list.size
        } finally {
            db.endTransaction()
        }
    }

    fun getAll(): List<AttendanceDto> {
        val db = readableDatabase
        val out = mutableListOf<AttendanceDto>()

        val c = db.rawQuery(
            """
            SELECT $COL_ID, $COL_TANGGAL, $COL_NIM, $COL_NAMA, $COL_STATUS, $COL_JAM, $COL_KET
            FROM $TABLE
            ORDER BY $COL_ID DESC
            """.trimIndent(),
            null
        )

        c.use {
            val id = it.getColumnIndexOrThrow(COL_ID)
            val t = it.getColumnIndexOrThrow(COL_TANGGAL)
            val n = it.getColumnIndexOrThrow(COL_NIM)
            val nm = it.getColumnIndexOrThrow(COL_NAMA)
            val s = it.getColumnIndexOrThrow(COL_STATUS)
            val j = it.getColumnIndexOrThrow(COL_JAM)
            val k = it.getColumnIndexOrThrow(COL_KET)

            while (it.moveToNext()) {
                out.add(
                    AttendanceDto(
                        id = it.getLong(id),
                        tanggal = it.getString(t),
                        nim = it.getString(n),
                        namaMahasiswa = if (it.isNull(nm)) null else it.getString(nm),
                        status = it.getString(s),
                        jamInput = if (it.isNull(j)) null else it.getString(j),
                        keterangan = if (it.isNull(k)) null else it.getString(k),
                    )
                )
            }
        }
        return out
    }
}