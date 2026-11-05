<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
$user = current_user();
if (!$user || !in_array($user['role'], ROLE_POS, true)) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Not authorised for POS.']); exit; }
$pdo = db(); $tid = require_tenant();
$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (!hash_equals($_SESSION['csrf'] ?? '', (string)($in['_csrf'] ?? ''))) { http_response_code(419); echo json_encode(['ok'=>false,'error'=>'CSRF mismatch']); exit; }
$items = $in['items'] ?? [];
if (!$items) { echo json_encode(['ok'=>false,'error'=>'Cart is empty']); exit; }
$branchId = (int)($in['branch_id'] ?? 0) ?: branch_id();
if (!$branchId) { $branchId = (int)q_branch($pdo,$tid); }
$tableId = !empty($in['table_id']) ? (int)$in['table_id'] : null;
$customerId = !empty($in['customer_id']) ? (int)$in['customer_id'] : null;
$itemDiscPct = max(0.0, min(100.0, (float)($in['item_discount_percent'] ?? 0)));
$orderDisc = max(0.0, (float)($in['order_discount'] ?? 0));
$taxRate = max(0.0, min(40.0, (float)($in['tax_rate'] ?? 0)));
function q_branch(PDO $pdo, int $tid) { $s=$pdo->prepare('SELECT id FROM branches WHERE tenant_id=? ORDER BY is_main DESC, id LIMIT 1'); $s->execute([$tid]); return $s->fetchColumn(); }
try {
    $pdo->beginTransaction();
    // Validate branch / table / customer belong to tenant
    $s=$pdo->prepare('SELECT id FROM branches WHERE id=? AND tenant_id=?'); $s->execute([$branchId,$tid]); if(!$s->fetchColumn()) throw new RuntimeException('Invalid branch.');
    if ($tableId) { $s=$pdo->prepare('SELECT id FROM restaurant_tables WHERE id=? AND tenant_id=?'); $s->execute([$tableId,$tid]); if(!$s->fetchColumn()) throw new RuntimeException('Invalid table.'); }
    if ($customerId) { $s=$pdo->prepare('SELECT id FROM customers WHERE id=? AND tenant_id=?'); $s->execute([$customerId,$tid]); if(!$s->fetchColumn()) throw new RuntimeException('Invalid customer.'); }
    // Load products (server-side prices — never trust client totals)
    $lines=[]; $subtotal=0.0;
    $ps=$pdo->prepare('SELECT * FROM products WHERE id=? AND tenant_id=? AND is_available=1 FOR UPDATE');
    foreach ($items as $it) { $pid=(int)($it['product_id']??0); $qty=max(1,(int)($it['quantity']??1)); $ps->execute([$pid]); $p=$ps->fetch(); if(!$p) throw new RuntimeException('Product not available: '.$pid);
        if ((int)$p['track_stock']===1 && (float)$p['stock_quantity'] < $qty) throw new RuntimeException('Insufficient stock for '.$p['name']);
        $lt=(float)$p['price']*$qty; $subtotal+=$lt; $lines[]=['p'=>$p,'qty'=>$qty,'lt'=>$lt]; }
    $discount = $subtotal*$itemDiscPct/100 + $orderDisc;
    $tax = max(0,$subtotal-$discount)*$taxRate/100;
    $total = max(0,$subtotal-$discount+$tax);
    $orderNo = 'ORD-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
    $pdo->prepare('INSERT INTO orders(tenant_id,branch_id,table_id,customer_id,user_id,order_number,order_type,status,payment_status,subtotal,discount,tax_rate,tax_amount,total,created_at) VALUES(?,?,?,?,?,?,?,"pending","paid",?,?,?,?,?,NOW())')
        ->execute([$tid,$branchId,$tableId,$customerId,$user['id'],$orderNo,in_array($in['order_type']??'', ['dine-in','takeaway','delivery'], true)?$in['order_type']:'dine-in',$subtotal,$discount,$taxRate,$tax,$total]);
    $oid=(int)$pdo->lastInsertId();
    $oi=$pdo->prepare('INSERT INTO order_items(tenant_id,order_id,product_id,product_name,quantity,unit_price,line_total) VALUES(?,?,?,?,?,?,?)');
    $decP=$pdo->prepare('UPDATE products SET stock_quantity=stock_quantity-? WHERE id=? AND tenant_id=?');
    $ingQ=$pdo->prepare('SELECT ingredient_id,quantity FROM product_ingredients WHERE product_id=? AND tenant_id=?');
    $decI=$pdo->prepare('UPDATE ingredients SET stock_quantity=stock_quantity-? WHERE id=? AND tenant_id=?');
    $mov=$pdo->prepare('INSERT INTO stock_movements(tenant_id,branch_id,product_id,ingredient_id,type,quantity,reference,note,created_by,created_at) VALUES(?,?,?,?,"out",?,?,?, ?,NOW())');
    foreach ($lines as $ln) { $oi->execute([$tid,$oid,$ln['p']['id'],$ln['p']['name'],$ln['qty'],$ln['p']['price'],$ln['lt']]);
        if ((int)$ln['p']['track_stock']===1) { $decP->execute([$ln['qty'],$ln['p']['id'],$tid]); $mov->execute([$tid,$branchId,$ln['p']['id'],null,-$ln['qty'],'order #'.$oid,'POS sale '.$orderNo,$user['id']]); }
        $ingQ->execute([$ln['p']['id'],$tid]); foreach ($ingQ->fetchAll() as $ing) { $use=(float)$ing['quantity']*$ln['qty']; $decI->execute([$use,$ing['ingredient_id'],$tid]); $mov->execute([$tid,$branchId,null,$ing['ingredient_id'],-$use,'order #'.$oid,'Ingredient use '.$orderNo,$user['id']]); } }
    // Payments (single method or validated split)
    $op=$pdo->prepare('INSERT INTO order_payments(tenant_id,order_id,method,amount,status,created_at) VALUES(?,?,?, ?,"paid",NOW())');
    $pays = $in['payments'] ?? null;
    if (($in['payment_method'] ?? 'cash')==='split' && is_array($pays) && $pays) { $sum=0; foreach($pays as $pay){ $amt=(float)($pay['amount']??0); if($amt>0){ $op->execute([$tid,$oid,in_array($pay['method']??'', ['cash','card','online'], true)?$pay['method']:'cash',$amt]); $sum+=$amt; } } if (abs($sum-$total)>0.51) throw new RuntimeException('Split payments must equal the order total.'); }
    else $op->execute([$tid,$oid,in_array($in['payment_method']??'', ['cash','card','online'], true)?$in['payment_method']:'cash',$total]);
    $pdo->prepare('INSERT INTO kitchen_tickets(tenant_id,branch_id,order_id,status,created_at) VALUES(?,?,?,"pending",NOW())')->execute([$tid,$branchId,$oid]);
    if ($tableId) $pdo->prepare('UPDATE restaurant_tables SET status="occupied" WHERE id=? AND tenant_id=?')->execute([$tableId,$tid]);
    if ($customerId) { $pts=(int)floor($total); $pdo->prepare('UPDATE customers SET loyalty_points=loyalty_points+?, total_spent=total_spent+?, visits=visits+1 WHERE id=? AND tenant_id=?')->execute([$pts,$total,$customerId,$tid]); }
    log_activity($pdo, 'POS order '.$orderNo.' placed ('.money($total).')');
    audit_log($pdo,'pos_order','orders',$oid);
    $pdo->commit();
    echo json_encode(['ok'=>true,'order_id'=>$oid,'order_number'=>$orderNo,'total'=>$total]);
} catch (Throwable $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); echo json_encode(['ok'=>false,'error'=>$ex->getMessage()]); }
