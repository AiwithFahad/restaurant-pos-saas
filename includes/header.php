<?php $b = $base ?? ''; $cu = function_exists('current_user') ? current_user() : null; $cuName = $cu['name'] ?? ''; $cuIni = ''; foreach (preg_split('/\s+/', trim($cuName)) as $w) { if ($w !== '') $cuIni .= strtoupper($w[0]); if (strlen($cuIni) >= 2) break; } ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? APP_NAME) ?> | <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= $b ?>assets/css/style.css" rel="stylesheet"></head>
<body><script>try{if(localStorage.getItem('resto-theme')==='dark'){document.body.classList.add('dark');document.documentElement.dataset.theme='dark';}if(localStorage.getItem('resto-sb')==='1')document.body.classList.add('sb-collapsed');}catch(e){}</script><div class="layout"><?php include __DIR__ . '/sidebar.php'; ?><main class="main p-4">
<header class="topbar mb-4">
<button class="icon-btn" id="sidebarToggle" type="button" title="Collapse sidebar" aria-label="Collapse sidebar"><i class="bi bi-layout-sidebar-inset"></i></button>
<h1 class="page-title"><?= e($pageTitle ?? '') ?></h1>
<form class="global-search" method="get" action="<?= $b ?>menu.php" role="search"><i class="bi bi-search"></i><input type="search" name="q" placeholder="Search menu, products…" aria-label="Global search"></form>
<div class="ms-auto d-flex align-items-center gap-2">
<div class="dropdown"><button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-label="Notifications"><i class="bi bi-bell"></i><span class="dot"></span></button>
<ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:260px">
<li><a class="dropdown-item rounded py-2" href="<?= $b ?>kitchen.php"><i class="bi bi-fire text-primary me-2"></i>Kitchen — live orders waiting</a></li>
<li><a class="dropdown-item rounded py-2" href="<?= $b ?>orders.php"><i class="bi bi-receipt text-primary me-2"></i>Orders — track &amp; update status</a></li>
<li><a class="dropdown-item rounded py-2" href="<?= $b ?>inventory.php"><i class="bi bi-boxes text-primary me-2"></i>Inventory — check low stock</a></li>
</ul></div>
<button class="icon-btn" id="themeToggle" type="button" title="Toggle light / dark theme" aria-label="Toggle theme"><i class="bi bi-moon"></i></button>
<div class="topbar-user"><span class="avatar-circle"><?= e($cuIni !== '' ? $cuIni : 'R') ?></span><span class="u-meta"><span class="u-name d-block"><?= e($cuName) ?></span><span class="u-role d-block"><?= e(str_replace('_',' ', $cu['role'] ?? '')) ?></span></span></div>
</div></header>
<?php if ($m = flash('success')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('error')): ?><div class="alert alert-danger"><?= e($m) ?></div><?php endif; ?>
