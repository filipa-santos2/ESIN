<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

if (session_status() === PHP_SESSION_NONE) session_start();

$codigo = trim($_GET['código'] ?? '');
if ($codigo === '') {
  header('Location: ' . $BASE_URL . '/diseases.php?error=' . urlencode('Código inválido'));
  exit;
}

$stmt = $pdo->prepare('SELECT "código","designação" FROM "Doenças" WHERE "código" = ?');
$stmt->execute([$codigo]);
$disease = $stmt->fetch();

if (!$disease) {
  header('Location: ' . $BASE_URL . '/diseases.php?error=' . urlencode('Doença não encontrada'));
  exit;
}

function go_error(string $codigo, string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/disease_edit.php?código=' . urlencode($codigo) . '&error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $designacao = trim($_POST['designação'] ?? '');
  if ($designacao === '') {
    go_error($codigo, 'Preenche a designação.');
  }

  $upd = $pdo->prepare('UPDATE "Doenças" SET "designação" = ? WHERE "código" = ?');
  $upd->execute([$designacao, $codigo]);

  header('Location: ' . $BASE_URL . '/diseases.php?success=' . urlencode('Doença atualizada com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar doença</h1>
  <p><small>Código: <?= htmlspecialchars($codigo) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/disease_edit.php?código=<?= urlencode($codigo) ?>">
    <div class="field">
      <label>Código</label>
      <div class="input-like"><?= htmlspecialchars($disease['código']) ?></div>
    </div>

    <div class="field">
      <label for="designação">Designação</label>
      <input id="designação" name="designação" value="<?= htmlspecialchars($disease['designação']) ?>" required>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/diseases.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
