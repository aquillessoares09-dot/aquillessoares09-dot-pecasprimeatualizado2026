<?php
require_once __DIR__ . '/config.php';

// Processa ações do carrinho (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['product_id'] ?? 0);
    if (isset($_POST['add'])) {
        $st = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
        $st->execute([$pid]);
        if ($p = $st->fetch()) {
            $qty = (int)($_POST['qty'] ?? 1);
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            $newQty = min(($_SESSION['cart'][$pid] ?? 0) + $qty, $p['stock']);
            if ($newQty > 0) $_SESSION['cart'][$pid] = $newQty;
        }
    }
    if (isset($_POST['update'])) {
        $qty = (int)$_POST['qty'];
        if ($qty <= 0) unset($_SESSION['cart'][$pid]);
        else $_SESSION['cart'][$pid] = $qty;
    }
    if (isset($_POST['remove'])) unset($_SESSION['cart'][$pid]);
    header('Location: ' . ($_POST['back'] ?? 'cart.php'));
    exit;
}

// Buscar produto
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?');
$st->execute([$id]);
$product = $st->fetch();
if (!$product) { http_response_code(404); die('Produto não encontrado. <a href="index.php">Voltar</a>'); }
$pageTitle = $product['name'];

// Pergunta / resposta
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ask'])) {
    require_login();
    $st = $pdo->prepare('INSERT INTO questions (product_id, user_id, question) VALUES (?, ?, ?)');
    $st->execute([$id, current_user()['id'], trim($_GET['ask'])]);
    header('Location: product.php?id=' . $id);
    exit;
}

$questions = $pdo->prepare('SELECT * FROM questions WHERE product_id = ? ORDER BY id DESC');
$questions->execute([$id]);
$questions = $questions->fetchAll();
$related = $pdo->prepare('SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id WHERE p.category_id = ? AND p.id != ? ORDER BY sales DESC LIMIT 4');
$related->execute([$product['category_id'], $id]);
$related = $related->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="product-layout">
  <div class="product-gallery">
    <img src="<?= $BASE . product_img($product) ?>" alt="<?= e($product['name']) ?>">
  </div>
  <div class="product-info">
    <p class="cat"><?= e($product['category']) ?> · <?= e($product['brand']) ?> · Novo</p>
    <h1><?= e($product['name']) ?></h1>
    <?php if ($product['sales'] > 0): ?><p class="sales"><?= $product['sales'] ?> vendidos</p><?php endif; ?>
    <p class="stock"><?= $product['stock'] > 0 ? 'Em estoque (' . $product['stock'] . ' disponíveis)' : 'Sem estoque' ?></p>

    <?php if ($product['old_price']): ?>
      <p class="old-price"><?= money($product['old_price']) ?> <span class="discount"><?= round((1 - $product['price'] / $product['old_price']) * 100) ?>% OFF</span></p>
    <?php endif; ?>
    <p class="price xl"><?= money($product['price']) ?></p>
    <?php if ($product['free_shipping']): ?><p class="freeship">Frete grátis para todo o Brasil</p><?php endif; ?>

    <div class="buy-actions">
      <form method="post" action="product.php">
        <input type="hidden" name="product_id" value="<?= $id ?>">
        <label>Quantidade <input type="number" name="qty" value="1" min="1" max="<?= $product['stock'] ?>" class="qty-input"></label>
        <button class="btn" type="submit" name="add" value="1" <?= $product['stock'] == 0 ? 'disabled' : '' ?>>Adicionar ao carrinho</button>
      </form>
      <a class="btn buy-now" href="cart.php?buy=<?= $id ?>">Comprar agora</a>
    </div>
  </div>
  <div class="product-desc">
    <h2>Descrição</h2>
    <p><?= nl2br(e($product['description'])) ?></p>
    <?php if ($product['specs']): ?>
      <h3>Especificações</h3>
      <table class="specs">
        <?php foreach (explode('|', $product['specs']) as $s): list($k, $v) = array_pad(explode(':', $s, 2), 2, ''); ?>
          <tr><th><?= e(trim($k)) ?></th><td><?= e(trim($v)) ?></td></tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>

<section class="qa-section">
  <h2>Perguntas e respostas</h2>
  <?php foreach ($questions as $q): ?>
    <div class="qa">
      <p class="q"><b>Pergunta:</b> <?= e($q['question']) ?></p>
      <?php if ($q['answer']): ?><p class="a"><b>Resposta:</b> <?= e($q['answer']) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <form method="get" class="ask-form">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="text" name="ask" placeholder="Faça uma pergunta ao vendedor" required>
    <button class="btn secondary" type="submit">Perguntar</button>
  </form>
</section>

<?php if ($related): ?>
<h2 class="section-title">Quem viu este produto também viu</h2>
<div class="grid">
  <?php foreach ($related as $p): ?>
    <a class="card" href="product.php?id=<?= $p['id'] ?>">
      <img src="<?= $BASE . product_img($p) ?>" alt="<?= e($p['name']) ?>">
      <div class="card-body">
        <p class="cat"><?= e($p['category']) ?></p>
        <h3><?= e($p['name']) ?></h3>
        <p class="price"><?= money($p['price']) ?></p>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
