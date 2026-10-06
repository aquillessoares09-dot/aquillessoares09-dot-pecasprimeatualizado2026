<?php
// Gera imagens de produto (SVG) com base no nome e categoria — funciona offline no XAMPP
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT p.name, c.name AS cat FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?');
$st->execute([$id]);
$p = $st->fetch();

$cat = $p['cat'] ?? '';
$name = $p['name'] ?? 'Peça';
$initials = mb_strtoupper(mb_substr($name, 0, 1) . (mb_substr($name, 5, 1) ?: ''));
$label = mb_substr($name, 0, 28);

header('Content-Type: image/svg+xml');
echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#f5f5f5"/>
      <stop offset="1" stop-color="#e0e0e0"/>
    </linearGradient>
  </defs>
  <rect width="400" height="300" fill="url(#g)"/>
  <rect x="0" y="270" width="400" height="30" fill="#242323"/>
  <text x="200" y="150" font-size="90" font-weight="bold" text-anchor="middle" fill="#ff3131" font-family="Arial">$initials</text>
  <text x="200" y="225" font-size="17" text-anchor="middle" fill="#242323" font-family="Arial"><?= e($cat) ?></text>
  <text x="200" y="290" font-size="14" text-anchor="middle" fill="#ffffff" font-family="Arial">Peças Prime · {$label}</text>
</svg>
SVG;
