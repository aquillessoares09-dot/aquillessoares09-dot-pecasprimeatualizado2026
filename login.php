<?php
require_once __DIR__ . '/config.php';

if (current_user()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([trim($_POST['email'])]);
    if ($user = $st->fetch()) {
        if (password_verify($_POST['password'], $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            header('Location: ' . ($_GET['redirect'] ?? 'index.php'));
            exit;
        }
    }
    $error = 'E-mail ou senha incorretos.';
}

$pageTitle = 'Entrar';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-box">
  <h1>Entrar</h1>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <label>E-mail<input type="email" name="email" required></label>
    <label>Senha<input type="password" name="password" required></label>
    <button class="btn wide" type="submit">Entrar</button>
  </form>
  <p class="muted">Não tem conta? <a href="register.php">Cadastre-se</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
