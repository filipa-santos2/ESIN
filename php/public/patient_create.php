<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function go_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/patient_create.php?error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome_completo   = trim($_POST['nome_completo'] ?? '');
  $data_nascimento = trim($_POST['data_nascimento'] ?? '');
  $sexo            = trim($_POST['sexo'] ?? '');
  $telefone        = trim($_POST['telefone'] ?? '');
  $email           = trim($_POST['email'] ?? '');

  if ($nome_completo === '' || $data_nascimento === '' || $sexo === '') {
    go_error('Preenche nome, data de nascimento e sexo.');
  }

  if (!in_array($sexo, ['F','M','O'], true)) {
    go_error('Sexo inválido (usa F, M ou O).');
  }

  // validar data (YYYY-MM-DD)
  $dt = DateTime::createFromFormat('Y-m-d', $data_nascimento);
  if (!$dt || $dt->format('Y-m-d') !== $data_nascimento) {
    go_error('Data de nascimento inválida (formato: AAAA-MM-DD).');
  }

  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    go_error('Email inválido.');
  }

  // (Opcional) evitar duplicados por email
  if ($email !== '') {
    $chk = $pdo->prepare('SELECT 1 FROM "Pacientes" WHERE lower("email") = lower(?)');
    $chk->execute([$email]);
    if ($chk->fetchColumn()) {
      go_error('Já existe um paciente com esse email.');
    }
  }

  $ins = $pdo->prepare('
    INSERT INTO "Pacientes" ("nome_completo","data_nascimento","sexo","telefone","email")
    VALUES (?,?,?,?,?)
  ');
  $ins->execute([
    $nome_completo,
    $data_nascimento,
    $sexo,
    ($telefone === '' ? null : $telefone),
    ($email === '' ? null : $email),
  ]);

  header('Location: ' . $BASE_URL . '/patients.php?success=' . urlencode('Paciente criado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Criar paciente</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/patient_create.php">
    <div class="field">
      <label for="nome_completo">Nome completo</label>
      <input id="nome_completo" name="nome_completo" required>
    </div>

    <div class="field">
      <label for="data_nascimento">Data de nascimento</label>
      <input id="data_nascimento" name="data_nascimento" type="date" required>
    </div>

    <div class="field">
      <label for="sexo">Sexo</label>
      <select id="sexo" name="sexo" required>
        <option value="F">F</option>
        <option value="M">M</option>
        <option value="O">O</option>
      </select>
    </div>

    <div class="field">
      <label for="telefone">Telefone (opcional)</label>
      <input id="telefone" name="telefone">
    </div>

    <div class="field">
      <label for="email">Email (opcional)</label>
      <input id="email" name="email" type="email">
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/patients.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
