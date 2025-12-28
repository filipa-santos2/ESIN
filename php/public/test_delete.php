<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/tests.php?error=' . urlencode('ID inválido'));
  exit;
}

$stmt = $pdo->prepare('SELECT "id","paciente_id","tipo","data" FROM "Testes" WHERE "id" = ?');
$stmt->execute([$id]);
$test = $stmt->fetch();

if (!$test) {
  header('Location: ' . $BASE_URL . '/tests.php?error=' . urlencode('Teste não encontrado'));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $pdo->prepare('DELETE FROM "Testes" WHERE "id" = ?');
  $stmt->execute([$id]);

  header('Location: ' . $BASE_URL . '/tests.php?success=' . urlencode('Teste apagado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar teste</h1>

  <p>
    Tens a certeza que queres apagar o teste
    <strong>#<?= htmlspecialchars((string)$test['id']) ?></strong>
    (<?= htmlspecialchars((string)$test['tipo']) ?>, <?= htmlspecialchars((string)$test['data']) ?>)?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/test_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/tests.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
