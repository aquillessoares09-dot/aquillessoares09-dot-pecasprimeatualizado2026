<?php
require_once __DIR__ . '/config.php';

if (current_user()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
        $error = 'Preencha todos os campos. A senha precisa ter no mínimo 6 caracteres.';
    } else {
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $error = 'Este e-mail já está cadastrado.';
        } else {
            $st = $pdo->prepare('INSERT INTO users (name, email, password, phone, address, city, state, zip) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $st->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), trim($_POST['phone'] ?? ''), trim($_POST['address'] ?? ''), trim($_POST['city'] ?? ''), trim($_POST['state'] ?? ''), trim($_POST['zip'] ?? '')]);
            $uid = $pdo->lastInsertId();
            $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $st->execute([$uid]);
            $user = $st->fetch();
            unset($user['password']);
            $_SESSION['user'] = $user;
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Criar conta';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-box">
  <h1>Criar conta</h1>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <label>Nome completo<input type="text" name="name" required></label>
    <label>E-mail<input type="email" name="email" required></label>
    <label>Senha (mín. 6 caracteres)<input type="password" name="password" required minlength="6"></label>
    <label>Telefone<input type="text" name="phone"></label>
    <label>Endereço<input type="text" name="address"></label>
    <div class="form-row">
      <label>CEP<input type="text" name="zip" id="cep" maxlength="9" inputmode="numeric" placeholder="00000-000"></label>
      <span id="cep-status"></span>
    </div>
    <div class="form-row">
      <label>Cidade<input type="text" name="city" id="cidade"></label>
      <label>UF<input type="text" name="state" id="uf" maxlength="2" style="max-width:90px"></label>
    </div>
    <button class="btn wide" type="submit">Criar conta</button>
  </form>
  <p class="muted">Já tem conta? <a href="login.php">Entrar</a></p>
</div>
<script>
(function () {
  var cep = document.getElementById('cep');
  var status = document.getElementById('cep-status');
  function buscar() {
    var v = cep.value.replace(/\D/g, '');
    if (v.length !== 8) return;
    status.textContent = 'Buscando...';
    fetch('https://viacep.com.br/ws/' + v + '/json/')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.erro) { status.textContent = 'CEP não encontrado.'; return; }
        var end = document.querySelector('input[name="address"]');
        if (end && !end.value) end.value = d.logradouro || '';
        document.getElementById('cidade').value = d.localidade || '';
        document.getElementById('uf').value = d.uf || '';
        status.textContent = 'Endereço encontrado!';
      })
      .catch(function () { status.textContent = 'Sem conexão, preencha manualmente.'; });
  }
  cep.addEventListener('blur', buscar);
  cep.addEventListener('input', function () {
    var v = cep.value.replace(/\D/g, '').substring(0, 8);
    cep.value = v.length > 5 ? v.substring(0, 5) + '-' + v.substring(5) : v;
  });
  cep.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); buscar(); } });
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
