USE absensikelas;

START TRANSACTION;

-- =========================
-- 1) INSERT MASTER DATA
-- =========================

-- USERS (Admin/Dosen)
-- password_hash bebas (untuk demo). Kalau mau real: hash bcrypt dari app.
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin', 'admin@kampus.test', 'demo_hash_admin', 'ADMIN'),
('Dosen 1', 'dosen1@kampus.test', 'demo_hash_dosen1', 'DOSEN');

-- KELAS
INSERT INTO kelas (nama_kelas) VALUES
('TI-2A'),
('II RKS B');

-- MATA KULIAH
INSERT INTO mata_kuliah (kode_mk, nama_mk, sks) VALUES
('PKS', 'Pemrograman Keamanan Sistem', 3),
('PPL', 'Pengembangan Perangkat Lunak', 3);

-- MAHASISWA
-- Pastikan kelas_id sesuai data kelas yang barusan diinsert (1 = TI-2A, 2 = II RKS B)
INSERT INTO mahasiswa (nim, nama, angkatan, kelas_id) VALUES
('23123456', 'Andi', '2024', 1),
('23123457', 'Budi', '2024', 1),
('2423102087', 'Yahfi Al Farisy', '2024', 2);

-- PERTEMUAN
-- dosen_id: ambil dari users (asumsi Dosen 1 id=2)
-- kelas_id & mata_kuliah_id: sesuai insert di atas
INSERT INTO pertemuan (tanggal, topik, kelas_id, mata_kuliah_id, dosen_id) VALUES
('2026-02-24', 'Pengenalan Sistem Absensi', 2, 1, 2),
('2026-02-24', 'PPL - Scrum Overview',        1, 2, 2);

-- =========================
-- 2) INSERT ABSENSI (TRANSAKSI)
-- =========================
-- absensi pakai mahasiswa_id, bukan nim.
-- Cara aman: cari id mahasiswa & pertemuan lewat subquery.

-- Yahfi hadir di pertemuan kelas II RKS B (pertemuan pertama)
INSERT INTO absensi (pertemuan_id, mahasiswa_id, status, jam_input, keterangan)
VALUES (
  (SELECT id FROM pertemuan WHERE tanggal='2026-02-24' AND kelas_id=2 AND mata_kuliah_id=1 LIMIT 1),
  (SELECT id FROM mahasiswa WHERE nim='2423102087' LIMIT 1),
  'HADIR', '16:10:00', NULL
);

-- Andi izin di pertemuan kelas TI-2A (pertemuan kedua)
INSERT INTO absensi (pertemuan_id, mahasiswa_id, status, jam_input, keterangan)
VALUES (
  (SELECT id FROM pertemuan WHERE tanggal='2026-02-24' AND kelas_id=1 AND mata_kuliah_id=2 LIMIT 1),
  (SELECT id FROM mahasiswa WHERE nim='23123456' LIMIT 1),
  'IZIN', '16:12:00', 'Ada keperluan keluarga'
);

-- Budi sakit di pertemuan kelas TI-2A (pertemuan kedua)
INSERT INTO absensi (pertemuan_id, mahasiswa_id, status, jam_input, keterangan)
VALUES (
  (SELECT id FROM pertemuan WHERE tanggal='2026-02-24' AND kelas_id=1 AND mata_kuliah_id=2 LIMIT 1),
  (SELECT id FROM mahasiswa WHERE nim='23123457' LIMIT 1),
  'SAKIT', '16:13:00', 'Demam'
);

-- =========================
-- 3) UPDATE (contoh)
-- =========================
-- Misal Andi yang tadinya IZIN berubah jadi HADIR (keterangan dihapus)
UPDATE absensi a
JOIN mahasiswa m ON m.id = a.mahasiswa_id
JOIN pertemuan p ON p.id = a.pertemuan_id
SET a.status = 'HADIR',
    a.keterangan = NULL,
    a.jam_input = '16:20:00'
WHERE m.nim = '23123456'
  AND p.tanggal = '2026-02-24'
  AND p.kelas_id = 1
  AND p.mata_kuliah_id = 2;

-- =========================
-- 4) DELETE (contoh)
-- =========================
-- Misal hapus absensi Budi untuk pertemuan TI-2A tanggal tsb (contoh skenario salah input)
DELETE a FROM absensi a
JOIN mahasiswa m ON m.id = a.mahasiswa_id
JOIN pertemuan p ON p.id = a.pertemuan_id
WHERE m.nim = '23123457'
  AND p.tanggal = '2026-02-24'
  AND p.kelas_id = 1
  AND p.mata_kuliah_id = 2;

COMMIT;

-- =========================
-- 5) SELECT JOIN (untuk lampiran & bukti query kompleks)
-- =========================
-- Rekap absensi per tanggal (JOIN 6 tabel)
SELECT
  p.tanggal,
  k.nama_kelas,
  mk.kode_mk,
  mk.nama_mk,
  u.name AS dosen,
  m.nim,
  m.nama AS nama_mahasiswa,
  a.status,
  a.jam_input,
  a.keterangan
FROM absensi a
JOIN pertemuan p ON p.id = a.pertemuan_id
JOIN mahasiswa m ON m.id = a.mahasiswa_id
JOIN kelas k ON k.id = p.kelas_id
JOIN mata_kuliah mk ON mk.id = p.mata_kuliah_id
JOIN users u ON u.id = p.dosen_id
WHERE p.tanggal = '2026-02-24'
ORDER BY k.nama_kelas, mk.kode_mk, m.nim;

-- (Opsional) Hitung jumlah per status
SELECT
  p.tanggal,
  k.nama_kelas,
  SUM(a.status='HADIR') AS hadir,
  SUM(a.status='IZIN')  AS izin,
  SUM(a.status='SAKIT') AS sakit,
  SUM(a.status='ALPA')  AS alpa
FROM absensi a
JOIN pertemuan p ON p.id = a.pertemuan_id
JOIN kelas k ON k.id = p.kelas_id
WHERE p.tanggal = '2026-02-24'
GROUP BY p.tanggal, k.nama_kelas
ORDER BY k.nama_kelas;