<?php
require_once __DIR__ . '/config.php';
require_login();

// Busca o pedido (do próprio usuário ou de qualquer um, se admin)
$id = (int)($_GET['id'] ?? 0);
$u = current_user();
$st = $pdo->prepare('SELECT * FROM orders WHERE id = ?' . ($u['is_admin'] ? '' : ' AND user_id = ?'));
$st->execute($u['is_admin'] ? [$id] : [$id, $u['id']]);
$order = $st->fetch();
if (!$order) { http_response_code(404); die('Pedido não encontrado. <a href="my-orders.php">Voltar</a>'); }

$st = $pdo->prepare('SELECT u.name, u.email, u.cpf FROM users u WHERE u.id = ?');
$st->execute([$order['user_id']]);
$customer = $st->fetch() ?: [];

$st = $pdo->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
$st->execute([$id]);
$items = $st->fetchAll();

$paymentNames = ['pix' => 'Pix', 'cartao' => 'Cartão de crédito', 'boleto' => 'Boleto bancário'];
$shipping = null; // calculado abaixo a partir do subtotal
$subtotal = 0;
foreach ($items as $i) $subtotal += $i['price'] * $i['qty'];
$shipping = $subtotal >= 199 ? 0 : 29.90;

$pageTitle = 'Nota do pedido #' . $id;
include __DIR__ . '/includes/header.php';
?>

<div class="receipt">
  <div class="receipt-head">
    <div>
      <img src="<?= $BASE ?>assets/img/logo.jpg" alt="Peças Prime" class="receipt-logo">
      <h2>Peças Prime</h2>
      <p class="muted">comprovante de pedido — sem valor fiscal</p>
    </div>
    <div class="receipt-meta">
      <p><b>Pedido nº <?= $order['id'] ?></b></p>
      <p>Data: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
      <p>Status: <?= e($order['status']) ?></p>
      <p>Pagamento: <?= e($paymentNames[$order['payment']] ?? $order['payment']) ?></p>
    </div>
  </div>

  <div class="receipt-cols">
    <div>
      <h4>Cliente</h4>
      <p><?= e($customer['name'] ?? '') ?></p>
      <p class="muted"><?= e($customer['email'] ?? '') ?></p>
      <?php if (!empty($customer['cpf'])): ?><p class="muted">CPF: <?= e($customer['cpf']) ?></p><?php endif; ?>
    </div>
  </div>

  <table class="receipt-table">
    <thead>
      <tr><th>Item</th><th class="r">Qtd</th><th class="r">Unit.</th><th class="r">Total</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $i): ?>
      <tr>
        <td><?= e($i['name']) ?></td>
        <td class="r"><?= $i['qty'] ?></td>
        <td class="r"><?= money($i['price']) ?></td>
        <td class="r"><?= money($i['price'] * $i['qty']) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="sep"><td colspan="2"></td><td class="r">Subtotal</td><td class="r"><?= money($subtotal) ?></td></tr>
      <tr><td colspan="2"></td><td class="r">Frete</td><td class="r"><?= $shipping ? money($shipping) : 'Grátis' ?></td></tr>
      <tr class="grand"><td colspan="2"></td><td class="r"><b>Total</b></td><td class="r"><b><?= money($order['total']) ?></b></td></tr>
    </tbody>
  </table>

  <?php if ($order['payment'] === 'pix'):
      $pixCode = pix_payload(PIX_KEY, $order['total'], 'PEDIDO' . $order['id']);
  ?>
    <div class="receipt-pix">
      <div class="pix-qr">
        <div id="pix-qr-img"></div>
        <p class="muted">Abra o app do seu banco → Pix → Ler QR Code</p>
      </div>
      <div class="pix-copy">
        <p><b>Chave Pix (e-mail):</b> <?= e(PIX_KEY) ?></p>
        <label>Pix copia e cola:</label>
        <textarea id="pix-code" readonly rows="3"><?= e($pixCode) ?></textarea>
        <button class="btn secondary small no-print" type="button" onclick="var t=this;var ta=document.getElementById('pix-code');ta.select();navigator.clipboard.writeText(ta.value).then(function(){t.textContent='Copiado!';});">Copiar código</button>
      </div>
    </div>
  <?php endif; ?>

  <p class="receipt-footer">Obrigado por comprar na Peças Prime! Este documento é um comprovante do pedido realizado e não substitui a nota fiscal eletrônica.</p>
</div>

<button class="btn receipt-print-btn" onclick="window.print()">Imprimir / Salvar PDF</button>

<script src="<?= $BASE ?>assets/js/qrcode.min.js"></script>
<script>
(function () {
  var el = document.getElementById('pix-qr-img');
  if (!el) return;
  var qr = qrcode(0, 'M');
  qr.addData(document.getElementById('pix-code').value.trim());
  qr.make();
  el.innerHTML = qr.createImgTag(5, 10);
})();
</script>
<style>
.receipt { max-width: 720px; margin: 24px auto; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 28px 32px; }
.receipt-head { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #242323; padding-bottom: 14px; margin-bottom: 18px; }
.receipt-logo { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; }
.receipt-head h2 { font-size: 20px; margin-top: 6px; }
.receipt-meta { text-align: right; font-size: 14px; }
.receipt-cols { display: flex; gap: 40px; margin-bottom: 18px; font-size: 14px; }
.receipt-cols h4 { margin-bottom: 4px; font-size: 13px; text-transform: uppercase; color: #666; }
.receipt-table { width: 100%; border-collapse: collapse; font-size: 14px; margin: 10px 0 16px; }
.receipt-table th { text-align: left; border-bottom: 2px solid #242323; padding: 6px 8px; font-size: 12.5px; text-transform: uppercase; }
.receipt-table td { padding: 7px 8px; border-bottom: 1px solid #eee; }
.receipt-table .r { text-align: right; }
.receipt-table tr.sep td { border-top: 2px solid #242323; }
.receipt-pix { background: #f0f0f0; border-radius: 6px; padding: 14px; font-size: 14px; margin-bottom: 12px; display: flex; gap: 18px; align-items: center; flex-wrap: wrap; }
.pix-qr { text-align: center; }
.pix-qr img { background: #fff; padding: 8px; border: 1px solid #ddd; border-radius: 6px; }
.pix-copy { flex: 1; min-width: 220px; }
.pix-copy label { display: block; font-size: 12px; text-transform: uppercase; color: #666; margin: 8px 0 4px; }
.pix-copy textarea { width: 100%; font-size: 11px; font-family: monospace; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: none; word-break: break-all; }
.pix-copy .no-print { margin-top: 6px; }
@media print { .no-print { display: none !important; } }
.receipt-footer { font-size: 12.5px; color: #666; text-align: center; margin-top: 14px; }
.receipt-print-btn { display: block; margin: 0 auto 30px; }

@media print {
  .topbar, .cats-bar, .footer, .receipt-print-btn { display: none !important; }
  body { background: #fff; }
  .receipt { border: none; margin: 0; padding: 0; max-width: 100%; }
}
</style>
<?php include __DIR__ . '/includes/footer.php'; ?>
