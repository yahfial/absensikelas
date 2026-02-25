<?php
// api/ping.php -> cek koneksi DB
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/response.php";

try {
  $pdo = db();
  $v = $pdo->query("SELECT 1 AS ok")->fetch();
  respond("success", "DB connected.", $v);
} catch (Throwable $e) {
  respond("error", "DB connection failed.", null, ["detail"=>$e->getMessage()], 500);
}
?>
