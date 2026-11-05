<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['owner','branch_manager']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='staff'; $pageTitle='Staff & Employees'; $bid=branch_id();
function q1(PDO $pdo,string $sql,array $a){ $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchColumn(); }
function qa(PDO $pdo,string $sql,array $a): array { $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchAll(); }

$branches=tenant_branches($pdo,$tid); $roles=['owner','branch_manager','cashier','waiter','kitchen','inventory_manager','accountant'];
if (is_post()) { verify_csrf(); $act=$_POST['action']??'';
  if($act==='save'){ $d=[trim($_POST['name']),trim($_POST['email']??''),trim($_POST['phone']??''),$_POST['role'],!empty($_POST['branch_id'])?(int)$_POST['branch_id']:null,$_POST['shift']??'morning',(float)($_POST['salary']??0)];
    if(!empty($_POST['id'])) $pdo->prepare('UPDATE staff SET name=?,email=?,phone=?,role=?,branch_id=?,shift=?,salary=? WHERE id=? AND tenant_id=?')->execute([...$d,(int)$_POST['id'],$tid]);
    else { if (plan_limit_reached($pdo,'max_users','staff')) { flash('error','Plan staff limit reached — upgrade your plan.'); redirect('staff.php'); }
      $pdo->prepare('INSERT INTO staff(tenant_id,name,email,phone,role,branch_id,shift,salary,status,created_at) VALUES(?,?,?,?,?,?,?,?,"active",NOW())')->execute([$tid,...$d]); } flash('success','Staff saved.'); }
  elseif($act==='login_user'){ $s=$pdo->prepare('SELECT * FROM staff WHERE id=? AND tenant_id=?'); $s->execute([(int)$_POST['id'],$tid]); $st=$s->fetch();
    if($st && $st['email']){ try{ $pdo->prepare('INSERT INTO users(tenant_id,branch_id,name,email,password_hash,role,status,email_verified_at,created_at) VALUES(?,?,?,?,?,"active",NOW(),NOW())')->execute([$tid,$st['branch_id'],$st['name'],$st['email'],password_hash('password',PASSWORD_DEFAULT),in_array($st['role'],$roles,true)?$st['role']:'cashier']); $pdo->prepare('UPDATE staff SET user_id=? WHERE id=?')->execute([(int)$pdo->lastInsertId(),$st['id']]); flash('success','Login created — temporary password: password'); }catch(Throwable $ex){ flash('error','Login exists already.'); } } }
  elseif($act==='toggle'){ $pdo->prepare('UPDATE staff SET status=IF(status="active","inactive","active") WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); flash('success','Status toggled.'); }
  elseif($act==='delete'){ $pdo->prepare('DELETE FROM staff WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); flash('success','Staff deleted.'); }
  redirect('staff.php'); }
$rows=qa($pdo,'SELECT s.*,b.name branch_name FROM staff s LEFT JOIN branches b ON b.id=s.branch_id WHERE s.tenant_id=? ORDER BY s.name',[$tid]);
$edit=null; if(!empty($_GET['edit'])){ $s=$pdo->prepare('SELECT * FROM staff WHERE id=? AND tenant_id=?'); $s->execute([(int)$_GET['edit'],$tid]); $edit=$s->fetch(); }
include __DIR__ . '/includes/header.php'; ?>
<div class="card p-3 mb-3"><form class="row g-2" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($edit['id']??'') ?>">
<div class="col-md-2"><input class="form-control" name="name" placeholder="Name" value="<?= e($edit['name']??'') ?>" required></div>
<div class="col-md-2"><input class="form-control" name="email" placeholder="Email" value="<?= e($edit['email']??'') ?>"></div>
<div class="col-md-2"><input class="form-control" name="phone" placeholder="Phone" value="<?= e($edit['phone']??'') ?>"></div>
<div class="col-md-2"><select class="form-select" name="role"><?php foreach($roles as $r): ?><option value="<?= $r ?>" <?= ($edit['role']??'')===$r?'selected':'' ?>><?= str_replace('_',' ',$r) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select class="form-select" name="branch_id"><option value="">All branches</option><?php foreach($branches as $b): ?><option value="<?= $b['id'] ?>" <?= ($edit['branch_id']??0)==$b['id']?'selected':'' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><select class="form-select" name="shift"><?php foreach(['morning','evening','night','full-day'] as $sh): ?><option <?= ($edit['shift']??'morning')===$sh?'selected':'' ?>><?= $sh ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><input class="form-control" type="number" step="0.01" name="salary" placeholder="Salary" value="<?= e($edit['salary']??'') ?>"></div>
<div class="col-md-12"><button class="btn btn-primary"><?= $edit?'Update':'Add' ?> staff member</button></div></form></div>
<div class="card p-0 overflow-auto"><table class="table mb-0"><tr><th>Name</th><th>Role</th><th>Branch</th><th>Shift</th><th>Salary</th><th>Status</th><th></th></tr>
<?php foreach($rows as $r): ?><tr><td><strong><?= e($r['name']) ?></strong><div class="small text-muted"><?= e($r['email']??'') ?> <?= e($r['phone']??'') ?></div></td><td><?= e(str_replace('_',' ',$r['role'])) ?></td><td><?= e($r['branch_name']??'—') ?></td><td><?= e($r['shift']) ?></td><td><?= money($r['salary']) ?></td><td><span class="badge bg-<?= $r['status']==='active'?'success':'secondary' ?>"><?= e($r['status']) ?></span></td>
<td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="?edit=<?= $r['id'] ?>">Edit</a>
<form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-secondary">Toggle</button></form>
<?php if(!$r['user_id'] && $r['email']): ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="login_user"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-success">Create login</button></form><?php endif; ?>
<form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete staff member?">Delete</button></form></td></tr><?php endforeach; ?></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
