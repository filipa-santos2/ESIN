<?php
require_once __DIR__ . '/../../includes/config.php';

require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/doctors.php?error=' . urlencode('ID inválido'));
  exit;
}

// buscar médico (para mostrar no ecrã)
$stmt = $pdo->prepare('SELECT "id","nome_completo","num_ordem" FROM "Médicos" WHERE "id" = ?');
$stmt->execute([$id]);
$doctor = $stmt->fetch();

if (!$doctor) {
  header('Location: ' . $BASE_URL . '/doctors.php?error=' . urlencode('Médico não encontrado'));
  exit;
}

// POST: apagar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $del = $pdo->prepare('DELETE FROM "Médicos" WHERE "id" = ?');
    $del->execute([$id]);

    header('Location: ' . $BASE_URL . '/doctors.php?success=' . urlencode('Médico apagado com sucesso'));
    exit;

  } catch (Throwable $e) {
    // muito comum: FK (visitas/planos associados)
    $msg = 'Não foi possível apagar: este médico tem registos associados (ex.: visitas/planos).';
    header('Location: ' . $BASE_URL . '/doctor_delete.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
    exit;
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar médico</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($doctor['nome_completo']) ?></strong>
    (Nº ordem <?= htmlspecialchars($doctor['num_ordem']) ?>)?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/doctor_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/doctors.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
