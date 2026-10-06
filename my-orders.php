<?php
require_once __DIR__ . '/config.php';
require_login();
$pageTitle = 'Meus pedidos';
include __DIR__ . '/includes/header.php';

$st = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$st->execute([current_user()['id']]);
$orders = $st->fetchAll();

$success = $_GET['success'] ?? null;
if ($success) {
    $st2 = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
    $st2->execute([(int)$success, current_user()['id']]);
    $successOrder = $st2->fetch();
}
?>

<h1 class="page-title">Meus pedidos</h1>
<?php if ($success): ?>
  <p class="ok big-ok">Pedido #<?= (int)$success ?> confirmado! Você pode acompanhá-lo abaixo.</p>
  <?php if ($successOrder && $successOrder['payment'] === 'pix'): ?>
    <div class="pix-box">
      <h3>Pagamento via Pix</h3>
      <p>Para pagar, envie <b><?= money($successOrder['total']) ?></b> para a chave Pix abaixo:</p>
      <p class="pix-key"><?= e(PIX_KEY) ?></p>
      <p class="muted">Assim que o pagamento for identificado, o status do pedido será atualizado para "pago".</p>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php if (!$orders): ?>
  <p class="empty">Você ainda não fez nenhum pedido. <a href="products.php">Ver produtos</a></p>
<?php else: ?>
  <?php foreach ($orders as $o):
    $it = $pdo->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
    $it->execute([$o['id']]);
    $items = $it->fetchAll();
  ?>
  <div class="order-card">
    <div class="order-head">
      <b>Pedido #<?= $o['id'] ?></b>
      <span><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></span>
      <span class="status"><?= e($o['status']) ?></span>
    </div>
    <?php foreach ($items as $i): ?>
      <p><?= $i['qty'] ?>x <?= e($i['name']) ?> — <?= money($i['price'] * $i['qty']) ?></p>
    <?php endforeach; ?>
    <p class="total">Total: <b><?= money($o['total']) ?></b> · Pagamento: <?= e($o['payment']) ?></p>
    <?php if ($o['payment'] === 'pix'): ?>
      <p class="pix-inline">Chave Pix (e-mail): <b><?= e(PIX_KEY) ?></b></p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

