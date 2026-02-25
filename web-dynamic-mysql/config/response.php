<?php
// config/response.php
declare(strict_types=1);

function respond(string $status, string $message, $data=null, $errors=null, int $httpCode=200): void {
  http_response_code($httpCode);
  header("Content-Type: application/json; charset=utf-8");
  $payload = ["status"=>$status, "message"=>$message];
  if ($data !== null) $payload["data"] = $data;
  if ($errors !== null) $payload["errors"] = $errors;
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

function get_input(): array {
  $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
  if (stripos($contentType, "application/json") !== false) {
    $raw = file_get_contents("php://input");
    $obj = json_decode($raw, true);
    return is_array($obj) ? $obj : [];
  }
  return $_POST ?? [];
}

function method_override(): string {
  $method = $_SERVER["REQUEST_METHOD"] ?? "GET";
  if ($method === "POST" && isset($_POST["_method"])) {
    $m = strtoupper(trim((string)$_POST["_method"]));
    if (in_array($m, ["PUT","DELETE"], true)) return $m;
  }
  return strtoupper($method);
}
?>
