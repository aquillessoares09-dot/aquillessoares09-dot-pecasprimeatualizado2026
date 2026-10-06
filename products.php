<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Catálogo';
include __DIR__ . '/includes/header.php';

// Filtros
$where = []; $params = [];
if (!empty($_GET['q'])) { $where[] = 'p.name LIKE ?'; $params[] = '%' . $_GET['q'] . '%'; }
if (!empty($_GET['category'])) { $where[] = 'p.category_id = ?'; $params[] = (int)$_GET['category']; }
if (!empty($_GET['brand'])) { $where[] = 'p.brand = ?'; $params[] = $_GET['brand']; }
if (isset($_GET['min']) && $_GET['min'] !== '') { $where[] = 'p.price >= ?'; $params[] = (float)$_GET['min']; }
if (isset($_GET['max']) && $_GET['max'] !== '') { $where[] = 'p.price <= ?'; $params[] = (float)$_GET['max']; }

$orderBy = 'p.sales DESC';
$sort = $_GET['sort'] ?? '';
if ($sort === 'price_asc') $orderBy = 'p.price ASC';
if ($sort === 'price_desc') $orderBy = 'p.price DESC';
if ($sort === 'best') $orderBy = 'p.sales DESC';

$sql = 'SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY $orderBy";
$st = $pdo->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$brands = $pdo->query('SELECT DISTINCT brand FROM products ORDER BY brand')->fetchAll();
$qs = http_build_query(array_filter($_GET, fn($k) => $k !== 'sort' && $_GET[$k] !== '', ARRAY_FILTER_USE_KEY));
?>
<h1 class="page-title"><?= !empty($_GET['q']) ? 'Resultados para "' . e($_GET['q']) . '"' : 'Catálogo' ?> <span class="muted">(<?= count($products) ?> produtos)</span></h1>

<div class="catalog-layout">
  <aside class="filters">
    <form method="get">
      <?php if (!empty($_GET['q'])): ?><input type="hidden" name="q" value="<?= e($_GET['q']) ?>"><?php endif; ?>
      <h3>Categoria</h3>
      <select name="category" onchange="this.form.submit()">
        <option value="">Todas</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($_GET['category'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <h3>Marca</h3>
      <select name="brand" onchange="this.form.submit()">
        <option value="">Todas</option>
        <?php foreach ($brands as $b): ?>
          <option value="<?= e($b['brand']) ?>" <?= ($_GET['brand'] ?? '') === $b['brand'] ? 'selected' : '' ?>><?= e($b['brand']) ?></option>
        <?php endforeach; ?>
      </select>
      <h3>Preço (R$)</h3>
      <div class="price-range">
        <input type="number" name="min" placeholder="mín" value="<?= e($_GET['min'] ?? '') ?>">
        <input type="number" name="max" placeholder="máx" value="<?= e($_GET['max'] ?? '') ?>">
      </div>
      <button class="btn" type="submit">Aplicar filtros</button>
    </form>
  </aside>

  <div>
    <div class="sort-bar">
      Ordenar por:
      <a class="<?= $sort === '' ? 'active' : '' ?>" href="?<?= $qs ?>">Relevância</a>
      <a class="<?= $sort === 'price_asc' ? 'active' : '' ?>" href="?<?= $qs . ($qs ? '&' : '') ?>sort=price_asc">Menor preço</a>
      <a class="<?= $sort === 'price_desc' ? 'active' : '' ?>" href="?<?= $qs . ($qs ? '&' : '') ?>sort=price_desc">Maior preço</a>
      <a class="<?= $sort === 'best' ? 'active' : '' ?>" href="?<?= $qs . ($qs ? '&' : '') ?>sort=best">Mais vendidos</a>
    </div>

    <?php if (!$products): ?>
      <p class="empty">Nenhum produto encontrado. <a href="products.php">Limpar filtros</a></p>
    <?php else: ?>
    <div class="grid">
      <?php foreach ($products as $p): ?>
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
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
