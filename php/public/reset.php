<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$info  = trim($_GET['info'] ?? '');

// Quem está a definir password (primeiro acesso / reset)
$pending = $_SESSION['pending_set_password'] ?? null;

// Se alguém já está logado como DOCTOR e vem aqui para mudar a própria password,
// podemos aceitar o user atual como "pending" (sem depender do login_process).
if (!$pending && !empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'doctor') {
  $pending = [
    'doctor_id' => (int)($_SESSION['user']['doctor_id'] ?? 0),
    'email'     => (string)($_SESSION['user']['email'] ?? ''),
    'full_name' => (string)($_SESSION['user']['full_name'] ?? ''),
    'role'      => 'doctor',
  ];
}

if (!$pending || empty($pending['doctor_id'])) {
  header('Location: ' . $BASE_URL . '/login.php?error=' . urlencode('Pedido inválido'));
  exit;
}

$doctorId = (int)$pending['doctor_id'];

// Confirmar que o médico existe na BD
$stmt = $pdo->prepare('SELECT "id","nome_completo","email","password_hash" FROM "Médicos" WHERE "id" = ?');
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

if (!$doctor) {
  unset($_SESSION['pending_set_password']);
  header('Location: ' . $BASE_URL . '/login.php?error=' . urlencode('Utilizador não encontrado'));
  exit;
}

// Segurança: se alguém estiver logado como MÉDICO, só pode alterar a própria password
if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'doctor') {
  if ((int)($_SESSION['user']['doctor_id'] ?? 0) !== $doctorId) {
    header('Location: ' . $BASE_URL . '/profile.php?error=' . urlencode('Não podes alterar a password de outro utilizador'));
    exit;
  }
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password = (string)($_POST['password'] ?? '');
  $confirm  = (string)($_POST['confirm'] ?? '');

  if ($password === '' || $confirm === '') {
    $error = 'Preenche ambos os campos.';
  } elseif ($password !== $confirm) {
    $error = 'As passwords não coincidem.';
  } elseif (strlen($password) < 8) {
    $error = 'A password deve ter pelo menos 8 caracteres.';
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $upd = $pdo->prepare('UPDATE "Médicos" SET "password_hash" = ? WHERE "id" = ?');
    $upd->execute([$hash, $doctorId]);

    // Depois de definir password, manda para login (fluxo original)
    unset($_SESSION['pending_set_password']);

    header('Location: ' . $BASE_URL . '/login.php?success=' . urlencode('Password definida com sucesso. Podes iniciar sessão'));
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
    Conta: <strong><?= htmlspecialchars($doctor['email']) ?></strong>
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
