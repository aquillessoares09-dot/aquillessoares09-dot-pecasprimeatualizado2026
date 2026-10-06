<?php
require_once __DIR__ . '/config.php';

// "Comprar agora" direto da página do produto
if (isset($_GET['buy'])) {
    $pid = (int)$_GET['buy'];
    $st = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
    $st->execute([$pid]);
    if ($p = $st->fetch()) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $_SESSION['cart'][$pid] = min(($_SESSION['cart'][$pid] ?? 0) + 1, $p['stock']);
    }
    header('Location: cart.php');
    exit;
}

// Atualizações do carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['product_id'] ?? 0);
    if (isset($_POST['update'])) {
        $qty = (int)$_POST['qty'];
        if ($qty <= 0) unset($_SESSION['cart'][$pid]); else $_SESSION['cart'][$pid] = $qty;
    }
    if (isset($_POST['remove'])) unset($_SESSION['cart'][$pid]);
    header('Location: cart.php');
    exit;
}

$pageTitle = 'Carrinho';
include __DIR__ . '/includes/header.php';

$items = [];
foreach ($_SESSION['cart'] ?? [] as $pid => $qty) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$pid]);
    if ($p = $st->fetch()) { $p['qty'] = $qty; $items[] = $p; }
}
$subtotal = 0;
foreach ($items as $i) $subtotal += $i['price'] * $i['qty'];
$total = $subtotal;
$shipping = ($subtotal >= 199 || $subtotal == 0) ? 0 : 29.90;
$total += $shipping;
?>

<h1 class="page-title">Seu carrinho (<?= count($items) ?> itens)</h1>

<?php if (!$items): ?>
  <p class="empty">Seu carrinho está vazio. <a href="products.php">Ver produtos</a></p>
<?php else: ?>
<div class="cart-layout">
  <div class="cart-items">
    <?php foreach ($items as $p): ?>
      <div class="cart-row">
        <img src="<?= $BASE . product_img($p) ?>" alt="<?= e($p['name']) ?>">
        <div class="cart-info">
          <a href="product.php?id=<?= $p['id'] ?>"><h3><?= e($p['name']) ?></h3></a>
          <p class="cat"><?= e($p['brand']) ?></p>
          <p class="price"><?= money($p['price']) ?></p>
        </div>
        <div class="cart-controls">
          <form method="post" class="inline">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            <input type="number" name="qty" value="<?= $p['qty'] ?>" min="0" max="<?= $p['stock'] ?>" class="qty-input">
            <button class="link-btn" type="submit" name="update" value="1">Atualizar</button>
            <button class="link-btn danger" type="submit" name="remove" value="1">Excluir</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <aside class="cart-summary">
    <h3>Resumo</h3>
    <p>Subtotal: <b><?= money($subtotal) ?></b></p>
    <p>Frete: <b><?= $shipping ? money($shipping) : 'Grátis' ?></b></p>
    <?php if ($shipping): ?><p class="muted">Frete grátis acima de R$ 199!</p><?php endif; ?>
    <hr>
    <p class="total">Total: <b><?= money($total) ?></b></p>
    <a class="btn wide" href="checkout.php">Finalizar compra</a>
    <a class="muted" href="products.php">Continuar comprando</a>
  </aside>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
