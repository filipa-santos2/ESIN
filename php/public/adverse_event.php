<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['visits'])) $_SESSION['visits'] = [];
if (!isset($_SESSION['administrations'])) $_SESSION['administrations'] = [];
if (!isset($_SESSION['adverse_events'])) $_SESSION['adverse_events'] = [];

$visitId = (int)($_GET['visit_id'] ?? 0);
if ($visitId <= 0) { header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Visit inválida')); exit; }

// confirmar que existe e que é administration
$visit = null;
foreach ($_SESSION['visits'] as $v) {
  if ((int)$v['visit_id'] === $visitId) { $visit = $v; break; }
}
if (!$visit) { header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Visita não encontrada')); exit; }
if ((string)$visit['visit_type'] !== 'administration') {
  header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Evento adverso só existe para Administration'));
  exit;
}

// confirmar que existe registo em administrations
$adminExists = false;
foreach ($_SESSION['administrations'] as $a) {
  if ((int)$a['visit_id'] === $visitId) { $adminExists = true; break; }
}
if (!$adminExists) {
  header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Administration não encontrada para esta visita'));
  exit;
}

// encontrar AE existente (0..1)
$aeIndex = null;
for ($i = 0; $i < count($_SESSION['adverse_events']); $i++) {
  if ((int)$_SESSION['adverse_events'][$i]['visit_id'] === $visitId) { $aeIndex = $i; break; }
}

function redirect_ae(int $visitId, string $msg, bool $success=false): void {
  $key = $success ? 'success' : 'error';
  header('Location: ' . $BASE_URL . '/adverse_event.php?visit_id=' . urlencode((string)$visitId) . '&' . $key . '=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'save';

  if ($action === 'delete') {
    if ($aeIndex !== null) {
      array_splice($_SESSION['adverse_events'], $aeIndex, 1);
      redirect_ae($visitId, 'Evento adverso removido com sucesso', true);
    }
    redirect_ae($visitId, 'Não existe evento adverso para remover');
  }

  // save
  $type = trim($_POST['type'] ?? '');
  $onset_minutes = (int)($_POST['onset_minutes'] ?? -1);
  $outcome = trim($_POST['outcome'] ?? '');

  if ($type === '' || $outcome === '' || $onset_minutes < 0) {
    redirect_ae($visitId, 'Preenche type, onset_minutes (>=0) e outcome');
  }

  $record = [
    'visit_id' => $visitId,
    'type' => $type,
    'onset_minutes' => $onset_minutes,
    'outcome' => $outcome,
  ];

  if ($aeIndex === null) {
    $_SESSION['adverse_events'][] = $record;
  } else {
    $_SESSION['adverse_events'][$aeIndex] = $record;
  }

  redirect_ae($visitId, 'Evento adverso guardado com sucesso', true);
}

$ae = ($aeIndex !== null) ? $_SESSION['adverse_events'][$aeIndex] : ['type'=>'', 'onset_minutes'=>'', 'outcome'=>''];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Evento adverso</h1>
  <p><small>Visit ID (Administration): <?= htmlspecialchars((string)$visitId) ?></small></p>

  <div style="display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn" href="<?= $BASE_URL ?>/visits.php">Voltar às visitas</a>
    <a class="btn" href="<?= $BASE_URL ?>/visit_edit.php?id=<?= urlencode((string)$visitId) ?>">Voltar à visita</a>
  </div>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error" style="margin-top:12px;"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (!empty($_GET['success'])): ?>
    <div class="msg msg-success" style="margin-top:12px;"><?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>
</section>

<section class="card">
  <h2><?= ($aeIndex === null) ? 'Criar' : 'Editar' ?> evento adverso</h2>

  <form method="POST" action="<?= $BASE_URL ?>/adverse_event.php?visit_id=<?= urlencode((string)$visitId) ?>">
    <input type="hidden" name="action" value="save">

    <div class="field">
      <label for="type">Type</label>
      <input id="type" name="type" value="<?= htmlspecialchars((string)$ae['type']) ?>" required>
    </div>

    <div class="field">
      <label for="onset_minutes">Onset (minutos)</label>
      <input id="onset_minutes" name="onset_minutes" type="number" min="0" value="<?= htmlspecialchars((string)$ae['onset_minutes']) ?>" required>
    </div>

    <div class="field">
      <label for="outcome">Outcome</label>
      <input id="outcome" name="outcome" value="<?= htmlspecialchars((string)$ae['outcome']) ?>" required>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar</button>

      <?php if ($aeIndex !== null): ?>
        <form method="POST" action="<?= $BASE_URL ?>/adverse_event.php?visit_id=<?= urlencode((string)$visitId) ?>" style="margin:0;">
          <input type="hidden" name="action" value="delete">
          <button class="btn btn-danger" type="submit">Remover evento</button>
        </form>
      <?php endif; ?>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
