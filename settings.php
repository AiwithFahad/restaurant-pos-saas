<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['owner','branch_manager','cashier','waiter','kitchen','inventory_manager','accountant']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='settings'; $pageTitle='Settings'; $bid=branch_id();
function q1(PDO $pdo,string $sql,array $a){ $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchColumn(); }
function qa(PDO $pdo,string $sql,array $a): array { $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchAll(); }

require_role(['owner']);
if (is_post()) { verify_csrf();
  if(($_POST['action']??'')==='profile'){ $pdo->prepare('UPDATE users SET name=? WHERE id=?')->execute([trim($_POST['name']??''),$user['id']]); $_SESSION['user']['name']=trim($_POST['name']??''); flash('success','Profile updated.'); }
  elseif(($_POST['action']??'')==='password'){ if(!password_verify($_POST['current']??'', $pdo->query('SELECT password_hash FROM users WHERE id='.(int)$user['id'])->fetchColumn())) flash('error','Current password is wrong.');
    else { $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($_POST['new'],PASSWORD_DEFAULT),$user['id']]); flash('success','Password changed.'); } }
  elseif(($_POST['action']??'')==='ticket'){ $pdo->prepare('INSERT INTO support_tickets(tenant_id,user_id,subject,message,status,created_at) VALUES(?,?,?, ?,"open",NOW())')->execute([$tid,$user['id'],trim($_POST['subject']??''),trim($_POST['message']??'')]); flash('success','Support ticket created.'); }
  else { foreach(['restaurant_name','currency','tax_rate','timezone','receipt_footer'] as $k){ $pdo->prepare('INSERT INTO settings(tenant_id,setting_key,setting_value) VALUES(?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$tid,$k,$_POST[$k]??'']); } flash('success','Settings saved.'); }
  redirect('settings.php'); }
$s=$pdo->prepare('SELECT setting_key,setting_value FROM settings WHERE tenant_id=?'); $s->execute([$tid]); $cfg=[]; foreach($s->fetchAll() as $r) $cfg[$r['setting_key']]=$r['setting_value'];
$tickets=qa($pdo,'SELECT * FROM support_tickets WHERE tenant_id=? ORDER BY id DESC LIMIT 10',[$tid]);
$ann=qa($pdo,'SELECT * FROM announcements WHERE is_active=1 ORDER BY id DESC LIMIT 3',[]);
include __DIR__ . '/includes/header.php'; ?>
<?php foreach($ann as $a): ?><div class="alert alert-info"><strong><?= e($a['title']) ?></strong> — <?= e($a['body']) ?></div><?php endforeach; ?>
<div class="row g-3"><div class="col-lg-6"><div class="card p-3"><h6>Restaurant</h6><form method="post"><?= csrf_field() ?>
<div class="mb-2"><label class="form-label">Restaurant name</label><input class="form-control" name="restaurant_name" value="<?= e($cfg['restaurant_name']??'') ?>"></div>
<div class="mb-2"><label class="form-label">Currency</label><input class="form-control" name="currency" value="<?= e($cfg['currency']??'USD') ?>"></div>
<div class="mb-2"><label class="form-label">Default tax %</label><input class="form-control" name="tax_rate" value="<?= e($cfg['tax_rate']??'5') ?>"></div>
<div class="mb-2"><label class="form-label">Timezone</label><input class="form-control" name="timezone" value="<?= e($cfg['timezone']??'Asia/Karachi') ?>"></div>
<div class="mb-2"><label class="form-label">Receipt footer</label><input class="form-control" name="receipt_footer" value="<?= e($cfg['receipt_footer']??'Thank you for dining with us!') ?>"></div>
<button class="btn btn-primary">Save</button></form></div>
<div class="card p-3 mt-3"><h6>Change password</h6><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password"><input class="form-control mb-2" type="password" name="current" placeholder="Current password" required><input class="form-control mb-2" type="password" name="new" placeholder="New password (min 8)" required><button class="btn btn-outline-primary">Change password</button></form></div></div>
<div class="col-lg-6"><div class="card p-3"><h6>Profile</h6><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="profile"><input class="form-control mb-2" name="name" value="<?= e($user['name']) ?>"><div class="text-muted small mb-2"><?= e($user['email']) ?> · <?= e(str_replace('_',' ',$user['role'])) ?></div><button class="btn btn-primary">Update profile</button></form></div>
<div class="card p-3 mt-3"><h6>Support</h6><form method="post" class="mb-3"><?= csrf_field() ?><input type="hidden" name="action" value="ticket"><input class="form-control mb-2" name="subject" placeholder="Subject" required><textarea class="form-control mb-2" name="message" placeholder="How can we help?" required></textarea><button class="btn btn-outline-primary">Open ticket</button></form>
<?php foreach($tickets as $t): ?><div class="border-bottom py-2"><strong><?= e($t['subject']) ?></strong> <span class="badge bg-secondary"><?= e($t['status']) ?></span></div><?php endforeach; if(!$tickets) echo '<p class="text-muted small mb-0">No tickets yet.</p>'; ?></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
