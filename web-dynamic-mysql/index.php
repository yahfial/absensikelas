<?php ?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>AbsensiKelas — PHP + MySQL (PDO)</title>
  <style>
    body{font-family:system-ui,Segoe UI,Arial,sans-serif;margin:24px;line-height:1.4}
    code,pre{background:#f5f5f5;padding:2px 6px;border-radius:6px}
    pre{padding:12px;overflow:auto}
    .box{border:1px solid #ddd;border-radius:12px;padding:14px;margin:12px 0}
  </style>
</head>
<body>
  <h1>Demo PHP + MySQL (PDO)</h1>
  <div class="box">
    <h2>1) Cek koneksi DB</h2>
    <p>Buka: <code>/api/ping.php</code></p>
  </div>

  <div class="box">
    <h2>2) Mahasiswa</h2>
    <ul>
      <li><code>GET /api/students.php</code></li>
      <li><code>GET /api/students.php?nim=23123456</code></li>
      <li><code>POST /api/students.php</code> (nim,nama,kelas,angkatan)</li>
      <li><code>PUT /api/students.php</code> (JSON: nim,nama,kelas,angkatan)</li>
      <li><code>DELETE /api/students.php</code> (JSON: nim)</li>
    </ul>
  </div>

  <div class="box">
    <h2>3) Absensi</h2>
    <p>POST absensi akan otomatis membuat pertemuan jika belum ada (demo).</p>
    <ul>
      <li><code>GET /api/attendance.php</code></li>
      <li><code>GET /api/attendance.php?tanggal=YYYY-MM-DD</code></li>
      <li><code>POST /api/attendance.php</code> (tanggal,nim,status[,kode_mk,dosen_email])</li>
      <li><code>DELETE /api/attendance.php</code> (JSON: id)</li>
    </ul>
  </div>

  <div class="box">
    <h2>Jalankan server</h2>
    <pre>php -S localhost:8000</pre>
  </div>
</body>
</html>
