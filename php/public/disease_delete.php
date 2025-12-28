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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $del = $pdo->prepare('DELETE FROM "Doenças" WHERE "código" = ?');
    $del->execute([$codigo]);

    header('Location: ' . $BASE_URL . '/diseases.php?success=' . urlencode('Doença apagada com sucesso'));
    exit;
  } catch (PDOException $e) {
    header('Location: ' . $BASE_URL . '/disease_delete.php?código=' . urlencode($codigo) . '&error=' . urlencode('Não foi possível apagar: existem registos associados.'));
    exit;
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar doença</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($disease['designação']) ?></strong>
    (<?= htmlspecialchars($disease['código']) ?>)?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/disease_delete.php?código=<?= urlencode($codigo) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/diseases.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
