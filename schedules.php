<?php
require __DIR__.'/config.php';
$pdo = pdo();

function roleAllowedForType($role,$type){
  if ($role==='tutor'  && in_array($type,['tutorial','asesoria'],true)) return true;
  if ($role==='mentor' && $type==='mentoria') return true;
  return false;
}
function inAllow($role,$id,$pdo){
  if ($role==='tutor')  { $st=$pdo->prepare("SELECT 1 FROM allow_tutores WHERE rfc=?"); $st->execute([$id]); return (bool)$st->fetch(); }
  if ($role==='mentor') { $st=$pdo->prepare("SELECT 1 FROM allow_mentores WHERE cuenta=?"); $st->execute([$id]); return (bool)$st->fetch(); }
  if ($role==='alumno') { $st=$pdo->prepare("SELECT 1 FROM allow_alumnos  WHERE cuenta=?"); $st->execute([$id]); return (bool)$st->fetch(); }
  return false;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
  foreach (['ownerId','ownerRole','type','day','start','end'] as $k) if(empty($in[$k])) json_out(['error'=>"Campo requerido: $k"],400);
  if (!inAllow($in['ownerRole'],$in['ownerId'],$pdo)) json_out(['error'=>'No autorizado (allowlist)'],403);
  if (!roleAllowedForType($in['ownerRole'],$in['type'])) json_out(['error'=>'Tipo no permitido para este rol'],400);
  if (!($in['start'] < $in['end'])) json_out(['error'=>'Rango de horas invalido'],400);

  $id = $in['id'] ?: ('sch_'.bin2hex(random_bytes(6)));
  $st=$pdo->prepare("REPLACE INTO schedules(id,owner_id,owner_role,owner_name,email,type,day,start,end,created_at)
    VALUES(?,?,?,?,?,?,?,?,?,NOW())");
  $st->execute([$id,$in['ownerId'],$in['ownerRole'],$in['ownerName']??'',$in['email']??'',$in['type'],$in['day'],$in['start'],$in['end']]);

  $pdo->prepare("DELETE FROM schedule_subjects WHERE schedule_id=?")->execute([$id]);
  if (!empty($in['subjects']) && is_array($in['subjects'])) {
    $ins=$pdo->prepare("INSERT INTO schedule_subjects(schedule_id,subject) VALUES(?,?)");
    foreach ($in['subjects'] as $s) { $s=trim((string)$s); if($s!=='') $ins->execute([$id,$s]); }
  }
  json_out(['ok'=>true,'id'=>$id]);
}

if ($_SERVER['REQUEST_METHOD']==='DELETE') {
  $id = $_GET['id'] ?? '';
  $actorRole = $_GET['actorRole'] ?? '';
  $actorId   = $_GET['actorId'] ?? '';
  if ($id==='') json_out(['error'=>'id requerido'],400);
  $sc = $pdo->prepare("SELECT * FROM schedules WHERE id=?"); $sc->execute([$id]); $s = $sc->fetch();
  if (!$s) json_out(['error'=>'No existe'],404);
  // Admin por headers
  $isAdmin = false;
  $u = $_SERVER['HTTP_X_ADMIN_USER'] ?? ''; $p = $_SERVER['HTTP_X_ADMIN_PASS'] ?? '';
  global $ADMIN_USER,$ADMIN_PASS; if ($u===$ADMIN_USER && $p===$ADMIN_PASS) $isAdmin=true;
  if (!($isAdmin || ($s['owner_id']===$actorId && $s['owner_role']===$actorRole))) json_out(['error'=>'No autorizado'],403);
  $pdo->prepare("DELETE FROM schedules WHERE id=?")->execute([$id]);
  json_out(['ok'=>true]);
}

if ($_SERVER['REQUEST_METHOD']==='GET') {
  // Filtros: types=..., day=..., hour=HH:MM, subject=..., name=..., ownerId=...
  $types = isset($_GET['types']) && $_GET['types']!=='' ? explode(',',$_GET['types']) : [];
  $day   = $_GET['day']   ?? '';
  $hour  = $_GET['hour']  ?? '';
  $name  = $_GET['name']  ?? '';
  $owner = $_GET['ownerId'] ?? '';
  $subject = $_GET['subject'] ?? '';  // coma separada

  $where=[]; $p=[];
  if ($types){ $where[]='type IN ('.implode(',',array_fill(0,count($types),'?')).')'; $p=array_merge($p,$types); }
  if ($day!==''){ $where[]='day=?'; $p[]=$day; }
  if ($hour!==''){ $where[]='start<=? AND end>=?'; $p[]=$hour; $p[]=$hour; }
  if ($owner!==''){ $where[]='owner_id=?'; $p[]=$owner; }
  if ($name!==''){ $where[]='LOWER(owner_name) LIKE ?'; $p[]='%'.strtolower($name).'%'; }

  $sql="SELECT s.*,
        GROUP_CONCAT(ss.subject ORDER BY ss.subject SEPARATOR '||') AS subjects
        FROM schedules s
        LEFT JOIN schedule_subjects ss ON ss.schedule_id=s.id
        ".($where?('WHERE '.implode(' AND ',$where)):'')."
        GROUP BY s.id
        ORDER BY FIELD(day,'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'), start";
  $st=$pdo->prepare($sql); $st->execute($p);
  $items = [];
  foreach ($st as $r){
    $r['subjects'] = $r['subjects']? explode('||',$r['subjects']) : [];
    $items[] = $r;
  }
  // filtro extra por subject si viene
  if ($subject!=='') {
    $want = array_map('trim', explode(',', $subject));
    $items = array_values(array_filter($items, function($it) use ($want){
      if ($it['type']==='tutorial') return false;
      foreach ($it['subjects'] as $s) if (in_array($s,$want,true)) return true;
      return false;
    }));
  }
  json_out(['items'=>$items]);
}

json_out(['error'=>'Metodo no permitido'],405);
