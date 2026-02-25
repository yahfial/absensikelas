<?php
// Simple PHP+JSON CRUD (no framework) for "mahasiswa"
// Endpoints:
//   GET    /api/students.php            -> list students
//   GET    /api/students.php?nim=...    -> get by nim
//   POST   /api/students.php            -> create (form-data or JSON)
//   PUT    /api/students.php            -> update (JSON) OR POST with _method=PUT
//   DELETE /api/students.php            -> delete by nim (JSON) OR POST with _method=DELETE
//
// Storage: data/students.json (file-based) -> easy to migrate to DB later.

header("Content-Type: application/json; charset=utf-8");

$dataFile = __DIR__ . "/../data/students.json";

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
  // default: form-data / x-www-form-urlencoded
  return $_POST;
}

function method_override() {
  $method = $_SERVER["REQUEST_METHOD"] ?? "GET";
  if ($method === "POST" && isset($_POST["_method"])) {
    $m = strtoupper(trim($_POST["_method"]));
    if (in_array($m, ["PUT", "DELETE"])) return $m;
  }
  return strtoupper($method);
}

function validate_student($in, $isUpdate = false) {
  $errors = [];

  $nim = trim((string)($in["nim"] ?? ""));
  $nama = trim((string)($in["nama"] ?? ""));
  $kelas = trim((string)($in["kelas"] ?? ""));
  $angkatan = trim((string)($in["angkatan"] ?? ""));

  if (!$nim) $errors["nim"] = "NIM wajib diisi.";
  if ($nim && (!preg_match("/^[0-9]{8,12}$/", $nim))) $errors["nim"] = "NIM harus angka 8–12 digit.";

  if (!$nama) $errors["nama"] = "Nama wajib diisi.";
  if (!$kelas) $errors["kelas"] = "Kelas wajib diisi.";

  if (!$angkatan) $errors["angkatan"] = "Angkatan wajib diisi.";
  if ($angkatan && (!preg_match("/^[0-9]{4}$/", $angkatan))) $errors["angkatan"] = "Angkatan harus 4 digit (contoh 2024).";

  if (!empty($errors)) return [false, $errors];

  return [true, ["nim"=>$nim, "nama"=>$nama, "kelas"=>$kelas, "angkatan"=>$angkatan]];
}

// -------------------
// ROUTING
// -------------------
$method = method_override();

if ($method === "GET") {
  $list = load_list($dataFile);
  $nim = trim((string)($_GET["nim"] ?? ""));
  if ($nim) {
    foreach ($list as $s) {
      if (($s["nim"] ?? "") === $nim) respond("success", "Data ditemukan.", $s);
    }
    respond("error", "Data tidak ditemukan.", null, ["nim" => "NIM tidak terdaftar."], 404);
  }
  respond("success", "Daftar mahasiswa.", $list);
}

if ($method === "POST") {
  $in = get_input();
  [$ok, $result] = validate_student($in);
  if (!$ok) respond("error", "Validasi gagal.", null, $result, 422);

  $list = load_list($dataFile);
  foreach ($list as $s) {
    if (($s["nim"] ?? "") === $result["nim"]) {
      respond("error", "NIM sudah terdaftar.", null, ["nim" => "NIM sudah ada."], 409);
    }
  }

  $new = $result;
  $new["id"] = (string)round(microtime(true) * 1000);
  $new["created_at"] = date("c");

  $list[] = $new;
  save_list($dataFile, $list);

  respond("success", "Mahasiswa berhasil ditambahkan.", $new, null, 201);
}

if ($method === "PUT") {
  // For PUT, read JSON input (or treat empty JSON as error)
  $in = get_input();
  [$ok, $result] = validate_student($in, true);
  if (!$ok) respond("error", "Validasi gagal.", null, $result, 422);

  $list = load_list($dataFile);
  $updated = null;

  for ($i = 0; $i < count($list); $i++) {
    if (($list[$i]["nim"] ?? "") === $result["nim"]) {
      $list[$i]["nama"] = $result["nama"];
      $list[$i]["kelas"] = $result["kelas"];
      $list[$i]["angkatan"] = $result["angkatan"];
      $list[$i]["updated_at"] = date("c");
      $updated = $list[$i];
      break;
    }
  }

  if ($updated === null) respond("error", "Data tidak ditemukan.", null, ["nim" => "NIM tidak terdaftar."], 404);

  save_list($dataFile, $list);
  respond("success", "Mahasiswa berhasil diperbarui.", $updated);
}

if ($method === "DELETE") {
  $in = get_input();
  $nim = trim((string)($in["nim"] ?? ""));
  if (!$nim) respond("error", "Validasi gagal.", null, ["nim" => "NIM wajib diisi."], 422);

  $list = load_list($dataFile);
  $newList = [];
  $deleted = null;

  foreach ($list as $s) {
    if (($s["nim"] ?? "") === $nim) {
      $deleted = $s;
      continue;
    }
    $newList[] = $s;
  }

  if ($deleted === null) respond("error", "Data tidak ditemukan.", null, ["nim" => "NIM tidak terdaftar."], 404);

  save_list($dataFile, $newList);
  respond("success", "Mahasiswa berhasil dihapus.", $deleted);
}

respond("error", "Method tidak didukung.", null, ["method" => $method], 405);
?>
