package com.example.absensikelas

data class AttendancePostDto(
    val tanggal: String,
    val nim: String,
    val status: String,
    val keterangan: String? = null
)