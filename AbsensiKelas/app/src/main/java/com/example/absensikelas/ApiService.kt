package com.example.absensikelas

import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    @GET("api/students.php")
    suspend fun getStudents(): ApiResponse<List<StudentDto>>

    // pakai attendance.php (tanggal + nim)
    @GET("api/attendance.php")
    suspend fun getAttendance(@Query("tanggal") tanggal: String? = null): ApiResponse<List<AttendanceDto>>

    @POST("api/attendance.php")
    suspend fun createAttendance(@Body body: AttendancePostDto): ApiResponse<Any>
}