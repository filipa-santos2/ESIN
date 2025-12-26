<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['diagnoses'])) $_SESSION['diagnoses'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['diseases'])) $_SESSION['diseases'] = [];

function go_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/diagnosis_create.php?error=' . urlencode($msg));
  exit;
}


function is_valid_ymd(string $date): bool {
  $dt = DateTime::createFromFormat('Y-m-d', $date);
  return $dt && $dt->format('Y-m-d') === $date;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $icd11_code = trim($_POST['icd11_code'] ?? '');
  $onset_date = trim($_POST['onset_date'] ?? '');
  $status = trim($_POST['status'] ?? '');
  $resolution_date = trim($_POST['resolution_date'] ?? '');
  $notes = trim($_POST['notes'] ?? '');

  $allowedStatus = ['active','inactive','resolved'];

  if ($patient_id <= 0 || $icd11_code === '' || $onset_date === '' || $status === '') {
    go_error('Preenche paciente, doença, onset date e status');
  }
  if (!in_array($status, $allowedStatus, true)) {
    go_error('Status inválido');
  }
  
  // ===== Validar onset_date =====
  if (!is_valid_ymd($onset_date)) {
    go_error('Onset date inválida');
  }

  $today = new DateTime('today');
  $onsetObj = new DateTime($onset_date);

  if ($onsetObj > $today) {
    go_error('Onset date não pode ser futura');
  }

  // ===== Regra status ↔ resolution_date =====
  if ($status === 'resolved' && $resolution_date === '') {
    go_error('Resolution date é obrigatória quando o status é resolved');
  }

  if ($resolution_date !== '') {
    if (!is_valid_ymd($resolution_date)) {
      go_error('Resolution date inválida');
    }

    $resObj = new DateTime($resolution_date);

    if ($resObj > $today) {
      go_error('Resolution date não pode ser futura');
    }

    if ($resObj < $onsetObj) {
      go_error('Resolution date tem de ser igual ou posterior a onset date');
    }
  }

  $resolution_date = ($resolution_date === '' ? null : $resolution_date);

  $patientExists = false;
  foreach ($_SESSION['patients'] as $p) {
    if ((int)$p['patient_id'] === $patient_id) { $patientExists = true; break; }
  }
  if (!$patientExists) go_error('Paciente inválido');

  $diseaseExists = false;
  foreach ($_SESSION['diseases'] as $d) {
    if ((string)$d['icd11_code'] === $icd11_code) { $diseaseExists = true; break; }
  }
  if (!$diseaseExists) go_error('Doença inválida');

  $maxId = 0;
  foreach ($_SESSION['diagnoses'] as $d) $maxId = max($maxId, (int)$d['diagnosis_id']);
  $newId = $maxId + 1;

  $_SESSION['diagnoses'][] = [
    'diagnosis_id' => $newId,
    'patient_id' => $patient_id,
    'icd11_code' => $icd11_code,
    'onset_date' => $onset_date,
    'status' => $status,
    'resolution_date' => $resolution_date,
    'notes' => $notes,
  ];

  header('Location: ' . $BASE_URL . '/diagnoses.php?success=' . urlencode('Diagnóstico criado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar diagnóstico</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/diagnosis_create.php">
    <div class="field">
      <label for="patient_id">Paciente</label>
      <select id="patient_id" name="patient_id" required>
        <?php foreach ($_SESSION['patients'] as $p): ?>
          <option value="<?= htmlspecialchars((string)$p['patient_id']) ?>"><?= htmlspecialchars($p['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="icd11_code">Doença</label>
      <select id="icd11_code" name="icd11_code" required>
        <?php foreach ($_SESSION['diseases'] as $d): ?>
          <option value="<?= htmlspecialchars((string)$d['icd11_code']) ?>">
            <?= htmlspecialchars($d['icd11_code'] . ' — ' . $d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="onset_date">Onset date</label>
      <input type="date" name="onset_date" id="onset_date"
            min="1900-01-01" max="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status" required>
        <option value="active">active</option>
        <option value="inactive">inactive</option>
        <option value="resolved">resolved</option>
      </select>
    </div>

    <div class="field">
      <label for="resolution_date">Resolution date (opcional)</label>
      <input type="date" name="resolution_date" id="resolution_date"
            min="1900-01-01" max="<?= date('Y-m-d') ?>">
    </div>

    <div class="field">
      <label for="notes">Notas (opcional)</label>
      <textarea id="notes" name="notes" rows="3"></textarea>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/diagnoses.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>