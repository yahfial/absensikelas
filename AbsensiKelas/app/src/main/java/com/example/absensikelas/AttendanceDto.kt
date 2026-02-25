package com.example.absensikelas

import com.google.gson.annotations.SerializedName

data class AttendanceDto(
    val id: Long,
    val tanggal: String,
    val nim: String,

    @SerializedName("nama_mahasiswa")
    val namaMahasiswa: String? = null,

    val status: String,

    @SerializedName("jam_input")
    val jamInput: String? = null,

    // di API attendance.php tidak selalu ada, tapi boleh untuk tampilan/cache
    val keterangan: String? = null,

    @SerializedName("nama_kelas")
    val namaKelas: String? = null,

    @SerializedName("kode_mk")
    val kodeMk: String? = null,

    val dosen: String? = null
)