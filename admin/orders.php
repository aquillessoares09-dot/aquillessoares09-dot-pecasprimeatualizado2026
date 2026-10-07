<?php
require_once __DIR__ . '/../config.php';
if (!current_user() || !current_user()['is_admin']) { http_response_code(403); die('Acesso restrito.'); }

// Apagar pedido
if (isset($_POST['delete_order'])) {
    $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([(int)$_POST['order_id']]);
    $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([(int)$_POST['order_id']]);
    header('Location: orders.php');
    exit;
}

// Atualizar status do pedido
if (isset($_POST['status'])) {
    $st = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $st->execute([trim($_POST['status']), (int)$_POST['order_id']]);
    header('Location: orders.php');
    exit;
}

// Responder pergunta
if (isset($_POST['answer'])) {
    $st = $pdo->prepare('UPDATE questions SET answer = ? WHERE id = ?');
    $st->execute([trim($_POST['answer']), (int)$_POST['question_id']]);
    header('Location: orders.php?tab=questions');
    exit;
}

$orders = $pdo->query('SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC')->fetchAll();
$questions = $pdo->query('SELECT q.*, p.name AS product_name FROM questions q JOIN products p ON p.id = q.product_id ORDER BY q.id DESC')->fetchAll();
$statuses = ['aguardando pagamento', 'pago', 'enviado', 'entregue', 'cancelado'];

$pageTitle = 'Pedidos';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-head">
  <h1 class="page-title">Pedidos e perguntas</h1>
  <a class="btn secondary" href="index.php">← Painel</a>
</div>

<h2 class="section-title">Pedidos</h2>
<?php if (!$orders): ?><p class="empty">Nenhum pedido ainda.</p><?php endif; ?>
<?php foreach ($orders as $o):
  $it = $pdo->prepare('SELECT oi.*, p.name AS pname FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
  $it->execute([$o['id']]);
  $items = $it->fetchAll();
?>
<div class="order-card">
  <div class="order-head">
    <b>#<?= $o['id'] ?></b>
    <span><?= e($o['user_name'] ?? '—') ?> (<?= e($o['user_email'] ?? '') ?>)</span>
    <span><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></span>
    <span><?= money($o['total']) ?> · <?= e($o['payment']) ?></span>
  </div>
  <?php foreach ($items as $i): ?><p><?= $i['qty'] ?>x <?= e($i['pname']) ?></p><?php endforeach; ?>
  <form method="post" class="status-form">
    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
    <select name="status">
      <?php foreach ($statuses as $s): ?>
        <option <?= $o['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn secondary" type="submit">Atualizar status</button>
  </form>
  <form method="post" onsubmit="return confirm('Apagar o pedido #<?= $o['id'] ?>? Isso não pode ser desfeito.')">
    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
    <button class="btn danger" type="submit" name="delete_order" value="1">Apagar pedido</button>
  </form>
</div>
<?php endforeach; ?>

<h2 class="section-title">Perguntas dos clientes</h2>
<?php if (!$questions): ?><p class="empty">Nenhuma pergunta ainda.</p><?php endif; ?>
<?php foreach ($questions as $q): ?>
<div class="order-card">
  <p><b><?= e($q['product_name']) ?></b></p>
  <p><b>Pergunta:</b> <?= e($q['question']) ?></p>
  <?php if ($q['answer']): ?><p class="ok"><b>Resposta:</b> <?= e($q['answer']) ?></p><?php else: ?>
  <form method="post" class="status-form">
    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
    <input type="text" name="answer" placeholder="Responder ao cliente" required>
    <button class="btn secondary" type="submit">Responder</button>
  </form>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>

