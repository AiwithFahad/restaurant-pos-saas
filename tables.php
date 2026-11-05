<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['owner','branch_manager','cashier','waiter']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='tables'; $pageTitle='Tables & Floor'; $bid=branch_id();
function q1(PDO $pdo,string $sql,array $a){ $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchColumn(); }
function qa(PDO $pdo,string $sql,array $a): array { $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchAll(); }

$branches=tenant_branches($pdo,$tid);
if (is_post()) { verify_csrf(); $act=$_POST['action']??'';
  if($act==='save'){ $name=trim($_POST['name']??''); $branch=(int)($_POST['branch_id']??0); if($bid) $branch=$bid;
    if(!empty($_POST['id'])) $pdo->prepare('UPDATE restaurant_tables SET name=?,branch_id=?,capacity=? WHERE id=? AND tenant_id=?')->execute([$name,$branch,(int)$_POST['capacity'],(int)$_POST['id'],$tid]);
    else $pdo->prepare('INSERT INTO restaurant_tables(tenant_id,branch_id,name,capacity,status) VALUES(?,?,?,?,'."'available'".')')->execute([$tid,$branch,$name,(int)($_POST['capacity']??4)]);
    flash('success','Table saved.'); }
  elseif($act==='status'){ $pdo->prepare('UPDATE restaurant_tables SET status=? WHERE id=? AND tenant_id=?')->execute([in_array($_POST['status'],['available','occupied','reserved'],true)?$_POST['status']:'available',(int)$_POST['id'],$tid]); flash('success','Table status updated.'); }
  elseif($act==='delete'){ $pdo->prepare('DELETE FROM restaurant_tables WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); flash('success','Table deleted.'); }
  redirect('tables.php'); }
$rows=qa($pdo,'SELECT t.*,b.name branch_name FROM restaurant_tables t JOIN branches b ON b.id=t.branch_id WHERE t.tenant_id=?'.($bid?' AND t.branch_id='.(int)$bid:'').' ORDER BY b.id,t.name',[$tid]);
$edit=null; if(!empty($_GET['edit'])){ $s=$pdo->prepare('SELECT * FROM restaurant_tables WHERE id=? AND tenant_id=?'); $s->execute([(int)$_GET['edit'],$tid]); $edit=$s->fetch(); }
include __DIR__ . '/includes/header.php'; ?>
<div class="card p-3 mb-3"><form class="row g-2" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($edit['id']??'') ?>">
<div class="col-md-3"><input class="form-control" name="name" placeholder="Table name e.g. T5" value="<?= e($edit['name']??'') ?>" required></div>
<div class="col-md-3"><select class="form-select" name="branch_id"><?php foreach($branches as $b): ?><option value="<?= $b['id'] ?>" <?= ($edit['branch_id']??$bid)==$b['id']?'selected':'' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><input class="form-control" type="number" name="capacity" placeholder="Seats" value="<?= e($edit['capacity']??'4') ?>"></div>
<div class="col-md-2"><button class="btn btn-primary w-100"><?= $edit?'Update':'Add' ?> table</button></div></form></div>
<div class="row g-3"><?php foreach($rows as $r): $cls=$r['status']==='available'?'success':($r['status']==='occupied'?'danger':'warning'); ?>
<div class="col-6 col-md-3 col-xl-2"><div class="card p-3 text-center border-<?= $cls ?>"><div class="fs-3"><i class="bi bi-grid-3x3"></i></div><div class="fw-bold"><?= e($r['name']) ?></div><div class="small text-muted"><?= e($r['branch_name']) ?> · <?= $r['capacity'] ?> seats</div>
<span class="badge bg-<?= $cls ?> my-2"><?= e($r['status']) ?></span>
<form method="post" class="d-flex gap-1 justify-content-center flex-wrap"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $r['id'] ?>">
<?php if($r['status']!=='available'): ?><button class="btn btn-sm btn-outline-success" name="status" value="available">Free</button><?php endif; ?>
<?php if($r['status']!=='occupied'): ?><button class="btn btn-sm btn-outline-danger" name="status" value="occupied">Occupy</button><?php endif; ?>
<?php if($r['status']!=='reserved'): ?><button class="btn btn-sm btn-outline-warning" name="status" value="reserved">Reserve</button><?php endif; ?></form>
<div class="mt-2"><a class="small" href="?edit=<?= $r['id'] ?>">Edit</a> · <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-link btn-sm p-0 text-danger" data-confirm="Delete this table?">Delete</button></form></div></div></div><?php endforeach; ?></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
