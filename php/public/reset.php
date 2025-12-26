<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$email = trim($_GET['email'] ?? '');
$info  = trim($_GET['info'] ?? '');

// Validação básica
if ($email === '') {
  header('Location: ' . $BASE_URL . '/login.php?error=Pedido+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['doctors'])) {
  $_SESSION['doctors'] = [];
}

// Encontrar médico pelo email
$index = null;
for ($i = 0; $i < count($_SESSION['doctors']); $i++) {
  $dEmail = $_SESSION['doctors'][$i]['email'] ?? '';
  if ($dEmail !== '' && strtolower($dEmail) === strtolower($email)) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: ' . $BASE_URL . '/login.php?error=Utilizador+n%C3%A3o+encontrado');
  exit;
}

// Segurança: se alguém estiver logado como MÉDICO, só pode alterar a própria password
if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'doctor') {
  $loggedEmail = strtolower($_SESSION['user']['email'] ?? '');
  if ($loggedEmail !== strtolower($email)) {
    header('Location: ' . $BASE_URL . '/profile.php?error=N%C3%A3o+podes+alterar+a+password+de+outro+utilizador');
    exit;
  }
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm'] ?? '';

  if ($password === '' || $confirm === '') {
    $error = 'Preenche ambos os campos.';
  } elseif ($password !== $confirm) {
    $error = 'As passwords não coincidem.';
  } elseif (strlen($password) < 8) {
    $error = 'A password deve ter pelo menos 8 caracteres.';
  } else {
    $_SESSION['doctors'][$index]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

    // Depois de definir password, manda para login
    header('Location: ' . $BASE_URL . '/login.php?success=Password+definida+com+sucesso.+Podes+iniciar+sess%C3%A3o');
    exit;
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Definir password</h1>

  <?php if ($info !== ''): ?>
    <div class="msg msg-info"><?= htmlspecialchars($info) ?></div>
  <?php endif; ?>

  <p style="opacity:.85; margin-top:6px;">
    Conta: <strong><?= htmlspecialchars($email) ?></strong>
  </p>

  <?php if (!empty($error)): ?>
    <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="field">
      <label for="password">Nova password</label>
      <input id="password" name="password" type="password" required minlength="8">
    </div>

    <div class="field">
      <label for="confirm">Confirmar password</label>
      <input id="confirm" name="confirm" type="password" required minlength="8">
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/login.php">Voltar ao login</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
