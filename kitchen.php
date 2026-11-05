<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['owner','branch_manager','kitchen','cashier']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='kitchen'; $pageTitle='Kitchen Display System'; $bid=branch_id();
function q1(PDO $pdo,string $sql,array $a){ $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchColumn(); }
function qa(PDO $pdo,string $sql,array $a): array { $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchAll(); }

if (is_post()) { verify_csrf(); $id=(int)$_POST['id']; $st=$_POST['status']==='ready'?'ready':'preparing';
  $pdo->beginTransaction(); $pdo->prepare('UPDATE orders SET status=? WHERE id=? AND tenant_id=? AND status IN ("pending","preparing")')->execute([$st,$id,$tid]);
  $pdo->prepare('UPDATE kitchen_tickets SET status=?, started_at=IF(?="preparing",COALESCE(started_at,NOW()),started_at), ready_at=IF(?="ready",NOW(),ready_at) WHERE order_id=? AND tenant_id=?')->execute([$st,$st,$st,$id,$tid]); $pdo->commit(); redirect('kitchen.php'); }
include __DIR__ . '/includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3"><span class="text-muted small">Live view — auto-refreshes every 15 seconds.</span><a class="btn btn-sm btn-outline-primary" href="kitchen.php">Refresh now</a></div>
<div class="row g-3" id="kds"><div class="col-12 text-muted">Loading kitchen orders…</div></div>
<script>
const csrf=<?= json_encode(csrf_token()) ?>;
async function loadKds(){ try{ const r=await fetch('api/kitchen_orders.php'); const rows=await r.json();
 document.getElementById('kds').innerHTML = rows.length ? rows.map(o=>`<div class="col-md-4 col-xl-3"><div class="card p-3 h-100 border-${o.status==='ready'?'success':o.status==='preparing'?'warning':'secondary'} border-2"><div class="d-flex justify-content-between"><strong>${o.order_number}</strong><span class="badge bg-${o.status==='ready'?'success':o.status==='preparing'?'warning':'secondary'}">${o.status}</span></div><div class="small text-muted">${o.order_type}${o.table_name?' · Table '+o.table_name:''} · ${o.created_at}</div><ul class="small mt-2 mb-3">${o.items.map(i=>`<li>${i.product_name} × ${i.quantity}</li>`).join('')}</ul><form method="post" class="mt-auto"><input type="hidden" name="_csrf" value="${csrf}"><input type="hidden" name="id" value="${o.id}">${o.status==='pending'?`<button class="btn btn-warning btn-sm w-100" name="status" value="preparing">Start cooking</button>`:o.status==='preparing'?`<button class="btn btn-success btn-sm w-100" name="status" value="ready">Mark ready</button>`:'<span class="badge bg-success w-100 py-2">Ready for serving</span>'}</form></div></div>`).join('') : '<div class="col-12"><div class="card p-4 text-center text-muted">No pending kitchen orders.</div></div>';
 }catch(e){} }
loadKds(); setInterval(loadKds,15000);
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
