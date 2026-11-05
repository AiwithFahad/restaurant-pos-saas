<?php $b = $base ?? ''; ?>
<nav class="sidebar d-flex flex-column p-3">
  <a class="brand text-white text-decoration-none fs-5 fw-bold mb-4" href="<?= $b ?>dashboard.php"><i class="bi bi-shop"></i> RestoPro</a>
  <?php foreach ([['dashboard','dashboard.php','bi-speedometer2','Dashboard'],['pos','pos.php','bi-calculator','POS Billing'],['orders','orders.php','bi-receipt','Orders'],['kitchen','kitchen.php','bi-fire','Kitchen Display'],['tables','tables.php','bi-grid-3x3','Tables'],['menu','menu.php','bi-journal-richtext','Menu'],['inventory','inventory.php','bi-boxes','Inventory'],['customers','customers.php','bi-people','Customers'],['staff','staff.php','bi-person-badge','Staff'],['branches','branches.php','bi-buildings','Branches'],['finance','finance.php','bi-wallet2','Finance'],['reports','reports.php','bi-bar-chart','Reports'],['subscription','subscription.php','bi-credit-card','Subscription'],['settings','settings.php','bi-gear','Settings']] as [$k,$u,$i,$l]): ?>
  <a class="nav-link <?= ($active??'')===$k?'active':'' ?>" href="<?= $b ?><?= $u ?>"><i class="bi <?= $i ?>"></i> <?= $l ?></a>
  <?php endforeach; ?>
  <div class="mt-auto small text-white-50"><?= e(current_user()['name'] ?? '') ?><br><?= e(str_replace('_',' ',current_user()['role'] ?? '')) ?></div>
  <a class="nav-link" href="<?= $b ?>auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</nav>
