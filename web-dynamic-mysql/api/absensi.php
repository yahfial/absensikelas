<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

function json_input(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function ok($data, string $message = 'OK'): void {
  echo json_encode(['status' => 'success', 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
  exit;
}

function err(string $message, int $code = 400, array $errors = []): void {
  http_response_code($code);
  echo json_encode(['status' => 'error', 'message' => $message, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo = db();

try {
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Kalau tabel pertemuan & mahasiswa ada, tampilkan info lengkap
    // Kalau belum ada/namanya beda, pakai query sederhana di bawahnya (alternatif)
    $stmt = $pdo->query("
      SELECT 
        a.id,
        a.pertemuan_id,
        a.mahasiswa_id,
        a.status,
        a.keterangan,
        a.jam_input,
        a.created_at,
        m.nim,
        m.nama,
        p.tanggal AS tanggal_pertemuan
      FROM absensi a
      LEFT JOIN mahasiswa m ON m.id = a.mahasiswa_id
      LEFT JOIN pertemuan p ON p.id = a.pertemuan_id
      ORDER BY a.id DESC
      LIMIT 200
    ");
    $rows = $stmt->fetchAll();
    ok($rows, 'Daftar absensi');
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = json_input();

    $pertemuan_id = (int)($in['pertemuan_id'] ?? 0);
    $mahasiswa_id = (int)($in['mahasiswa_id'] ?? 0);
    $status = strtoupper(trim((string)($in['status'] ?? '')));
    $keterangan = isset($in['keterangan']) ? trim((string)$in['keterangan']) : null;
    if ($keterangan === '') $keterangan = null;

    $errors = [];
    if ($pertemuan_id <= 0) $errors['pertemuan_id'] = 'pertemuan_id wajib (angka)';
    if ($mahasiswa_id <= 0) $errors['mahasiswa_id'] = 'mahasiswa_id wajib (angka)';
    if (!in_array($status, ['HADIR','IZIN','SAKIT','ALPA'], true)) $errors['status'] = 'status tidak valid';

    if ($errors) err('Validasi gagal', 422, $errors);

    // Validasi opsional: pastikan pertemuan ada
    $cekP = $pdo->prepare("SELECT COUNT(*) c FROM pertemuan WHERE id = ?");
    $cekP->execute([$pertemuan_id]);
    if ((int)($cekP->fetch()['c'] ?? 0) === 0) {
      err('Pertemuan tidak ditemukan', 422, ['pertemuan_id' => 'pertemuan_id tidak terdaftar']);
    }

    // Validasi opsional: pastikan mahasiswa ada
    $cekM = $pdo->prepare("SELECT COUNT(*) c FROM mahasiswa WHERE id = ?");
    $cekM->execute([$mahasiswa_id]);
    if ((int)($cekM->fetch()['c'] ?? 0) === 0) {
      err('Mahasiswa tidak ditemukan', 422, ['mahasiswa_id' => 'mahasiswa_id tidak terdaftar']);
    }

    // Insert sesuai kolom tabel absensi kamu
    $stmt = $pdo->prepare("
      INSERT INTO absensi (pertemuan_id, mahasiswa_id, status, jam_input, keterangan, created_at)
      VALUES (?, ?, ?, CURTIME(), ?, NOW())
    ");
    $stmt->execute([$pertemuan_id, $mahasiswa_id, $status, $keterangan]);

    $id = (int)$pdo->lastInsertId();
    ok([
      'id' => $id,
      'pertemuan_id' => $pertemuan_id,
      'mahasiswa_id' => $mahasiswa_id,
      'status' => $status,
      'keterangan' => $keterangan
    ], 'Absensi berhasil dibuat');
  }

  err('Method not allowed', 405);
} catch (Throwable $e) {
  err('Server error: ' . $e->getMessage(), 500);
}