<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['tests'])) $_SESSION['tests'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['allergens'])) $_SESSION['allergens'] = [];

function go_error(string $msg): void {
  header('Location: ' . $BASE_URL . '/test_create.php?error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $who_iuis_code = trim($_POST['who_iuis_code'] ?? '');
  $test_date = trim($_POST['test_date'] ?? '');
  $test_type = trim($_POST['test_type'] ?? '');
  $test_result = trim($_POST['test_result'] ?? '');

  $allowed = ['positive', 'negative', 'inconclusive'];

  if ($patient_id <= 0 || $who_iuis_code === '' || $test_date === '' || $test_type === '' || $test_result === '') {
    go_error('Preenche todos os campos');
  }
  if (!in_array($test_result, $allowed, true)) {
    go_error('Resultado inválido');
  }

  // validar paciente
  $patientExists = false;
  foreach ($_SESSION['patients'] as $p) {
    if ((int)$p['patient_id'] === $patient_id) { $patientExists = true; break; }
  }
  if (!$patientExists) go_error('Paciente inválido');

  // validar alergénio
  $allergenExists = false;
  foreach ($_SESSION['allergens'] as $a) {
    if ((string)$a['who_iuis_code'] === $who_iuis_code) { $allergenExists = true; break; }
  }
  if (!$allergenExists) go_error('Alergénio inválido');

  // gerar id
  $maxId = 0;
  foreach ($_SESSION['tests'] as $t) $maxId = max($maxId, (int)$t['test_id']);
  $newId = $maxId + 1;

  $_SESSION['tests'][] = [
    'test_id' => $newId,
    'patient_id' => $patient_id,
    'who_iuis_code' => $who_iuis_code,
    'test_date' => $test_date,
    'test_type' => $test_type,
    'test_result' => $test_result,
  ];

  header('Location: ' . $BASE_URL . '/tests.php?success=' . urlencode('Teste criado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar teste</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (empty($_SESSION['patients']) || empty($_SESSION['allergens'])): ?>
    <div class="msg msg-error">
      Não é possível criar testes sem pacientes e alergénios.
    </div>
  <?php else: ?>
    <form method="POST" action="<?= $BASE_URL ?>/test_create.php">
      <div class="field">
        <label for="patient_id">Paciente</label>
        <select id="patient_id" name="patient_id" required>
          <?php foreach ($_SESSION['patients'] as $p): ?>
            <option value="<?= htmlspecialchars((string)$p['patient_id']) ?>">
              <?= htmlspecialchars($p['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="who_iuis_code">Alergénio (WHO/IUIS)</label>
        <select id="who_iuis_code" name="who_iuis_code" required>
          <?php foreach ($_SESSION['allergens'] as $a): ?>
            <option value="<?= htmlspecialchars((string)$a['who_iuis_code']) ?>">
              <?= htmlspecialchars($a['who_iuis_code'] . ' — ' . ($a['common_name'] ?? '')) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="test_date">Data do teste</label>
        <input id="test_date" name="test_date" type="date" required>
      </div>

      <div class="field">
        <label for="test_type">Tipo de teste</label>
        <input id="test_type" name="test_type" placeholder="Ex: skin prick / IgE" required>
      </div>

      <div class="field">
        <label for="test_result">Resultado</label>
        <select id="test_result" name="test_result" required>
          <option value="positive">positive</option>
          <option value="negative">negative</option>
          <option value="inconclusive">inconclusive</option>
        </select>
      </div>

      <div style="display:flex; gap:10px;">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= $BASE_URL ?>/tests.php">Cancelar</a>
      </div>
    </form>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
