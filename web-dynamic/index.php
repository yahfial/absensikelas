<?php
// Minimal landing page (optional) to help testing quickly
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PHP + JSON Demo — AbsensiKelas</title>
  <style>
    body{font-family:system-ui,Segoe UI,Arial,sans-serif;margin:24px;line-height:1.4}
    code,pre{background:#f5f5f5;padding:2px 6px;border-radius:6px}
    pre{padding:12px;overflow:auto}
    .box{border:1px solid #ddd;border-radius:12px;padding:14px;margin:12px 0}
  </style>
</head>
<body>
  <h1>Demo PHP + JSON (Tanpa Framework)</h1>
  <p>Ini membantu kamu mengetes endpoint dengan cepat memakai browser/Postman.</p>

  <div class="box">
    <h2>Endpoint Mahasiswa</h2>
    <ul>
      <li><code>GET /api/students.php</code></li>
      <li><code>GET /api/students.php?nim=23123456</code></li>
      <li><code>POST /api/students.php</code> (nim,nama,kelas,angkatan)</li>
      <li><code>PUT /api/students.php</code> (JSON: nim,nama,kelas,angkatan)</li>
      <li><code>DELETE /api/students.php</code> (JSON: nim)</li>
    </ul>
  </div>

  <div class="box">
    <h2>Endpoint Absensi</h2>
    <ul>
      <li><code>GET /api/attendance.php</code></li>
      <li><code>GET /api/attendance.php?tanggal=2026-02-23</code></li>
      <li><code>POST /api/attendance.php</code> (tanggal,nim,nama,kelas,status)</li>
      <li><code>DELETE /api/attendance.php</code> (JSON: id)</li>
    </ul>
  </div>

  <div class="box">
    <h2>Jalankan Server</h2>
    <pre>cd web-dynamic
php -S localhost:8000</pre>
    <p>Lalu buka: <code>http://localhost:8000</code></p>
  </div>
</body>
</html>
