<?php
require_once __DIR__ . '/../../includes/config.php';

require_once __DIR__ . '/../../includes/auth.php';
require_admin();

function go_list_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/doctors.php?error=' . urlencode($msg));
  exit;
}

function go_self(int $id, string $key, string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/doctors_edit.php?id=' . urlencode((string)$id) . '&' . $key . '=' . urlencode($msg));
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  go_list_error('ID inválido');
}

try {
  $stmt = $pdo->prepare('
    SELECT "id","nome_completo","num_ordem","especialidade","telefone","email","password_hash"
    FROM "Médicos"
    WHERE "id" = ?
  ');
  $stmt->execute([$id]);
  $doctor = $stmt->fetch();

  if (!$doctor) {
    go_list_error('Médico não encontrado');
  }

  // Ação: repor password (força primeiro acesso)
  if (isset($_GET['action']) && $_GET['action'] === 'reset_password') {
    $rst = $pdo->prepare('UPDATE "Médicos" SET "password_hash" = NULL WHERE "id" = ?');
    $rst->execute([$id]);

    go_self($id, 'success', 'Password reposta. O médico deve definir uma nova password no primeiro acesso');
  }

  // POST: guardar alterações
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $license_no = trim($_POST['license_no'] ?? '');
    $specialty  = trim($_POST['specialty'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $email      = trim($_POST['email'] ?? '');

    if ($full_name === '' || $license_no === '' || $specialty === '' || $email === '') {
      go_self($id, 'error', 'Preenche nome, número de ordem, especialidade e email');
    }

    // duplicado por num_ordem (ignorando o próprio)
    $chk1 = $pdo->prepare('SELECT 1 FROM "Médicos" WHERE "num_ordem" = ? AND "id" <> ? LIMIT 1');
    $chk1->execute([$license_no, $id]);
    if ($chk1->fetchColumn()) {
      go_self($id, 'error', 'Já existe um médico com esse número de ordem');
    }

    // duplicado por email (case-insensitive) ignorando o próprio
    $chk2 = $pdo->prepare('SELECT 1 FROM "Médicos" WHERE LOWER("email") = LOWER(?) AND "id" <> ? LIMIT 1');
    $chk2->execute([$email, $id]);
    if ($chk2->fetchColumn()) {
      go_self($id, 'error', 'Já existe um médico com esse email');
    }

    // atualizar (sem mexer na password)
    $upd = $pdo->prepare('
      UPDATE "Médicos"
      SET "nome_completo" = ?,
          "num_ordem"     = ?,
          "especialidade" = ?,
          "telefone"      = ?,
          "email"         = ?
      WHERE "id" = ?
    ');
    $upd->execute([
      $full_name,
      $license_no,
      $specialty,
      ($phone === '' ? null : $phone),
      $email,
      $id
    ]);

    header('Location: ' . $BASE_URL . '/doctors.php?success=' . urlencode('Médico atualizado com sucesso'));
    exit;
  }

  // voltar a buscar (para refletir reset ou alterações)
  $stmt->execute([$id]);
  $doctor = $stmt->fetch();
  $hasPassword = !empty($doctor['password_hash']);

} catch (Throwable $e) {
  go_list_error('Erro: ' . $e->getMessage());
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar médico</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (!empty($_GET['success'])): ?>
    <div class="msg msg-success"><?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>

  <div class="msg msg-info" style="margin-bottom:12px;">
    Estado da conta:
    <strong><?= $hasPassword ? 'Password definida' : 'Primeiro acesso pendente' ?></strong>
  </div>

  <form method="POST" action="<?= $BASE_URL ?>/doctors_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="full_name">Nome completo</label>
      <input id="full_name" name="full_name" value="<?= htmlspecialchars($doctor['nome_completo'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label for="license_no">Número de ordem</label>
      <input id="license_no" name="license_no" value="<?= htmlspecialchars($doctor['num_ordem'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label for="specialty">Especialidade</label>
      <input id="specialty" name="specialty" value="<?= htmlspecialchars($doctor['especialidade'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label for="phone">Telefone</label>
      <input id="phone" name="phone" value="<?= htmlspecialchars($doctor['telefone'] ?? '') ?>">
    </div>

    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= htmlspecialchars($doctor['email'] ?? '') ?>" required>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/doctors.php">Cancelar</a>

      <a class="btn"
         href="<?= $BASE_URL ?>/doctors_edit.php?id=<?= urlencode((string)$id) ?>&action=reset_password"
         onclick="return confirm('Repor password? O médico terá de definir uma nova password no próximo login.');">
        Repor password
      </a>
    </div>
  </form>

  <p style="opacity:.85; margin-top:14px;">
    Nota: o administrador não define passwords. O médico define a password no primeiro acesso (ou após “Repor password”).
  </p>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
