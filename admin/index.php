<?php
require_once __DIR__ . '/../config.php';
if (!current_user() || !current_user()['is_admin']) { http_response_code(403); die('Acesso restrito.'); }

$stats = [
  'products' => $pdo->query('SELECT COUNT(*) c FROM products')->fetch()['c'],
  'users' => $pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'],
  'orders' => $pdo->query('SELECT COUNT(*) c FROM orders')->fetch()['c'],
  'revenue' => $pdo->query('SELECT COALESCE(SUM(total),0) t FROM orders')->fetch()['t'],
  'lowstock' => $pdo->query('SELECT COUNT(*) c FROM products WHERE stock < 20')->fetch()['c'],
];
$recent = $pdo->query('SELECT o.*, u.name AS user_name FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 5')->fetchAll();

$pageTitle = 'Painel admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-head">
  <h1 class="page-title">Painel do vendedor</h1>
  <a class="btn" href="products.php">Gerenciar produtos</a>
  <a class="btn secondary" href="orders.php">Gerenciar pedidos</a>
</div>

<div class="stats-row">
  <div class="stat"><p>Produtos</p><b><?= $stats['products'] ?></b></div>
  <div class="stat"><p>Usuários</p><b><?= $stats['users'] ?></b></div>
  <div class="stat"><p>Pedidos</p><b><?= $stats['orders'] ?></b></div>
  <div class="stat"><p>Faturamento</p><b><?= money($stats['revenue']) ?></b></div>
  <div class="stat <?= $stats['lowstock'] ? 'alert' : '' ?>"><p>Estoque baixo (&lt;20)</p><b><?= $stats['lowstock'] ?></b></div>
</div>

<h2 class="section-title">Pedidos recentes</h2>
<?php if (!$recent): ?><p class="empty">Nenhum pedido ainda.</p><?php endif; ?>
<?php foreach ($recent as $o): ?>
  <div class="order-card">
    <div class="order-head">
      <b>#<?= $o['id'] ?></b>
      <span><?= e($o['user_name'] ?? '—') ?></span>
      <span><?= money($o['total']) ?></span>
      <span class="status"><?= e($o['status']) ?></span>
    </div>
  </div>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
