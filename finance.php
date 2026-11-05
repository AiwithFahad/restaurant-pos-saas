<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['owner','branch_manager','accountant']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='finance'; $pageTitle='Finance — Expenses & Cash Closing'; $bid=branch_id();
function q1(PDO $pdo,string $sql,array $a){ $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchColumn(); }
function qa(PDO $pdo,string $sql,array $a): array { $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchAll(); }

$branches=tenant_branches($pdo,$tid);
if (is_post()) { verify_csrf();
  if(($_POST['action']??'')==='expense_save'){ if(!empty($_POST['id'])) $pdo->prepare('UPDATE expenses SET branch_id=?,title=?,category=?,amount=?,expense_date=? WHERE id=? AND tenant_id=?')->execute([(int)$_POST['branch_id'],trim($_POST['title']),trim($_POST['category']),(float)$_POST['amount'],$_POST['expense_date']?:date('Y-m-d'),(int)$_POST['id'],$tid]);
    else $pdo->prepare('INSERT INTO expenses(tenant_id,branch_id,title,category,amount,expense_date,created_at) VALUES(?,?,?,?,?,?,NOW())')->execute([$tid,(int)$_POST['branch_id'],trim($_POST['title']),trim($_POST['category']),(float)$_POST['amount'],$_POST['expense_date']?:date('Y-m-d')]); flash('success','Expense saved.'); }
  elseif(($_POST['action']??'')==='expense_delete'){ $pdo->prepare('DELETE FROM expenses WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); flash('success','Expense deleted.'); }
  redirect('finance.php'); }
$date=$_GET['date']??date('Y-m-d');
$closing=qa($pdo,'SELECT method, COALESCE(SUM(amount),0) total, COUNT(*) cnt FROM order_payments WHERE tenant_id=? AND DATE(created_at)=? AND status="paid" GROUP BY method',[$tid,$date]);
$daySales=q1($pdo,'SELECT COALESCE(SUM(total),0) FROM orders WHERE tenant_id=? AND DATE(created_at)=CURDATE() AND status NOT IN ("cancelled","refunded")',[$tid]);
$sales=q1($pdo,'SELECT COALESCE(SUM(total),0) FROM orders WHERE tenant_id=? AND status NOT IN ("cancelled","refunded")',[$tid]);
$exp=q1($pdo,'SELECT COALESCE(SUM(amount),0) FROM expenses WHERE tenant_id=?',[$tid]);
$purch=q1($pdo,'SELECT COALESCE(SUM(total),0) FROM purchase_orders WHERE tenant_id=? AND status="received"',[$tid]);
$expenses=qa($pdo,'SELECT e.*,b.name branch_name FROM expenses e LEFT JOIN branches b ON b.id=e.branch_id WHERE e.tenant_id=? ORDER BY e.expense_date DESC LIMIT 30',[$tid]);
$edit=null; if(!empty($_GET['edit'])){ $s=$pdo->prepare('SELECT * FROM expenses WHERE id=? AND tenant_id=?'); $s->execute([(int)$_GET['edit'],$tid]); $edit=$s->fetch(); }
include __DIR__ . '/includes/header.php'; ?>
<div class="row g-3 mb-1"><?php foreach([['Total sales (all time)',$sales],['Expenses',$exp],['Purchases (received)',$purch],['Profit / Loss',$sales-$exp-$purch],["Today's sales",$daySales]] as [$l,$v]): ?><div class="col-md-4 col-xl"><div class="card stat-card p-3"><div class="fs-5 fw-bold <?= $v<0?'text-danger':'' ?>"><?= money($v) ?></div><div class="text-muted small"><?= $l ?></div></div></div><?php endforeach; ?></div>
<div class="row g-3"><div class="col-lg-5"><div class="card p-3"><h6>Daily cash closing — <?= e($date) ?></h6>
<form class="d-flex gap-2 mb-3" method="get"><input class="form-control" type="date" name="date" value="<?= e($date) ?>"><button class="btn btn-outline-primary">View</button></form>
<table class="table mb-0"><tr><th>Payment method</th><th>Payments</th><th>Total</th></tr><?php $gt=0; foreach($closing as $r): $gt+=(float)$r['total']; ?><tr><td><?= e($r['method']) ?></td><td><?= $r['cnt'] ?></td><td><?= money($r['total']) ?></td></tr><?php endforeach; ?><tr class="fw-bold"><td>Total collected</td><td></td><td><?= money($gt) ?></td></tr></table></div></div>
<div class="col-lg-7"><div class="card p-3"><h6><?= $edit?'Edit':'Add' ?> expense</h6><form class="row g-2 mb-3" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="expense_save"><input type="hidden" name="id" value="<?= e($edit['id']??'') ?>">
<div class="col-md-3"><input class="form-control" name="title" placeholder="Title" value="<?= e($edit['title']??'') ?>" required></div>
<div class="col-md-2"><input class="form-control" name="category" placeholder="Category" value="<?= e($edit['category']??'general') ?>"></div>
<div class="col-md-2"><input class="form-control" type="number" step="0.01" name="amount" placeholder="Amount" value="<?= e($edit['amount']??'') ?>" required></div>
<div class="col-md-2"><input class="form-control" type="date" name="expense_date" value="<?= e($edit['expense_date']??date('Y-m-d')) ?>"></div>
<div class="col-md-2"><select class="form-select" name="branch_id"><?php foreach($branches as $b): ?><option value="<?= $b['id'] ?>" <?= ($edit['branch_id']??$bid)==$b['id']?'selected':'' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><button class="btn btn-primary w-100">Save</button></div></form>
<table class="table table-sm mb-0"><tr><th>Expense</th><th>Branch</th><th>Date</th><th>Amount</th><th></th></tr><?php foreach($expenses as $r): ?><tr><td><?= e($r['title']) ?> <span class="badge bg-secondary"><?= e($r['category']) ?></span></td><td><?= e($r['branch_name']??'') ?></td><td><?= e($r['expense_date']) ?></td><td><?= money($r['amount']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="?edit=<?= $r['id'] ?>">Edit</a> <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="expense_delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; ?></table></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
