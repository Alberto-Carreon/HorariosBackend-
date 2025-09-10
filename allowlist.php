<?php
require __DIR__.'/config.php';
$pdo = pdo();

if ($_SERVER['REQUEST_METHOD']==='GET') {
  $t = $pdo->query("SELECT rfc FROM allow_tutores")->fetchAll();
  $m = $pdo->query("SELECT cuenta FROM allow_mentores")->fetchAll();
  $a = $pdo->query("SELECT cuenta FROM allow_alumnos")->fetchAll();
  json_out([
    'tutoresRFC'=> array_column($t,'rfc'),
    'mentoresCuenta'=> array_column($m,'cuenta'),
    'alumnosCuenta'=> array_column($a,'cuenta'),
  ]);
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  require_admin();
  $type = $_GET['type'] ?? '';         // tutores | mentores | alumnos
  $input = json_decode(file_get_contents('php://input'), true) ?: [];
  $list = $input['list'] ?? [];
  if (!in_array($type, ['tutores','mentores','alumnos'],true)) json_out(['error'=>'type invalido'],400);

  if ($type==='tutores') {
    $ins=$pdo->prepare("INSERT IGNORE INTO allow_tutores(rfc) VALUES(?)");
    $ok=0; foreach($list as $v){ $v=strtoupper(trim((string)$v)); if(preg_match('/^[A-ZÑ&]{4}\d{6}$/u',$v)){ $ins->execute([$v]); $ok++; } }
    json_out(['ok'=>true,'added'=>$ok]);
  }
  if ($type==='mentores' || $type==='alumnos') {
    $table = $type==='mentores' ? 'allow_mentores' : 'allow_alumnos';
    $ins=$pdo->prepare("INSERT IGNORE INTO $table(cuenta) VALUES(?)");
    $ok=0; foreach($list as $v){ $v=trim((string)$v); if(preg_match('/^\d{7}$/',$v)){ $ins->execute([$v]); $ok++; } }
    json_out(['ok'=>true,'added'=>$ok]);
  }
}

if ($_SERVER['REQUEST_METHOD']==='DELETE') {
  require_admin();
  $type = $_GET['type'] ?? '';
  $val  = $_GET['val']  ?? '';
  if ($type==='tutores') { $st=$pdo->prepare("DELETE FROM allow_tutores WHERE rfc=?"); $st->execute([strtoupper($val)]); }
  elseif ($type==='mentores') { $st=$pdo->prepare("DELETE FROM allow_mentores WHERE cuenta=?"); $st->execute([$val]); }
  elseif ($type==='alumnos')  { $st=$pdo->prepare("DELETE FROM allow_alumnos  WHERE cuenta=?"); $st->execute([$val]); }
  else json_out(['error'=>'type invalido'],400);
  json_out(['ok'=>true]);
}
json_out(['error'=>'Metodo no permitido'],405);
