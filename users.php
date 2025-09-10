<?php
require __DIR__.'/config.php';
$pdo = pdo();
if ($_SERVER['REQUEST_METHOD']!=='POST') json_out(['error'=>'Metodo no permitido'],405);
$in = json_decode(file_get_contents('php://input'), true) ?: [];
$id = $in['id'] ?? ''; $role = $in['role'] ?? '';
$name = $in['name'] ?? ''; $email = $in['email'] ?? '';
if ($id==='' || $role==='') json_out(['error'=>'id y role requeridos'],400);
$st=$pdo->prepare("INSERT INTO users(id,role,name,email,created_at) VALUES(?,?,?,?,NOW())
  ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email)");
$st->execute([$id,$role,$name,$email]);
json_out(['ok'=>true]);
