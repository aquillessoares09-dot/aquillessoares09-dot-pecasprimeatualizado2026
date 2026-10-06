<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';

$offers = $pdo->query('SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id WHERE old_price IS NOT NULL ORDER BY (old_price - price) DESC LIMIT 12')->fetchAll();
$featured = $pdo->query('SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id WHERE featured = 1 ORDER BY sales DESC LIMIT 8')->fetchAll();
$best = $pdo->query('SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id ORDER BY sales DESC LIMIT 4')->fetchAll();
?>

<section class="hero">
  <div class="hero-text">
    <h1>Tudo para o seu carro, <span>com preço de fábrica</span></h1>
    <p>Milhares de peças das melhores marcas. Frete grátis acima de R$ 199.</p>
    <a class="btn" href="products.php">Ver catálogo completo</a>
  </div>
  <div class="hero-badge">Até <b>40% OFF</b><br>em freios e suspensão</div>
</section>

<h2 class="section-title">Ofertas do dia</h2>
<div class="grid">
  <?php foreach ($offers as $p): ?>
    <a class="card" href="product.php?id=<?= $p['id'] ?>">
      <img src="<?= $BASE . product_img($p) ?>" alt="<?= e($p['name']) ?>">
      <?php if ($p['old_price']): ?>
        <span class="discount"><?= round((1 - $p['price'] / $p['old_price']) * 100) ?>% OFF</span>
      <?php endif; ?>
      <div class="card-body">
        <p class="cat"><?= e($p['category']) ?></p>
        <h3><?= e($p['name']) ?></h3>
        <?php if ($p['free_shipping']): ?><p class="freeship">Frete grátis</p><?php endif; ?>
        <p class="price"><?= money($p['price']) ?></p>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<h2 class="section-title">Destaques</h2>
<div class="grid">
  <?php foreach ($featured as $p): ?>
    <a class="card" href="product.php?id=<?= $p['id'] ?>">
      <img src="<?= $BASE . product_img($p) ?>" alt="<?= e($p['name']) ?>">
      <div class="card-body">
        <p class="cat"><?= e($p['category']) ?></p>
        <h3><?= e($p['name']) ?></h3>
        <?php if ($p['free_shipping']): ?><p class="freeship">Frete grátis</p><?php endif; ?>
        <p class="price"><?= money($p['price']) ?></p>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<h2 class="section-title">Mais vendidos</h2>
<div class="grid">
  <?php foreach ($best as $p): ?>
    <a class="card" href="product.php?id=<?= $p['id'] ?>">
      <img src="<?= $BASE . product_img($p) ?>" alt="<?= e($p['name']) ?>">
      <div class="card-body">
        <p class="cat"><?= e($p['category']) ?></p>
        <h3><?= e($p['name']) ?></h3>
        <?php if ($p['sales'] > 0): ?><p class="sales"><?= $p['sales'] ?> vendidos</p><?php endif; ?>
        <p class="price"><?= money($p['price']) ?></p>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
