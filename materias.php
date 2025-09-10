<?php
require __DIR__.'/config.php';
function norm($s){ return strtolower(iconv('UTF-8','ASCII//TRANSLIT',$s)); }
$pdo = pdo();

if ($_SERVER['REQUEST_METHOD']==='GET') {
  $st = $pdo->query("SELECT name FROM subjects ORDER BY name");
  json_out(['materias'=> array_column($st->fetchAll(), 'name')]);
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_admin();
  $input = json_decode(file_get_contents('php://input'), true);
  $arr = $input['materias'] ?? null;
  if (!is_array($arr)) json_out(['error'=>'materias[] requerido'],400);
  $pdo->exec("DELETE FROM subjects");
  $ins = $pdo->prepare("INSERT INTO subjects(name,norm) VALUES(?,?)");
  foreach ($arr as $m) {
    $m = trim((string)$m);
    if ($m==='') continue;
    $ins->execute([$m, norm($m)]);
  }
  json_out(['ok'=>true,'count'=>count($arr)]);
}
json_out(['error'=>'Metodo no permitido'],405);
