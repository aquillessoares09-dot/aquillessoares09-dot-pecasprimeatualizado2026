<?php
require_once __DIR__ . '/config.php';
require_login();

$items = [];
foreach ($_SESSION['cart'] ?? [] as $pid => $qty) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$pid]);
    if ($p = $st->fetch()) { $p['qty'] = $qty; $items[] = $p; }
}
if (!$items) { header('Location: cart.php'); exit; }

$subtotal = 0;
foreach ($items as $i) $subtotal += $i['price'] * $i['qty'];
$shipping = $subtotal >= 199 ? 0 : 29.90;
$orderError = '';
$total = $subtotal + $shipping;

// Finalizar pedido
if (isset($_POST['place_order'])) {
    $u = current_user();
    $totalFinal = $subtotal + $shipping;
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('INSERT INTO orders (user_id, total, payment, status, address) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$u['id'], $totalFinal, $_POST['payment'] ?? 'pix', 'aguardando pagamento', '']);
        $orderId = $pdo->lastInsertId();
        foreach ($items as $i) {
            $st = $pdo->prepare('INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)');
            $st->execute([$orderId, $i['id'], $i['qty'], $i['price']]);
            $pdo->prepare('UPDATE products SET stock = stock - ?, sales = sales + ? WHERE id = ?')->execute([$i['qty'], $i['qty'], $i['id']]);
        }
        $pdo->commit();
        unset($_SESSION['cart']);
        header('Location: my-orders.php?success=' . $orderId);
        exit;
    } catch (Exception $ex) {
        $pdo->rollBack();
        $orderError = 'Erro ao processar o pedido: ' . $ex->getMessage();
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/includes/header.php';
$u = current_user();
?>

<h1 class="page-title">Finalizar compra</h1>
<div class="checkout-layout">
  <div class="checkout-form">
    <form method="post" class="address-form">
      <h3>Forma de pagamento</h3>
      <div class="payment-options">
        <label class="radio"><input type="radio" name="payment" value="pix" checked> Pix (aprovação imediata)</label>
        <p class="muted">Chave Pix (e-mail): <b><?= e(PIX_KEY) ?></b></p>
      </div>

      <?php if ($orderError): ?><p class="error"><?= e($orderError) ?></p><?php endif; ?>

      <button class="btn wide" type="submit" name="place_order" value="1">Confirmar pedido</button>
    </form>
  </div>

  <aside class="cart-summary">
    <h3>Resumo do pedido</h3>
    <?php foreach ($items as $i): ?>
      <p><?= $i['qty'] ?>x <?= e($i['name']) ?> <b><?= money($i['price'] * $i['qty']) ?></b></p>
    <?php endforeach; ?>
    <hr>
    <p>Subtotal: <b><?= money($subtotal) ?></b></p>
    <p>Frete: <b><?= $shipping ? money($shipping) : 'Grátis' ?></b></p>
    <hr>
    <p class="total">Total: <b><?= money($total) ?></b></p>
  </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

