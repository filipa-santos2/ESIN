<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

if (session_status() === PHP_SESSION_NONE) session_start();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/diagnoses.php?error=' . urlencode('ID inválido'));
  exit;
}

$stmt = $pdo->prepare('SELECT "id","paciente_id","doença_código" FROM "Diagnósticos" WHERE "id"=?');
$stmt->execute([$id]);
$dx = $stmt->fetch();

if (!$dx) {
  header('Location: ' . $BASE_URL . '/diagnoses.php?error=' . urlencode('Diagnóstico não encontrado'));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $del = $pdo->prepare('DELETE FROM "Diagnósticos" WHERE "id"=?');
  $del->execute([$id]);

  header('Location: ' . $BASE_URL . '/diagnoses.php?success=' . urlencode('Diagnóstico apagado com sucesso'));
  exit;
}

// para mensagem
$pname = $pdo->prepare('SELECT "nome_completo" FROM "Pacientes" WHERE "id"=?');
$pname->execute([(int)$dx['paciente_id']]);
$patientName = $pname->fetchColumn() ?: '—';

$dname = $pdo->prepare('SELECT "designação" FROM "Doenças" WHERE "código"=?');
$dname->execute([(string)$dx['doença_código']]);
$diseaseName = $dname->fetchColumn() ?: '—';

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar diagnóstico</h1>

  <p>
    Tens a certeza que queres apagar o diagnóstico de
    <strong><?= htmlspecialchars((string)$patientName) ?></strong>
    para
    <strong><?= htmlspecialchars((string)$dx['doença_código'] . ' — ' . $diseaseName) ?></strong>?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/diagnosis_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/diagnoses.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
