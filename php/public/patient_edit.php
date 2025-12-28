<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/patients.php?error=' . urlencode('ID inválido'));
  exit;
}

$stmt = $pdo->prepare('SELECT * FROM "Pacientes" WHERE "id" = ?');
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
  header('Location: ' . $BASE_URL . '/patients.php?error=' . urlencode('Paciente não encontrado'));
  exit;
}

function go_error(int $id, string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/patient_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome_completo   = trim($_POST['nome_completo'] ?? '');
  $data_nascimento = trim($_POST['data_nascimento'] ?? '');
  $sexo            = trim($_POST['sexo'] ?? '');
  $telefone        = trim($_POST['telefone'] ?? '');
  $email           = trim($_POST['email'] ?? '');

  if ($nome_completo === '' || $data_nascimento === '' || $sexo === '') {
    go_error($id, 'Preenche nome, data de nascimento e sexo.');
  }

  if (!in_array($sexo, ['F','M','O'], true)) {
    go_error($id, 'Sexo inválido (usa F, M ou O).');
  }

  $dt = DateTime::createFromFormat('Y-m-d', $data_nascimento);
  if (!$dt || $dt->format('Y-m-d') !== $data_nascimento) {
    go_error($id, 'Data de nascimento inválida (formato: AAAA-MM-DD).');
  }

  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    go_error($id, 'Email inválido.');
  }

  // duplicado email ignorando o próprio
  if ($email !== '') {
    $chk = $pdo->prepare('SELECT 1 FROM "Pacientes" WHERE lower("email") = lower(?) AND "id" <> ?');
    $chk->execute([$email, $id]);
    if ($chk->fetchColumn()) {
      go_error($id, 'Já existe um paciente com esse email.');
    }
  }

  $upd = $pdo->prepare('
    UPDATE "Pacientes"
    SET "nome_completo"=?, "data_nascimento"=?, "sexo"=?, "telefone"=?, "email"=?
    WHERE "id"=?
  ');
  $upd->execute([
    $nome_completo,
    $data_nascimento,
    $sexo,
    ($telefone === '' ? null : $telefone),
    ($email === '' ? null : $email),
    $id
  ]);

  header('Location: ' . $BASE_URL . '/patients.php?success=' . urlencode('Paciente atualizado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar paciente</h1>
  <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/patient_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="nome_completo">Nome completo</label>
      <input id="nome_completo" name="nome_completo" value="<?= htmlspecialchars($patient['nome_completo']) ?>" required>
    </div>

    <div class="field">
      <label for="data_nascimento">Data de nascimento</label>
      <input id="data_nascimento" name="data_nascimento" type="date"
             value="<?= htmlspecialchars($patient['data_nascimento']) ?>" required>
    </div>

    <div class="field">
      <label for="sexo">Sexo</label>
      <select id="sexo" name="sexo" required>
        <option value="F" <?= ($patient['sexo']==='F') ? 'selected' : '' ?>>F</option>
        <option value="M" <?= ($patient['sexo']==='M') ? 'selected' : '' ?>>M</option>
        <option value="O" <?= ($patient['sexo']==='O') ? 'selected' : '' ?>>O</option>
      </select>
    </div>

    <div class="field">
      <label for="telefone">Telefone (opcional)</label>
      <input id="telefone" name="telefone" value="<?= htmlspecialchars((string)($patient['telefone'] ?? '')) ?>">
    </div>

    <div class="field">
      <label for="email">Email (opcional)</label>
      <input id="email" name="email" type="email" value="<?= htmlspecialchars((string)($patient['email'] ?? '')) ?>">
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/patients.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
