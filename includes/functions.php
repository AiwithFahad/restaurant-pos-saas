<?php
declare(strict_types=1);
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) { http_response_code(419); exit('CSRF token mismatch.'); }
}
function flash(string $key, ?string $msg = null): ?string {
    if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return $msg; }
    $v = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $v;
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function tenant_id(): ?int { $u = current_user(); return isset($u['tenant_id']) && $u['tenant_id'] !== null ? (int)$u['tenant_id'] : null; }
function audit_log(PDO $pdo, string $action, string $entity = '', ?int $entityId = null): void {
    try {
        $u = current_user();
        $s = $pdo->prepare('INSERT INTO audit_logs(tenant_id,user_id,action,entity,entity_id,ip_address,created_at) VALUES(?,?,?,?,?,?,NOW())');
        $s->execute([$u['tenant_id'] ?? null, $u['id'] ?? null, $action, $entity, $entityId, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $ignored) {}
}
function log_activity(PDO $pdo, string $text): void {
    try { $s=$pdo->prepare('INSERT INTO activities(tenant_id,user_id,description,created_at) VALUES(?,?,?,NOW())'); $s->execute([tenant_id(), current_user()['id'] ?? null, $text]); } catch (Throwable $ignored) {}
}
function money($n): string { return '$' . number_format((float)$n, 2); }
function export_csv(array $rows, string $filename): void {
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    foreach ($rows as $i => $r) { if ($i === 0) fputcsv($out, array_keys($r)); fputcsv($out, $r); }
    fclose($out); exit;
}
function subscription_limits(PDO $pdo): array {
    $s = $pdo->prepare('SELECT p.* FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.tenant_id=? AND s.status IN ("trial","active") ORDER BY s.id DESC LIMIT 1');
    $s->execute([tenant_id()]); return $s->fetch() ?: ['name'=>'Free','max_users'=>5,'max_branches'=>1,'max_orders'=>500];
}

function branch_id(): ?int { $u = current_user(); return isset($u['branch_id']) && $u['branch_id'] !== null ? (int)$u['branch_id'] : null; }
function branch_scope(string $alias = ''): string { // SQL fragment: restrict to session branch when the user is branch-bound
    $b = branch_id(); if ($b === null) return '';
    $col = $alias !== '' ? $alias . '.branch_id' : 'branch_id';
    return ' AND ' . $col . ' = ' . $b;
}
function tenant_branches(PDO $pdo, int $tid): array { $s=$pdo->prepare('SELECT * FROM branches WHERE tenant_id=? ORDER BY id'); $s->execute([$tid]); return $s->fetchAll(); }
function plan_limit_reached(PDO $pdo, string $column, string $table): bool {
    $lim = subscription_limits($pdo); $max = (int)($lim[$column] ?? 0); if ($max <= 0) return false;
    $s = $pdo->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE tenant_id=?'); $s->execute([tenant_id()]);
    return (int)$s->fetchColumn() >= $max;
}
