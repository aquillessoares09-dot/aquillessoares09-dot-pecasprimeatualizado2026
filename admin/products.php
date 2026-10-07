<?php
require_once __DIR__ . '/../config.php';
if (!current_user() || !current_user()['is_admin']) { http_response_code(403); die('Acesso restrito.'); }

// Adicionar / editar
if (isset($_POST['save'])) {
    $data = [
        trim($_POST['name']), trim($_POST['description'] ?? ''), trim($_POST['specs'] ?? ''),
        (int)$_POST['category_id'], trim($_POST['brand'] ?? ''),
        (float)$_POST['price'], ($_POST['old_price'] !== '' ? (float)$_POST['old_price'] : null),
        (int)$_POST['stock'], (int)$_POST['sales'],
        isset($_POST['free_shipping']) ? 1 : 0, isset($_POST['featured']) ? 1 : 0,
    ];
    if (!empty($_POST['id'])) {
        $pid = (int)$_POST['id'];
        $st = $pdo->prepare('UPDATE products SET name=?, description=?, specs=?, category_id=?, brand=?, price=?, old_price=?, stock=?, sales=?, free_shipping=?, featured=? WHERE id=?');
        $st->execute([...$data, $pid]);
    } else {
        $st = $pdo->prepare('INSERT INTO products (name, description, specs, category_id, brand, price, old_price, stock, sales, free_shipping, featured, rating, rating_count) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0)');
        $st->execute([...$data, 5]);
        $pid = (int)$pdo->lastInsertId();
    }

    // Imagem do produto
    $atual = $pdo->prepare('SELECT image FROM products WHERE id = ?');
    $atual->execute([$pid]);
    $imgAtual = $atual->fetchColumn();
    if (isset($_POST['remove_image']) && $imgAtual) {
        delete_product_image_file($imgAtual);
        $pdo->prepare('UPDATE products SET image = NULL WHERE id = ?')->execute([$pid]);
        $imgAtual = null;
    }
    if (!empty($_FILES['image']['name'])) {
        $novo = save_product_image($pid);
        if ($novo) {
            delete_product_image_file($imgAtual);
            $pdo->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([$novo, $pid]);
        }
    }
    header('Location: products.php');
    exit;
}

// Excluir
if (isset($_GET['delete'])) {
    $st = $pdo->prepare('SELECT image FROM products WHERE id = ?');
    $st->execute([(int)$_GET['delete']]);
    delete_product_image_file($st->fetchColumn());
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: products.php');
    exit;
}

// Produto em edição
$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$products = $pdo->query('SELECT p.*, c.name AS category FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC')->fetchAll();

$pageTitle = 'Produtos';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-head">
  <h1 class="page-title">Produtos</h1>
  <a class="btn secondary" href="index.php">← Painel</a>
</div>

<div class="admin-form">
  <h3><?= $edit ? 'Editar produto #' . $edit['id'] : 'Cadastrar novo produto' ?></h3>
  <form method="post" enctype="multipart/form-data">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
    <div class="form-row">
      <label>Nome*<input type="text" name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
      <label>Marca<input type="text" name="brand" value="<?= e($edit['brand'] ?? '') ?>"></label>
      <label>Categoria
        <select name="category_id">
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($edit['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div class="form-row">
      <label>Preço (R$)*<input type="number" step="0.01" name="price" required value="<?= e($edit['price'] ?? '') ?>"></label>
      <label>Preço antigo (R$)<input type="number" step="0.01" name="old_price" value="<?= e($edit['old_price'] ?? '') ?>"></label>
      <label>Estoque<input type="number" name="stock" value="<?= (int)($edit['stock'] ?? 0) ?>"></label>
    </div>
    <label>Imagem do produto<input type="file" name="image" accept="image/*"></label>
    <?php if ($edit && $edit['image']): ?>
      <div class="edit-img">
        <img src="<?= $BASE ?>assets/img/products/<?= e($edit['image']) ?>" alt="Imagem atual" class="admin-thumb">
        <label class="radio"><input type="checkbox" name="remove_image"> Remover esta imagem</label>
      </div>
    <?php endif; ?>
    <label>Descrição<textarea name="description" rows="2"><?= e($edit['description'] ?? '') ?></textarea></label>
    <label>Especificações (separadas por | ex: Material: cerâmica|Conteúdo: 4 pastilhas)<input type="text" name="specs" value="<?= e($edit['specs'] ?? '') ?>"></label>
    <div class="form-row checks">
      <label class="radio"><input type="checkbox" name="free_shipping" <?= !empty($edit['free_shipping']) ? 'checked' : '' ?>> Frete grátis</label>
      <label class="radio"><input type="checkbox" name="featured" <?= !empty($edit['featured']) ? 'checked' : '' ?>> Destaque</label>
    </div>
    <button class="btn" type="submit" name="save" value="1"><?= $edit ? 'Salvar alterações' : 'Cadastrar produto' ?></button>
    <?php if ($edit): ?><a class="btn secondary" href="products.php">Cancelar</a><?php endif; ?>
  </form>
</div>

<table class="admin-table">
  <tr><th>ID</th><th>Foto</th><th>Produto</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Vendas</th><th>Ações</th></tr>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><img src="<?= $BASE . product_img($p) ?>" alt="" class="admin-thumb"></td>
      <td><?= e($p['name']) ?></td>
      <td><?= e($p['category']) ?></td>
      <td><?= money($p['price']) ?></td>
      <td class="<?= $p['stock'] < 20 ? 'text-alert' : '' ?>"><?= $p['stock'] ?></td>
      <td><?= $p['sales'] ?></td>
      <td>
        <a href="products.php?edit=<?= $p['id'] ?>">Editar</a> ·
        <a class="danger" href="products.php?delete=<?= $p['id'] ?>" onclick="return confirm('Excluir este produto?')">Excluir</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
