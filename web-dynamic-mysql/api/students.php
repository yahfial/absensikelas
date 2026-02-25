<?php
// api/students.php (MySQL + PDO)
// GET    /api/students.php
// GET    /api/students.php?nim=...
// POST   /api/students.php            (form-data or JSON)
// PUT    /api/students.php            (JSON) or POST with _method=PUT
// DELETE /api/students.php            (JSON) or POST with _method=DELETE

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/response.php";

function validate_student(array $in): array {
  $errors = [];

  $nim = trim((string)($in["nim"] ?? ""));
  $nama = trim((string)($in["nama"] ?? ""));
  $kelas = trim((string)($in["kelas"] ?? ""));      // nama kelas (mis: TI-2A)
  $angkatan = trim((string)($in["angkatan"] ?? ""));

  if ($nim === "") $errors["nim"] = "NIM wajib diisi.";
  if ($nim !== "" && !preg_match("/^[0-9]{8,12}$/", $nim)) $errors["nim"] = "NIM harus angka 8–12 digit.";

  if ($nama === "") $errors["nama"] = "Nama wajib diisi.";
  if ($kelas === "") $errors["kelas"] = "Kelas wajib diisi.";

  if ($angkatan === "") $errors["angkatan"] = "Angkatan wajib diisi.";
  if ($angkatan !== "" && !preg_match("/^[0-9]{4}$/", $angkatan)) $errors["angkatan"] = "Angkatan harus 4 digit (contoh 2024).";

  if ($errors) return [false, $errors];
  return [true, ["nim"=>$nim, "nama"=>$nama, "kelas"=>$kelas, "angkatan"=>$angkatan]];
}

function get_or_create_kelas_id(PDO $pdo, string $nama_kelas): int {
  $stmt = $pdo->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
  $stmt->execute([$nama_kelas]);
  $row = $stmt->fetch();
  if ($row) return (int)$row["id"];

  // untuk memudahkan testing: auto-create kelas jika belum ada
  $ins = $pdo->prepare("INSERT INTO kelas (nama_kelas) VALUES (?)");
  $ins->execute([$nama_kelas]);
  return (int)$pdo->lastInsertId();
}

$method = method_override();
$pdo = db();

if ($method === "GET") {
  $nim = trim((string)($_GET["nim"] ?? ""));
  if ($nim !== "") {
    $stmt = $pdo->prepare("
      SELECT m.id, m.nim, m.nama, m.angkatan, m.kelas_id, k.nama_kelas
      FROM mahasiswa m
      JOIN kelas k ON k.id = m.kelas_id
      WHERE m.nim = ?
      LIMIT 1
    ");
    $stmt->execute([$nim]);
    $row = $stmt->fetch();
    if (!$row) respond("error", "Data tidak ditemukan.", null, ["nim"=>"NIM tidak terdaftar."], 404);
    respond("success", "Data ditemukan.", $row);
  }

  $rows = $pdo->query("
    SELECT m.id, m.nim, m.nama, m.angkatan, m.kelas_id, k.nama_kelas
    FROM mahasiswa m
    JOIN kelas k ON k.id = m.kelas_id
    ORDER BY m.nim
  ")->fetchAll();

  respond("success", "Daftar mahasiswa.", $rows);
}

if ($method === "POST") {
  $in = get_input();
  [$ok, $result] = validate_student($in);
  if (!$ok) respond("error", "Validasi gagal.", null, $result, 422);

  $kelas_id = get_or_create_kelas_id($pdo, $result["kelas"]);

  try {
    $stmt = $pdo->prepare("
      INSERT INTO mahasiswa (nim, nama, angkatan, kelas_id)
      VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$result["nim"], $result["nama"], $result["angkatan"], $kelas_id]);
  } catch (PDOException $e) {
    if ((int)$e->errorInfo[1] === 1062) {
      respond("error", "NIM sudah terdaftar.", null, ["nim"=>"NIM sudah ada."], 409);
    }
    throw $e;
  }

  $data = [
    "id" => (int)$pdo->lastInsertId(),
    "nim" => $result["nim"],
    "nama" => $result["nama"],
    "angkatan" => $result["angkatan"],
    "kelas_id" => $kelas_id,
    "nama_kelas" => $result["kelas"],
  ];

  respond("success", "Mahasiswa berhasil ditambahkan.", $data, null, 201);
}

if ($method === "PUT") {
  $in = get_input();
  [$ok, $result] = validate_student($in);
  if (!$ok) respond("error", "Validasi gagal.", null, $result, 422);

  $kelas_id = get_or_create_kelas_id($pdo, $result["kelas"]);

  $stmt = $pdo->prepare("
    UPDATE mahasiswa
    SET nama = ?, angkatan = ?, kelas_id = ?
    WHERE nim = ?
  ");
  $stmt->execute([$result["nama"], $result["angkatan"], $kelas_id, $result["nim"]]);

  if ($stmt->rowCount() === 0) {
    $chk = $pdo->prepare("SELECT id FROM mahasiswa WHERE nim = ? LIMIT 1");
    $chk->execute([$result["nim"]]);
    if (!$chk->fetch()) respond("error", "Data tidak ditemukan.", null, ["nim"=>"NIM tidak terdaftar."], 404);
  }

  $out = [
    "nim" => $result["nim"],
    "nama" => $result["nama"],
    "angkatan" => $result["angkatan"],
    "kelas_id" => $kelas_id,
    "nama_kelas" => $result["kelas"],
  ];

  respond("success", "Mahasiswa berhasil diperbarui.", $out);
}

if ($method === "DELETE") {
  $in = get_input();
  $nim = trim((string)($in["nim"] ?? ""));
  if ($nim === "") respond("error", "Validasi gagal.", null, ["nim"=>"NIM wajib diisi."], 422);

  $stmt = $pdo->prepare("DELETE FROM mahasiswa WHERE nim = ?");
  $stmt->execute([$nim]);

  if ($stmt->rowCount() === 0) respond("error", "Data tidak ditemukan.", null, ["nim"=>"NIM tidak terdaftar."], 404);

  respond("success", "Mahasiswa berhasil dihapus.", ["nim"=>$nim]);
}

respond("error", "Method tidak didukung.", null, ["method"=>$method], 405);
?>
