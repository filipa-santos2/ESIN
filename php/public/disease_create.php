<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

if (session_status() === PHP_SESSION_NONE) session_start();

function go_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/disease_create.php?error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $codigo = trim($_POST['código'] ?? '');
  $designacao = trim($_POST['designação'] ?? '');

  if ($codigo === '' || $designacao === '') {
    go_error('Preenche código e designação.');
  }

  // PK único
  $chk = $pdo->prepare('SELECT 1 FROM "Doenças" WHERE "código" = ?');
  $chk->execute([$codigo]);
  if ($chk->fetchColumn()) {
    go_error('Já existe uma doença com esse código.');
  }

  $ins = $pdo->prepare('INSERT INTO "Doenças" ("código","designação") VALUES (?,?)');
  $ins->execute([$codigo, $designacao]);

  header('Location: ' . $BASE_URL . '/diseases.php?success=' . urlencode('Doença adicionada com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar doença</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/disease_create.php">
    <div class="field">
      <label for="código">Código</label>
      <input id="código" name="código" required>
    </div>

    <div class="field">
      <label for="designação">Designação</label>
      <input id="designação" name="designação" required>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/diseases.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
