AbsensiKelas — Laravel REST API Patch

Ini hanya PATCH file untuk kamu COPY ke project Laravel kamu.

Langkah:
1) composer create-project laravel/laravel absensikelas-api
2) Set .env DB:
   DB_DATABASE=absensikelas
   DB_USERNAME=...
   DB_PASSWORD=...
3) Copy dari zip:
   - app/Models/* -> absensikelas-api/app/Models/
   - app/Http/Controllers/Api/* -> absensikelas-api/app/Http/Controllers/Api/
   - routes/api.php -> merge/overwrite absensikelas-api/routes/api.php
4) Jalankan:
   php artisan serve --port=8001

Tes:
GET  http://localhost:8001/api/v1/mahasiswa
POST http://localhost:8001/api/v1/mahasiswa
PUT  http://localhost:8001/api/v1/mahasiswa/{nim}
DEL  http://localhost:8001/api/v1/mahasiswa/{nim}

Pertemuan:
POST http://localhost:8001/api/v1/pertemuan (tanggal, kelas_id, mata_kuliah_id, dosen_id)

Absensi:
POST http://localhost:8001/api/v1/absensi (pertemuan_id, nim, status)
GET  http://localhost:8001/api/v1/absensi?tanggal=YYYY-MM-DD
