package com.example.absensikelas

import com.google.gson.annotations.SerializedName

data class StudentDto(
    val id: Long,
    val nim: String,
    val nama: String,
    val angkatan: String,

    @SerializedName(value = "kelas_id", alternate = ["kelasId", "kelas_id_kelas"])
    val kelasId: Int? = null,

    @SerializedName(value = "nama_kelas", alternate = ["namaKelas", "kelas", "nama_kelas_kelas", "kelas_nama"])
    val namaKelas: String? = null
)