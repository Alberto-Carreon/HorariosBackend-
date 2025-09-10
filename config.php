<?php
// 🔧 AJUSTA ESTAS 4 LÍNEAS A TU BASE DE DATOS DE BLUEHOST
$DB_HOST = 'localhost';
$DB_NAME = 'fi_horarios';
$DB_USER = 'usuario_db';
$DB_PASS = 'password_db';

// CORS: pon aquí tu URL de GitHub Pages
$ALLOWED_ORIGIN = 'https://TU_USUARIO.github.io';

// Admin por defecto (puedes cambiarlos o ponerlos vía .htaccess/variables)
$ADMIN_USER = 'correo';
$ADMIN_PASS = '7ut0r1a.4pp';

header('Access-Control-Allow-Origin: ' . $ALLOWED_ORIGIN);
header('Access-Control-Allow-Headers: Content-Type, X-Admin-User, X-Admin-Pass');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function pdo() {
  global $DB_HOST,$DB_NAME,$DB_USER,$DB_PASS;
  static $pdo;
  if (!$pdo) {
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
  }
  return $pdo;
}

function json_out($data, $code=200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function require_admin() {
  global $ADMIN_USER,$ADMIN_PASS;
  $u = $_SERVER['HTTP_X_ADMIN_USER'] ?? '';
  $p = $_SERVER['HTTP_X_ADMIN_PASS'] ?? '';
  if ($u === $ADMIN_USER && $p === $ADMIN_PASS) return;
  json_out(['error'=>'Admin auth required'], 401);
}
