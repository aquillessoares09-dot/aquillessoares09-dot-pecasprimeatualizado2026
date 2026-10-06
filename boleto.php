<?php
require_once __DIR__ . '/config.php';
require_login();

// ---- Boleto (simulação) ----
function dv_mod10($num) {
    $sum = 0; $w = 2;
    for ($i = strlen($num) - 1; $i >= 0; $i--) {
        $d = $num[$i] * $w; $sum += ($d > 9) ? $d - 9 : $d;
        $w = ($w == 2) ? 1 : 2;
    }
    $r = $sum % 10;
    return $r ? 10 - $r : 0;
}
function dv_mod11($num) {
    $sum = 0; $w = 2;
    for ($i = strlen($num) - 1; $i >= 0; $i--) {
        $sum += $num[$i] * $w; $w = ($w == 9) ? 2 : $w + 1;
    }
    $r = $sum % 11;
    $dv = 11 - $r;
    return ($r == 0 || $r == 1 || $dv > 9) ? 1 : $dv;
}

function barcode_i2of5($digits) {
    if (!function_exists('imagecreate')) return null;
    $n = 2; $w = 5; $h = 52;
    // padrões Interleaved 2 of 5 (1 = largo)
    $pat = [0 => '00110', 1 => '10001', 2 => '01001', 3 => '11000', 4 => '00101',
            5 => '10100', 6 => '01100', 7 => '00011', 8 => '10010', 9 => '01010'];
    $bars = [];
    // start: barra fina, espaço fino, barra fina, espaço fino
    $seq = [[1, 1], [0, 1], [1, 1], [0, 1]]; // [cor(1=preto), largura_em_un]
    for ($i = 0; $i < strlen($digits); $i += 2) {
        $d1 = $pat[(int)$digits[$i]]; $d2 = $pat[(int)$digits[$i + 1]];
        for ($k = 0; $k < 5; $k++) {
            $seq[] = [1, $d1[$k] ? $w : $n]; // barra (dígito ímpar)
            $seq[] = [0, $d2[$k] ? $w : $n]; // espaço (dígito par)
        }
    }
    // stop: barra larga, espaço fino, barra fina
    $seq[] = [1, $w]; $seq[] = [0, $n]; $seq[] = [1, $n];
    $totalW = 0;
    foreach ($seq as $s) $totalW += $s[1];
    $img = imagecreate($totalW + 20, $h);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $fg = imagecolorallocate($img, 0, 0, 0);
    $x = 10;
    foreach ($seq as $s) {
        if ($s[0]) imagefilledrectangle($img, $x, 0, $x + $s[1] - 1, $h - 1, $fg);
        $x += $s[1];
    }
    ob_start();
    imagepng($img);
    $data = ob_get_clean();
    imagedestroy($img);
    return 'data:image/png;base64,' . base64_encode($data);
}

// Busca o pedido
$id = (int)($_GET['id'] ?? 0);
$u = current_user();
$st = $pdo->prepare('SELECT * FROM orders WHERE id = ?' . ($u['is_admin'] ? '' : ' AND user_id = ?'));
$st->execute($u['is_admin'] ? [$id] : [$id, $u['id']]);
$order = $st->fetch();
if (!$order) { http_response_code(404); die('Pedido não encontrado. <a href="my-orders.php">Voltar</a>'); }
if ($order['payment'] !== 'boleto') { header('Location: order-receipt.php?id=' . $id); exit; }

$st = $pdo->prepare('SELECT name, email, cpf FROM users WHERE id = ?');
$st->execute([$order['user_id']]);
$customer = $st->fetch() ?: [];

// ---- Monta o boleto ----
$bank = '237'; $currency = '9';
$amountCents = str_pad((string)round($order['total'] * 100), 10, '0', STR_PAD_LEFT);
$due = date('Y-m-d', strtotime($order['created_at'] . ' +3 days'));
$factor = (string)max(1, floor((strtotime($due) - strtotime('1997-07-03')) / 86400));
$factor = str_pad($factor, 4, '0', STR_PAD_LEFT);
$free = '1' . str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) . date('ymd', strtotime($order['created_at'])) . str_pad('', 13, '0'); // 25 dígitos
$bar43 = $bank . $currency . $factor . $amountCents . $free; // 43 dígitos
$dv = dv_mod11($bar43);
$barcode = $bar43 . $dv; // 44 dígitos

$b1 = substr($barcode, 0, 4) . substr($barcode, 19, 5);
$b2 = substr($barcode, 24, 10);
$b3 = substr($barcode, 34, 10);
$linha = substr($b1, 0, 5) . '.' . substr($b1, 5) . dv_mod10($b1) . ' '
       . substr($b2, 0, 5) . '.' . substr($b2, 5) . dv_mod10($b2) . ' '
       . substr($b3, 0, 5) . '.' . substr($b3, 5) . dv_mod10($b3) . ' '
       . $dv . ' ' . $factor . $amountCents;

$barcodeImg = barcode_i2of5($barcode);
$pageTitle = 'Boleto do pedido #' . $id;
include __DIR__ . '/includes/header.php';
?>

<div class="boleto">
  <div class="boleto-head">
    <div>
      <img src="<?= $BASE ?>assets/img/logo.jpg" alt="Peças Prime" class="receipt-logo">
      <b>Peças Prime</b>
      <p class="muted">Marketplace de autopeças — CNPJ 00.000.000/0001-00</p>
    </div>
    <div class="boleto-meta">
      <p class="muted">Vencimento</p>
      <b><?= date('d/m/Y', strtotime($due)) ?></b>
    </div>
    <div class="boleto-meta">
      <p class="muted">Valor do documento</p>
      <b class="boleto-amount"><?= money($order['total']) ?></b>
    </div>
  </div>

  <div class="boleto-row">
    <div><p class="muted">Beneficiário</p><p>Peças Prime — Marketplace de Autopeças</p></div>
    <div><p class="muted">Agência / Código do beneficiário</p><p>0001 / 00000-0</p></div>
    <div><p class="muted">Nosso número</p><p><?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></p></div>
    <div><p class="muted">Nº do documento</p><p><?= $order['id'] ?></p></div>
  </div>

  <div class="boleto-row">
    <div class="grow"><p class="muted">Pagador</p><p><?= e($customer['name'] ?? '') ?></p></div>
    <div><p class="muted">CPF/CNPJ</p><p><?= e($customer['cpf'] ?? '—') ?></p></div>
    <div><p class="muted">Data do pedido</p><p><?= date('d/m/Y', strtotime($order['created_at'])) ?></p></div>
  </div>

  <div class="boleto-instr">
    <p class="muted">Instruções</p>
    <p>Pagável em qualquer banco, lotérica ou aplicativo bancário até a data de vencimento. Após o pagamento, o pedido será liberado para envio. Documento gerado em ambiente de demonstração.</p>
  </div>

  <p class="linha-digitavel"><?= e($linha) ?></p>
  <?php if ($barcodeImg): ?>
    <img src="<?= $barcodeImg ?>" alt="Código de barras do boleto" class="barcode">
  <?php else: ?>
    <p class="muted">(código de barras indisponível — extensão GD não habilitada no PHP)</p>
  <?php endif; ?>
</div>

<button class="btn receipt-print-btn" onclick="window.print()">Imprimir boleto</button>

<style>
.boleto { max-width: 780px; margin: 24px auto; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 26px 30px; font-size: 13.5px; }
.boleto-head { display: flex; justify-content: space-between; align-items: center; gap: 24px; border-bottom: 2px solid #242323; padding-bottom: 12px; margin-bottom: 16px; }
.boleto-meta { text-align: center; }
.boleto-amount { font-size: 17px; }
.boleto-row { display: flex; gap: 28px; padding: 8px 0; border-bottom: 1px dashed #ddd; }
.boleto-row .grow { flex: 1; }
.boleto-row p, .boleto-instr p { margin: 2px 0; }
.boleto-instr { padding: 10px 0; border-bottom: 1px dashed #ddd; }
.linha-digitavel { font-size: 17px; font-family: monospace; letter-spacing: .5px; text-align: center; margin: 18px 0 8px; font-weight: bold; }
.barcode { display: block; margin: 0 auto; max-width: 100%; }
@media print {
  .topbar, .cats-bar, .footer, .receipt-print-btn { display: none !important; }
  body { background: #fff; }
  .boleto { border: none; margin: 0; padding: 0; max-width: 100%; }
}
</style>
<?php include __DIR__ . '/includes/footer.php'; ?>
