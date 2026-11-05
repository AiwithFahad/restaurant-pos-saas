<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
$user = current_user(); if (!$user || !in_array($user['role'], ROLE_KITCHEN, true)) { http_response_code(403); echo json_encode([]); exit; }
$pdo=db(); $tid=require_tenant(); $bid=branch_id();
$sql='SELECT o.id,o.order_number,o.order_type,o.status,o.created_at,t.name table_name FROM orders o LEFT JOIN restaurant_tables t ON t.id=o.table_id WHERE o.tenant_id=? AND o.status IN ("pending","preparing","ready")'; $a=[$tid];
if ($bid) { $sql.=' AND o.branch_id=?'; $a[]=$bid; }
$s=$pdo->prepare($sql.' ORDER BY o.id ASC'); $s->execute($a); $orders=$s->fetchAll();
foreach ($orders as &$o) { $i=$pdo->prepare('SELECT product_name,quantity FROM order_items WHERE order_id=?'); $i->execute([$o['id']]); $o['items']=$i->fetchAll(); }
echo json_encode($orders);
