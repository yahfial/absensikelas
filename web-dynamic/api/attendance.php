<?php
// Simple PHP+JSON CRUD (no framework) for "absensi"
// Endpoints:
//   GET    /api/attendance.php                -> list attendance
//   GET    /api/attendance.php?tanggal=YYYY-MM-DD -> filter by tanggal (optional)
//   POST   /api/attendance.php                -> create
//   DELETE /api/attendance.php                -> delete by id (JSON) OR POST with _method=DELETE
//
// Storage: data/attendance.json

header("Content-Type: application/json; charset=utf-8");

$dataFile = __DIR__ . "/../data/attendance.json";

function respond($status, $message, $data = null, $errors = null, $httpCode = 200) {
  http_response_code($httpCode);
  $payload = ["status" => $status, "message" => $message];
  if ($data !== null) $payload["data"] = $data;
  if ($errors !== null) $payload["errors"] = $errors;
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

function load_list($file) {
  if (!file_exists($file)) return [];
  $raw = file_get_contents($file);
  $arr = json_decode($raw, true);
  return is_array($arr) ? $arr : [];
}

function save_list($file, $list) {
  $raw = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  file_put_contents($file, $raw, LOCK_EX);
}

function get_input() {
  $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
  if (stripos($contentType, "application/json") !== false) {
    $raw = file_get_contents("php://input");
    $obj = json_decode($raw, true);
    return is_array($obj) ? $obj : [];
  }
  return $_POST;
}

function method_override() {
  $method = $_SERVER["REQUEST_METHOD"] ?? "GET";
  if ($method === "POST" && isset($_POST["_method"])) {
    $m = strtoupper(trim($_POST["_method"]));
    if (in_array($m, ["DELETE"])) return $m;
  }
  return strtoupper($method);
}

function validate_attendance($in) {
  $errors = [];

  $tanggal = trim((string)($in["tanggal"] ?? ""));
  $nim = trim((string)($in["nim"] ?? ""));
  $nama = trim((string)($in["nama"] ?? ""));
  $kelas = trim((string)($in["kelas"] ?? ""));
  $status = trim((string)($in["status"] ?? ""));

  if (!$tanggal) $errors["tanggal"] = "Tanggal wajib diisi.";
  if ($tanggal && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $tanggal)) $errors["tanggal"] = "Format tanggal harus YYYY-MM-DD.";

  if (!$nim) $errors["nim"] = "NIM wajib diisi.";
  if ($nim && !preg_match("/^[0-9]{8,12}$/", $nim)) $errors["nim"] = "NIM harus angka 8–12 digit.";

  if (!$nama) $errors["nama"] = "Nama wajib diisi.";
  if (!$kelas) $errors["kelas"] = "Kelas wajib diisi.";

  $allowed = ["HADIR", "IZIN", "SAKIT", "ALPA"];
  if (!$status) $errors["status"] = "Status wajib diisi.";
  if ($status && !in_array($status, $allowed)) $errors["status"] = "Status tidak valid.";

  if (!empty($errors)) return [false, $errors];

  return [true, ["tanggal"=>$tanggal, "nim"=>$nim, "nama"=>$nama, "kelas"=>$kelas, "status"=>$status]];
}

$method = method_override();

if ($method === "GET") {
  $list = load_list($dataFile);
  $tanggal = trim((string)($_GET["tanggal"] ?? ""));
  if ($tanggal) {
    $filtered = array_values(array_filter($list, fn($r) => ($r["tanggal"] ?? "") === $tanggal));
    respond("success", "Daftar absensi (filter tanggal).", $filtered);
  }
  respond("success", "Daftar absensi.", $list);
}

if ($method === "POST") {
  $in = get_input();
  [$ok, $result] = validate_attendance($in);
  if (!$ok) respond("error", "Validasi gagal.", null, $result, 422);

  $list = load_list($dataFile);
  $new = $result;
  $new["id"] = (string)round(microtime(true) * 1000);
  $new["created_at"] = date("c");
  $list[] = $new;

  save_list($dataFile, $list);
  respond("success", "Absensi berhasil ditambahkan.", $new, null, 201);
}

if ($method === "DELETE") {
  $in = get_input();
  $id = trim((string)($in["id"] ?? ""));
  if (!$id) respond("error", "Validasi gagal.", null, ["id" => "ID wajib diisi."], 422);

  $list = load_list($dataFile);
  $newList = [];
  $deleted = null;

  foreach ($list as $r) {
    if (($r["id"] ?? "") === $id) {
      $deleted = $r;
      continue;
    }
    $newList[] = $r;
  }

  if ($deleted === null) respond("error", "Data tidak ditemukan.", null, ["id" => "ID tidak terdaftar."], 404);

  save_list($dataFile, $newList);
  respond("success", "Absensi berhasil dihapus.", $deleted);
}

respond("error", "Method tidak didukung.", null, ["method" => $method], 405);
?>
