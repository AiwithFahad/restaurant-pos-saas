<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['owner','branch_manager','cashier','waiter','kitchen','inventory_manager','accountant']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='orders'; $pageTitle='Orders'; $bid=branch_id();
function q1(PDO $pdo,string $sql,array $a){ $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchColumn(); }
function qa(PDO $pdo,string $sql,array $a): array { $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchAll(); }

if (is_post()) { verify_csrf(); $id=(int)$_POST['id']; $act=$_POST['action']??'';
  $s=$pdo->prepare('SELECT * FROM orders WHERE id=? AND tenant_id=?'); $s->execute([$id,$tid]); $o=$s->fetch(); if($o){
   if ($act==='status' && in_array($_POST['status'], ['pending','preparing','ready','served','completed','cancelled'], true)) {
      try{ $pdo->beginTransaction(); $pdo->prepare('UPDATE orders SET status=? WHERE id=? AND tenant_id=?')->execute([$_POST['status'],$id,$tid]);
        $pdo->prepare('UPDATE kitchen_tickets SET status=? WHERE order_id=? AND tenant_id=?')->execute([$_POST['status']==='completed'||$_POST['status']==='served'?'completed':$_POST['status'],$id,$tid]);
        if (in_array($_POST['status'],['completed','cancelled'],true) && $o['table_id']) $pdo->prepare('UPDATE restaurant_tables SET status="available" WHERE id=? AND tenant_id=?')->execute([$o['table_id'],$tid]);
        $pdo->commit(); flash('success','Order status updated.'); }catch(Throwable $ex){ $pdo->rollBack(); flash('error','Update failed.'); }
   } elseif ($act==='refund') {
      try{ $pdo->beginTransaction();
        $items=qa($pdo,'SELECT * FROM order_items WHERE order_id=? AND tenant_id=?',[$id,$tid]);
        foreach($items as $it){ $pdo->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=? AND tenant_id=? AND track_stock=1')->execute([$it['quantity'],$it['product_id'],$tid]);
          $ig=$pdo->prepare('SELECT ingredient_id,quantity FROM product_ingredients WHERE product_id=? AND tenant_id=?'); $ig->execute([$it['product_id'],$tid]);
          foreach($ig->fetchAll() as $g) $pdo->prepare('UPDATE ingredients SET stock_quantity=stock_quantity+? WHERE id=? AND tenant_id=?')->execute([(float)$g['quantity']*$it['quantity'],$g['ingredient_id'],$tid]); }
        $pdo->prepare('UPDATE orders SET status="refunded", payment_status="refunded" WHERE id=? AND tenant_id=?')->execute([$id,$tid]);
        $pdo->prepare('INSERT INTO order_payments(tenant_id,order_id,method,amount,status,created_at) VALUES(?,?,"refund",?,"refunded",NOW())')->execute([$tid,$id,-(float)$o['total']]);
        if($o['table_id']) $pdo->prepare('UPDATE restaurant_tables SET status="available" WHERE id=? AND tenant_id=?')->execute([$o['table_id'],$tid]);
        $pdo->commit(); log_activity($pdo,'Order '.$o['order_number'].' refunded'); flash('success','Order refunded — stock restored.'); }catch(Throwable $ex){ $pdo->rollBack(); flash('error','Refund failed.'); }
   } }
  redirect('orders.php'); }
$status=$_GET['status']??''; $sql='SELECT o.*,t.name table_name,c.name customer_name FROM orders o LEFT JOIN restaurant_tables t ON t.id=o.table_id LEFT JOIN customers c ON c.id=o.customer_id WHERE o.tenant_id=?'; $a=[$tid];
if($status){ $sql.=' AND o.status=?'; $a[]=$status; } if($bid){ $sql.=' AND o.branch_id=?'; $a[]=$bid; }
$rows=qa($pdo,$sql.' ORDER BY o.id DESC LIMIT 100',$a);
include __DIR__ . '/includes/header.php'; ?>
<form class="mb-3" method="get"><div class="row g-2"><div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option><?php foreach(['pending','preparing','ready','served','completed','cancelled','refunded'] as $st): ?><option <?= $status===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div><div class="col-md-2"><a class="btn btn-success w-100" href="pos.php">New order (POS)</a></div></div></form>
<div class="card p-0 overflow-auto"><table class="table mb-0"><tr><th>Order</th><th>Type / Table</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th>Date</th><th>Actions</th></tr>
<?php foreach($rows as $r): ?><tr><td><strong><?= e($r['order_number']) ?></strong></td><td><?= e($r['order_type']) ?><?= $r['table_name']?' · '.e($r['table_name']):'' ?></td><td><?= e($r['customer_name']??'Walk-in') ?></td>
<td><form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $r['id'] ?>"><select class="form-select form-select-sm" name="status"><?php foreach(['pending','preparing','ready','served','completed','cancelled'] as $st): ?><option <?= $r['status']===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-primary">Set</button></form></td>
<td><?= e($r['payment_status']) ?></td><td><?= money($r['total']) ?></td><td class="small"><?= e($r['created_at']) ?></td>
<td class="text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="receipt.php?id=<?= $r['id'] ?>" target="_blank">Receipt</a>
<?php if(!in_array($r['status'],['refunded','cancelled'],true)): ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="refund"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Refund this order and restore stock?">Refund</button></form><?php endif; ?></td></tr><?php endforeach; if(!$rows) echo '<tr><td colspan="8" class="text-muted p-3">No orders yet.</td></tr>'; ?></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
