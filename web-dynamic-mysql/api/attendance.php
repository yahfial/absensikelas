<?php
// api/attendance.php (MySQL + PDO)
// GET    /api/attendance.php
// GET    /api/attendance.php?tanggal=YYYY-MM-DD
// POST   /api/attendance.php            (form-data or JSON) -> auto-create pertemuan/master minimal
// DELETE /api/attendance.php            (JSON: id) or POST with _method=DELETE

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/response.php";

function validate_attendance(array $in): array {
  $errors = [];

  $tanggal = trim((string)($in["tanggal"] ?? ""));
  $nim = trim((string)($in["nim"] ?? ""));
  $status = trim((string)($in["status"] ?? ""));
  $kode_mk = trim((string)($in["kode_mk"] ?? "IF201"));
  $dosen_email = trim((string)($in["dosen_email"] ?? "dosen@demo.local"));
  $dosen_name = trim((string)($in["dosen_name"] ?? "Dosen A"));
  $nama_mk = trim((string)($in["nama_mk"] ?? "Pemrograman Lanjutan"));

  if ($tanggal === "") $errors["tanggal"] = "Tanggal wajib diisi.";
  if ($tanggal !== "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $tanggal)) $errors["tanggal"] = "Format tanggal harus YYYY-MM-DD.";

  if ($nim === "") $errors["nim"] = "NIM wajib diisi.";
  if ($nim !== "" && !preg_match("/^[0-9]{8,12}$/", $nim)) $errors["nim"] = "NIM harus angka 8–12 digit.";

  $allowed = ["HADIR","IZIN","SAKIT","ALPA"];
  if ($status === "") $errors["status"] = "Status wajib diisi.";
  if ($status !== "" && !in_array($status, $allowed, true)) $errors["status"] = "Status tidak valid.";

  if ($errors) return [false, $errors];

  return [true, [
    "tanggal"=>$tanggal,
    "nim"=>$nim,
    "status"=>$status,
    "kode_mk"=>$kode_mk,
    "nama_mk"=>$nama_mk,
    "dosen_email"=>$dosen_email,
    "dosen_name"=>$dosen_name,
  ]];
}

function get_or_create_dosen_id(PDO $pdo, string $email, string $name): int {
  $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
  $stmt->execute([$email]);
  $row = $stmt->fetch();
  if ($row) return (int)$row["id"];

  $ins = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'DOSEN')");
  $ins->execute([$name, $email, 'dummyhash']);
  return (int)$pdo->lastInsertId();
}

function get_or_create_mk_id(PDO $pdo, string $kode_mk, string $nama_mk): int {
  $stmt = $pdo->prepare("SELECT id FROM mata_kuliah WHERE kode_mk = ? LIMIT 1");
  $stmt->execute([$kode_mk]);
  $row = $stmt->fetch();
  if ($row) return (int)$row["id"];

  $ins = $pdo->prepare("INSERT INTO mata_kuliah (kode_mk, nama_mk, sks) VALUES (?, ?, 3)");
  $ins->execute([$kode_mk, $nama_mk]);
  return (int)$pdo->lastInsertId();
}

function get_or_create_pertemuan_id(PDO $pdo, string $tanggal, int $kelas_id, int $mk_id, int $dosen_id): int {
  $stmt = $pdo->prepare("
    SELECT id FROM pertemuan
    WHERE tanggal = ? AND kelas_id = ? AND mata_kuliah_id = ? AND dosen_id = ?
    ORDER BY id DESC LIMIT 1
  ");
  $stmt->execute([$tanggal, $kelas_id, $mk_id, $dosen_id]);
  $row = $stmt->fetch();
  if ($row) return (int)$row["id"];

  $ins = $pdo->prepare("
    INSERT INTO pertemuan (tanggal, topik, kelas_id, mata_kuliah_id, dosen_id)
    VALUES (?, 'Auto-created (demo)', ?, ?, ?)
  ");
  $ins->execute([$tanggal, $kelas_id, $mk_id, $dosen_id]);
  return (int)$pdo->lastInsertId();
}

$method = method_override();
$pdo = db();

if ($method === "GET") {
  $tanggal = trim((string)($_GET["tanggal"] ?? ""));

  $sql = "
    SELECT
      a.id,
      p.tanggal,
      k.nama_kelas,
      mk.kode_mk,
      u.name AS dosen,
      m.nim,
      m.nama AS nama_mahasiswa,
      a.status,
      a.jam_input
    FROM absensi a
    JOIN pertemuan p ON p.id = a.pertemuan_id
    JOIN mahasiswa m ON m.id = a.mahasiswa_id
    JOIN kelas k ON k.id = p.kelas_id
    JOIN mata_kuliah mk ON mk.id = p.mata_kuliah_id
    JOIN users u ON u.id = p.dosen_id
  ";

  $params = [];
  if ($tanggal !== "") {
    $sql .= " WHERE p.tanggal = ? ";
    $params[] = $tanggal;
  }
  $sql .= " ORDER BY p.tanggal DESC, k.nama_kelas, m.nim ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  respond("success", "Daftar absensi.", $rows);
}

if ($method === "POST") {
  $in = get_input();
  [$ok, $result] = validate_attendance($in);
  if (!$ok) respond("error", "Validasi gagal.", null, $result, 422);

  $stmt = $pdo->prepare("SELECT id, nim, nama, kelas_id FROM mahasiswa WHERE nim = ? LIMIT 1");
  $stmt->execute([$result["nim"]]);
  $mhs = $stmt->fetch();
  if (!$mhs) respond("error", "Mahasiswa tidak ditemukan.", null, ["nim"=>"NIM tidak terdaftar di tabel mahasiswa."], 404);

  $dosen_id = get_or_create_dosen_id($pdo, $result["dosen_email"], $result["dosen_name"]);
  $mk_id = get_or_create_mk_id($pdo, $result["kode_mk"], $result["nama_mk"]);
  $pertemuan_id = get_or_create_pertemuan_id($pdo, $result["tanggal"], (int)$mhs["kelas_id"], $mk_id, $dosen_id);

  try {
    $ins = $pdo->prepare("
      INSERT INTO absensi (pertemuan_id, mahasiswa_id, status, jam_input, keterangan)
      VALUES (?, ?, ?, CURTIME(), NULL)
    ");
    $ins->execute([$pertemuan_id, (int)$mhs["id"], $result["status"]]);
  } catch (PDOException $e) {
    if ((int)$e->errorInfo[1] === 1062) {
      respond("error", "Absensi ganda.", null, ["unique"=>"Mahasiswa sudah absen pada pertemuan ini."], 409);
    }
    throw $e;
  }

  $out = [
    "id" => (int)$pdo->lastInsertId(),
    "tanggal" => $result["tanggal"],
    "nim" => $mhs["nim"],
    "nama_mahasiswa" => $mhs["nama"],
    "status" => $result["status"],
    "pertemuan_id" => $pertemuan_id,
  ];
  respond("success", "Absensi berhasil ditambahkan.", $out, null, 201);
}

if ($method === "DELETE") {
  $in = get_input();
  $id = trim((string)($in["id"] ?? ""));
  if ($id === "") respond("error", "Validasi gagal.", null, ["id"=>"ID wajib diisi."], 422);

  $stmt = $pdo->prepare("DELETE FROM absensi WHERE id = ?");
  $stmt->execute([$id]);

  if ($stmt->rowCount() === 0) respond("error", "Data tidak ditemukan.", null, ["id"=>"ID tidak terdaftar."], 404);

  respond("success", "Absensi berhasil dihapus.", ["id"=>$id]);
}

respond("error", "Method tidak didukung.", null, ["method"=>$method], 405);
?>
