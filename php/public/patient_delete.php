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

$stmt = $pdo->prepare('SELECT "id","nome_completo" FROM "Pacientes" WHERE "id" = ?');
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
  header('Location: ' . $BASE_URL . '/patients.php?error=' . urlencode('Paciente não encontrado'));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $del = $pdo->prepare('DELETE FROM "Pacientes" WHERE "id" = ?');
    $del->execute([$id]);

    header('Location: ' . $BASE_URL . '/patients.php?success=' . urlencode('Paciente apagado com sucesso'));
    exit;
  } catch (PDOException $e) {
    header('Location: ' . $BASE_URL . '/patient_delete.php?id=' . urlencode((string)$id) . '&error=' . urlencode('Não foi possível apagar: existem registos associados a este paciente.'));
    exit;
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar paciente</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($patient['nome_completo']) ?></strong>?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/patient_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/patients.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
