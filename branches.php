<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['owner','branch_manager']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='branches'; $pageTitle='Branches'; $bid=branch_id();
function q1(PDO $pdo,string $sql,array $a){ $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchColumn(); }
function qa(PDO $pdo,string $sql,array $a): array { $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchAll(); }

if (is_post()) { verify_csrf(); $act=$_POST['action']??'';
  if($act==='save'){ if(!empty($_POST['id'])) $pdo->prepare('UPDATE branches SET name=?,address=?,phone=? WHERE id=? AND tenant_id=?')->execute([trim($_POST['name']),trim($_POST['address']??''),trim($_POST['phone']??''),(int)$_POST['id'],$tid]);
    else { if (plan_limit_reached($pdo,'max_branches','branches')) { flash('error','Plan branch limit reached — upgrade your plan.'); redirect('branches.php'); }
      $pdo->prepare('INSERT INTO branches(tenant_id,name,address,phone,is_main,created_at) VALUES(?,?,?,?,0,NOW())')->execute([$tid,trim($_POST['name']),trim($_POST['address']??''),trim($_POST['phone']??'')]); } flash('success','Branch saved.'); }
  elseif($act==='delete'){ $pdo->prepare('DELETE FROM branches WHERE id=? AND tenant_id=? AND is_main=0')->execute([(int)$_POST['id'],$tid]); flash('success','Branch deleted (main branch cannot be deleted).'); }
  redirect('branches.php'); }
$rows=qa($pdo,'SELECT b.*, (SELECT COUNT(*) FROM orders o WHERE o.branch_id=b.id) orders_count, (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.branch_id=b.id AND o.status!="cancelled") revenue, (SELECT COUNT(*) FROM staff s WHERE s.branch_id=b.id) staff_count, (SELECT COUNT(*) FROM restaurant_tables t WHERE t.branch_id=b.id) tables_count FROM branches b WHERE b.tenant_id=? ORDER BY b.is_main DESC,b.id',[$tid]);
$edit=null; if(!empty($_GET['edit'])){ $s=$pdo->prepare('SELECT * FROM branches WHERE id=? AND tenant_id=?'); $s->execute([(int)$_GET['edit'],$tid]); $edit=$s->fetch(); }
include __DIR__ . '/includes/header.php'; ?>
<div class="card p-3 mb-3"><form class="row g-2" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($edit['id']??'') ?>">
<div class="col-md-3"><input class="form-control" name="name" placeholder="Branch name" value="<?= e($edit['name']??'') ?>" required></div>
<div class="col-md-4"><input class="form-control" name="address" placeholder="Address" value="<?= e($edit['address']??'') ?>"></div>
<div class="col-md-3"><input class="form-control" name="phone" placeholder="Phone" value="<?= e($edit['phone']??'') ?>"></div>
<div class="col-md-2"><button class="btn btn-primary w-100"><?= $edit?'Update':'Add' ?> branch</button></div></form></div>
<div class="row g-3"><?php foreach($rows as $r): ?><div class="col-md-6 col-xl-4"><div class="card p-3 h-100"><div class="d-flex justify-content-between"><h6 class="mb-0"><?= e($r['name']) ?> <?= $r['is_main']?'<span class="badge bg-primary">Main</span>':'' ?></h6><span><a class="small" href="?edit=<?= $r['id'] ?>">Edit</a><?php if(!$r['is_main']): ?> · <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-link btn-sm p-0 text-danger" data-confirm="Delete branch?">Delete</button></form><?php endif; ?></span></div>
<div class="small text-muted"><?= e($r['address']??'') ?> · <?= e($r['phone']??'') ?></div>
<div class="row text-center mt-3"><div class="col-3"><div class="fw-bold"><?= $r['orders_count'] ?></div><div class="small text-muted">Orders</div></div><div class="col-3"><div class="fw-bold"><?= money($r['revenue']) ?></div><div class="small text-muted">Revenue</div></div><div class="col-3"><div class="fw-bold"><?= $r['staff_count'] ?></div><div class="small text-muted">Staff</div></div><div class="col-3"><div class="fw-bold"><?= $r['tables_count'] ?></div><div class="small text-muted">Tables</div></div></div></div></div><?php endforeach; ?></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
