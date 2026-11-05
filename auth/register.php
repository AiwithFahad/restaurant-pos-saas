<?php require_once __DIR__ . '/../includes/auth.php'; $error='';
if (is_post()) { verify_csrf(); $pdo=db();
  $company=trim($_POST['company']??''); $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
  if (strlen($pass)<8) $error='Password must be at least 8 characters.';
  else { try { $pdo->beginTransaction();
    $slug=strtolower(preg_replace('/[^a-z0-9]+/','-', $company));
    $pdo->prepare('INSERT INTO tenants(name,slug,status,created_at) VALUES(?,?,"active",NOW())')->execute([$company,$slug.'-'.substr(bin2hex(random_bytes(3)),0,6)]);
    $tid=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO branches(tenant_id,name,address,is_main,created_at) VALUES(?,?,?,1,NOW())')->execute([$tid,'Main Branch',trim($_POST['address']??'')]);
    $bid=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO users(tenant_id,branch_id,name,email,password_hash,role,status,email_verified_at,created_at) VALUES(?,?,?,?,"owner","active",NOW(),NOW())')->execute([$tid,$bid,$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);
    $uid=(int)$pdo->lastInsertId();
    $plan=$pdo->query('SELECT id FROM plans WHERE slug="free" LIMIT 1')->fetchColumn();
    $pdo->prepare('INSERT INTO subscriptions(tenant_id,plan_id,status,billing_cycle,starts_at,trial_ends_at,created_at) VALUES(?,?,"trial","monthly",NOW(),DATE_ADD(NOW(),INTERVAL 14 DAY),NOW())')->execute([$tid,$plan]);
    foreach ([['Starters',1],['Mains',2],['Burgers & Pizza',3],['Drinks & Desserts',4]] as [$cn,$so]) $pdo->prepare('INSERT INTO categories(tenant_id,name,sort_order) VALUES(?,?,?)')->execute([$tid,$cn,$so]);
    foreach ([['T1',4],['T2',4],['T3',2],['T4',6]] as [$tn,$cap]) $pdo->prepare('INSERT INTO restaurant_tables(tenant_id,branch_id,name,capacity,status) VALUES(?,?,?,?,'."'available'".')')->execute([$tid,$bid,$tn,$cap]);
    $pdo->commit(); $_SESSION['user']=['id'=>$uid,'tenant_id'=>$tid,'branch_id'=>$bid,'name'=>$name,'email'=>$email,'role'=>'owner']; redirect('../dashboard.php');
  } catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); $error='Registration failed — email may already exist.'; } }
}
$t='Register'; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($t) ?> | RestoPro SaaS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="../assets/css/style.css" rel="stylesheet"></head>
<body class="d-flex align-items-center justify-content-center min-vh-100"><div class="card shadow p-4" style="max-width:460px;width:100%">
<h3 class="mb-3">Open your restaurant — free trial</h3><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<div class="mb-3"><label class="form-label">Restaurant name</label><input class="form-control" name="company" required></div>
<div class="mb-3"><label class="form-label">Main branch address</label><input class="form-control" name="address"></div>
<div class="mb-3"><label class="form-label">Owner name</label><input class="form-control" name="name" required></div>
<div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
<div class="mb-3"><label class="form-label">Password (min 8)</label><input class="form-control" type="password" name="password" required></div>
<button class="btn btn-primary w-100">Create restaurant account</button></form>
<p class="small mt-3 mb-0">Already registered? <a href="login.php">Login</a></p></div></body></html>
