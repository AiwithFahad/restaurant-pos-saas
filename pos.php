<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['owner','branch_manager','cashier','waiter']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='pos'; $pageTitle='POS Billing'; $bid=branch_id();
function q1(PDO $pdo,string $sql,array $a){ $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchColumn(); }
function qa(PDO $pdo,string $sql,array $a): array { $s=$pdo->prepare($sql); $s->execute($a); return $s->fetchAll(); }

$cats = qa($pdo,'SELECT * FROM categories WHERE tenant_id=? ORDER BY sort_order',[$tid]);
$prods = qa($pdo,'SELECT p.*,c.name cat_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.tenant_id=? AND p.is_available=1 ORDER BY p.name',[$tid]);
$tables = qa($pdo,'SELECT * FROM restaurant_tables WHERE tenant_id=?'.($bid?' AND branch_id='.(int)$bid:'').' ORDER BY name',[$tid]);
$customers = qa($pdo,'SELECT * FROM customers WHERE tenant_id=? ORDER BY name',[$tid]);
$branches = tenant_branches($pdo,$tid);
include __DIR__ . '/includes/header.php';
$foodMap = ['burger'=>'photo-1568901346375-23c9450c58cd','zinger'=>'photo-1568901346375-23c9450c58cd','sandwich'=>'photo-1568901346375-23c9450c58cd','pizza'=>'photo-1513104890138-7c749659a591','salad'=>'photo-1512621776951-a57141f2eefd','caesar'=>'photo-1512621776951-a57141f2eefd','sushi'=>'photo-1579871494447-9811cf80d66c','roll'=>'photo-1579871494447-9811cf80d66c','pasta'=>'photo-1621996346565-e3dbc646d9a9','noodle'=>'photo-1621996346565-e3dbc646d9a9','spaghetti'=>'photo-1621996346565-e3dbc646d9a9','coffee'=>'photo-1509042239860-f550ce710b93','tea'=>'photo-1509042239860-f550ce710b93','steak'=>'photo-1546964124-0cce460f38ef','grill'=>'photo-1546964124-0cce460f38ef','chicken'=>'photo-1546964124-0cce460f38ef','biryani'=>'photo-1546964124-0cce460f38ef','kebab'=>'photo-1546964124-0cce460f38ef','cake'=>'photo-1551024506-0bccd828d307','dessert'=>'photo-1551024506-0bccd828d307','lava'=>'photo-1551024506-0bccd828d307','sweet'=>'photo-1551024506-0bccd828d307'];
$foodDefault = 'photo-1504674900247-0877df9cc836'; ?>
<div class="row g-3">
<div class="col-lg-8"><div class="card p-3">
<div class="d-flex gap-2 mb-3"><input class="form-control" id="posSearch" placeholder="Search products…"><select class="form-select w-auto" id="posCat"><option value="">All categories</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="row g-2" id="productGrid">
<?php foreach($prods as $p): ?><div class="col-6 col-md-4 col-xl-3 prod" data-cat="<?= $p['category_id'] ?>" data-name="<?= e(strtolower($p['name'])) ?>">
<button type="button" class="btn btn-outline-secondary prod-card-btn w-100 h-100 text-start" onclick="addToCart(<?= $p['id'] ?>,<?= json_encode($p['name']) ?>,<?= (float)$p['price'] ?>)">
<?php $hay = strtolower(($p['cat_name'] ?? '') . ' ' . $p['name']); $pimg = $p['image_url'] ?? ''; if (!$pimg) { $ph = $foodDefault; foreach ($foodMap as $kw => $u) { if (str_contains($hay, $kw)) { $ph = $u; break; } } $pimg = 'https://images.unsplash.com/' . $ph . '?auto=format&fit=crop&w=600&q=60'; } ?>
<span class="pos-img"><img src="<?= e($pimg) ?>" loading="lazy" onerror="this.remove()" alt=""></span>
<span class="pos-card-body"><span class="fw-semibold text-body d-block"><?= e($p['name']) ?></span><span class="small text-muted d-block"><?= e($p['cat_name'] ?? '') ?><?= $p['is_combo']?' · Combo':'' ?></span><span class="pos-price d-block"><?= money($p['price']) ?></span>
<span class="small d-block <?= $p['track_stock'] && $p['stock_quantity']<=0?'text-danger':'text-muted' ?>"><?= $p['track_stock']?('Stock: '.$p['stock_quantity']):'Always available' ?></span></span></button></div><?php endforeach; ?>
</div></div></div>
<div class="col-lg-4"><div class="card p-3 sticky-top" style="top:1rem"><h6><i class="bi bi-cart3"></i> Current order</h6>
<div class="row g-2 mb-2"><div class="col-6"><select class="form-select form-select-sm" id="orderType"><option value="dine-in">Dine-in</option><option value="takeaway">Takeaway</option><option value="delivery">Delivery</option></select></div>
<div class="col-6"><select class="form-select form-select-sm" id="tableId"><option value="">No table</option><?php foreach($tables as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?> (<?= e($t['status']) ?>)</option><?php endforeach; ?></select></div>
<div class="col-6"><select class="form-select form-select-sm" id="customerId"><option value="">Walk-in customer</option><?php foreach($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-6"><select class="form-select form-select-sm" id="branchId"><?php foreach($branches as $b): ?><option value="<?= $b['id'] ?>" <?= $bid==$b['id']?'selected':'' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></div></div>
<div id="cartItems" class="small mb-2"><p class="text-muted mb-0">Cart is empty — click a product.</p></div>
<div class="row g-2 small">
<div class="col-6">Item discount %<input class="form-control form-control-sm" type="number" id="itemDisc" value="0" min="0" max="100" oninput="renderCart()"></div>
<div class="col-6">Order discount $<input class="form-control form-control-sm" type="number" id="orderDisc" value="0" min="0" step="0.01" oninput="renderCart()"></div>
<div class="col-6">Tax %<input class="form-control form-control-sm" type="number" id="taxRate" value="5" min="0" max="40" oninput="renderCart()"></div>
<div class="col-6">Payment<select class="form-select form-select-sm" id="payMethod" onchange="document.getElementById('splitBox').style.display=this.value==='split'?'flex':'none'"><option>cash</option><option>card</option><option>split</option><option>online</option></select></div>
<div class="col-12 row g-2" id="splitBox" style="display:none"><div class="col-6">Cash $<input class="form-control form-control-sm" type="number" id="splitCash" value="0" step="0.01"></div><div class="col-6">Card $<input class="form-control form-control-sm" type="number" id="splitCard" value="0" step="0.01"></div></div></div>
<hr><div class="small" id="cartTotals"></div>
<button class="btn btn-success w-100 mt-2" id="placeBtn" onclick="placeOrder()"><i class="bi bi-check2-circle"></i> Place Order</button>
<div id="posMsg" class="small mt-2"></div>
</div></div></div>
<script>
let cart={};
function addToCart(id,name,price){ cart[id]=cart[id]||{id,name,price,qty:0}; cart[id].qty++; renderCart(); }
function chQty(id,d){ cart[id].qty+=d; if(cart[id].qty<=0) delete cart[id]; renderCart(); }
function totals(){ let sub=0; for(const k in cart) sub+=cart[k].price*cart[k].qty; const idisc=sub*(parseFloat(document.getElementById('itemDisc').value)||0)/100; const odisc=parseFloat(document.getElementById('orderDisc').value)||0; const tax=(sub-idisc-odisc)*(parseFloat(document.getElementById('taxRate').value)||0)/100; return {sub,idisc,odisc,tax,total:Math.max(0,sub-idisc-odisc+tax)}; }
function renderCart(){ const box=document.getElementById('cartItems'); const keys=Object.keys(cart); if(!keys.length){ box.innerHTML='<p class="text-muted mb-0">Cart is empty — click a product.</p>'; } else { box.innerHTML=keys.map(k=>{const i=cart[k];return `<div class="d-flex justify-content-between align-items-center border-bottom py-1"><span>${i.name} <span class="text-muted">× ${i.qty}</span></span><span>$${(i.price*i.qty).toFixed(2)} <button class="btn btn-sm btn-outline-secondary py-0" onclick="chQty(${k},-1)">−</button> <button class="btn btn-sm btn-outline-secondary py-0" onclick="chQty(${k},1)">+</button></span></div>`}).join(''); }
 const t=totals(); document.getElementById('cartTotals').innerHTML=`Subtotal $${t.sub.toFixed(2)}<br>Discounts −$${(t.idisc+t.odisc).toFixed(2)}<br>Tax $${t.tax.toFixed(2)}<br><strong class="fs-6">Total $${t.total.toFixed(2)}</strong>`; }
async function placeOrder(){ const keys=Object.keys(cart); if(!keys.length){ alert('Cart is empty'); return; }
 const t=totals(); const method=document.getElementById('payMethod').value; let payments=null;
 if(method==='split'){ payments=[{method:'cash',amount:parseFloat(document.getElementById('splitCash').value)||0},{method:'card',amount:parseFloat(document.getElementById('splitCard').value)||0}].filter(p=>p.amount>0); }
 const body={_csrf:<?= json_encode(csrf_token()) ?>, items:keys.map(k=>({product_id:cart[k].id,quantity:cart[k].qty})), order_type:document.getElementById('orderType').value, table_id:document.getElementById('tableId').value||null, customer_id:document.getElementById('customerId').value||null, branch_id:document.getElementById('branchId').value, item_discount_percent:parseFloat(document.getElementById('itemDisc').value)||0, order_discount:parseFloat(document.getElementById('orderDisc').value)||0, tax_rate:parseFloat(document.getElementById('taxRate').value)||0, payment_method:method, payments};
 const btn=document.getElementById('placeBtn'); btn.disabled=true;
 try{ const r=await fetch('api/place_order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}); const j=await r.json();
  if(j.ok){ cart={}; renderCart(); document.getElementById('posMsg').innerHTML=`<div class="alert alert-success py-2">Order <strong>${j.order_number}</strong> placed. <a href="receipt.php?id=${j.order_id}" target="_blank">Print receipt</a></div>`; }
  else document.getElementById('posMsg').innerHTML=`<div class="alert alert-danger py-2">${j.error||'Failed'}</div>`;
 }catch(e){ document.getElementById('posMsg').innerHTML='<div class="alert alert-danger py-2">Network error</div>'; } btn.disabled=false; }
document.getElementById('posSearch').addEventListener('input',e=>{ const v=e.target.value.toLowerCase(); document.querySelectorAll('.prod').forEach(el=>el.style.display=el.dataset.name.includes(v)?'':'none'); });
document.getElementById('posCat').addEventListener('change',e=>{ const v=e.target.value; document.querySelectorAll('.prod').forEach(el=>el.style.display=(!v||el.dataset.cat===v)?'':'none'); });
renderCart();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
