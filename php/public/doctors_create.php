<?php
require_once __DIR__ . '/../../includes/config.php';

require_once __DIR__ . '/../../includes/auth.php';
require_admin();

function go_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/doctors_create.php?error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name  = trim($_POST['full_name'] ?? '');
  $license_no = trim($_POST['license_no'] ?? '');
  $specialty  = trim($_POST['specialty'] ?? '');
  $phone      = trim($_POST['phone'] ?? '');
  $email      = trim($_POST['email'] ?? '');

  if ($full_name === '' || $license_no === '' || $specialty === '' || $email === '') {
    go_error('Preenche nome, número de ordem, especialidade e email');
  }

  try {
    // duplicado por num_ordem
    $chk1 = $pdo->prepare('SELECT 1 FROM "Médicos" WHERE "num_ordem" = ? LIMIT 1');
    $chk1->execute([$license_no]);
    if ($chk1->fetchColumn()) {
      go_error('Já existe um médico com esse número de ordem');
    }

    // duplicado por email (case-insensitive)
    $chk2 = $pdo->prepare('SELECT 1 FROM "Médicos" WHERE LOWER("email") = LOWER(?) LIMIT 1');
    $chk2->execute([$email]);
    if ($chk2->fetchColumn()) {
      go_error('Já existe um médico com esse email');
    }

    // inserir com password_hash NULL (definida no primeiro acesso)
    $ins = $pdo->prepare('
      INSERT INTO "Médicos" ("nome_completo","num_ordem","especialidade","telefone","email","password_hash")
      VALUES (?,?,?,?,?,NULL)
    ');
    $ins->execute([
      $full_name,
      $license_no,
      $specialty,
      ($phone === '' ? null : $phone),
      $email
    ]);

    header('Location: ' . $BASE_URL . '/doctors.php?success=' . urlencode('Médico criado. A password é definida no primeiro acesso'));
    exit;

  } catch (Throwable $e) {
    go_error('Erro ao criar médico: ' . $e->getMessage());
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Criar médico</h1>

  <p style="opacity:.85; margin-top:6px;">
    O administrador cria a conta do médico. A password é definida pelo médico no primeiro acesso (via “Definir password”).
  </p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/doctors_create.php">
    <div class="field">
      <label for="full_name">Nome completo</label>
      <input id="full_name" name="full_name" required>
    </div>

    <div class="field">
      <label for="license_no">Número de ordem</label>
      <input id="license_no" name="license_no" required>
    </div>

    <div class="field">
      <label for="specialty">Especialidade</label>
      <input id="specialty" name="specialty" required>
    </div>

    <div class="field">
      <label for="phone">Telefone</label>
      <input id="phone" name="phone" placeholder="9xxxxxxxx">
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/doctors.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
