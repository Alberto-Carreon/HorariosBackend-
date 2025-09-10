<?php
require __DIR__.'/config.php';
$pdo = pdo();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = 'log_'.bin2hex(random_bytes(6));
  $st=$pdo->prepare("INSERT INTO logs(id,ts,type,account,filters,schedule_id,schedule_type,subject_used,person_name,person_role)
    VALUES(?,NOW(),?,?,?,?,?,?,?,?)");
  $st->execute([
    $id, $in['type']??'', $in['account']??'', json_encode($in['filters']??[],JSON_UNESCAPED_UNICODE),
    $in['scheduleId']??'', $in['scheduleType']??'', $in['subjectUsed']??'',
    $in['personName']??'', $in['personRole']??''
  ]);
  json_out(['ok'=>true]);
}

if ($_SERVER['REQUEST_METHOD']==='GET') {
  require_admin();
  $st=$pdo->query("SELECT * FROM logs ORDER BY ts DESC LIMIT 1000");
  json_out(['items'=>$st->fetchAll()]);
}

json_out(['error'=>'Metodo no permitido'],405);
