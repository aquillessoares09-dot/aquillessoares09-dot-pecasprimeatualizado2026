<?php
session_start();

// Configuração do XAMPP (padrão: usuário root sem senha)
$DB_HOST = 'localhost';
$DB_NAME = 'pecas_prime';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados. Verifique se o MySQL do XAMPP está rodando e se você importou o arquivo <b>install.sql</b> no phpMyAdmin.<br>Detalhe: ' . htmlspecialchars($e->getMessage()));
}

// Chave Pix da loja (recebimento dos pedidos)
const PIX_KEY = 'aquillessoares09@gmail.com';

// Migração automática: coluna de imagem do produto (ignora se já existir)
try { $pdo->exec('ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL'); } catch (Exception $e) {}

// Salva a imagem enviada no formulário do admin; retorna o nome do arquivo ou null
function save_product_image($productId) {
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) return null;
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) return null;
    $dir = __DIR__ . '/assets/img/products';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $fname = 'prod_' . (int)$productId . '.' . $ext;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $fname)) return $fname;
    return null;
}

// Caminho da imagem do produto: foto enviada ou imagem automática (img.php)
function product_img($p) {
    if (!empty($p['image'])) return 'assets/img/products/' . $p['image'];
    return 'img.php?id=' . ($p['product_id'] ?? $p['id']);
}

// Apaga o arquivo de imagem de um produto (se houver)
function delete_product_image_file($image) {
    if ($image) @unlink(__DIR__ . '/assets/img/products/' . $image);
}

// Base para links (páginas em /admin/ ficam um nível abaixo)
$BASE = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) ? '../' : '';

// Helpers
function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function money($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function cart_count() { return array_sum($_SESSION['cart'] ?? []); }
function cart_total($pdo) {
    $total = 0;
    foreach ($_SESSION['cart'] ?? [] as $pid => $qty) {
        $st = $pdo->prepare('SELECT price FROM products WHERE id = ?');
        $st->execute([$pid]);
        if ($p = $st->fetch()) $total += $p['price'] * $qty;
    }
    return $total;
}
function current_user() { return $_SESSION['user'] ?? null; }
function require_login() {
    if (!current_user()) { header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); exit; }
}

