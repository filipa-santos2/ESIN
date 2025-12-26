<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['diagnoses'])) $_SESSION['diagnoses'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['diseases'])) $_SESSION['diseases'] = [];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ' . $BASE_URL . '/diagnoses.php?error=' . urlencode('ID inválido')); exit; }

$index = null;
for ($i = 0; $i < count($_SESSION['diagnoses']); $i++) {
  if ((int)$_SESSION['diagnoses'][$i]['diagnosis_id'] === $id) { $index = $i; break; }
}
if ($index === null) { header('Location: ' . $BASE_URL . '/diagnoses.php?error=' . urlencode('Diagnóstico não encontrado')); exit; }

function go_edit_error(int $id, string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/diagnosis_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
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
    go_edit_error($id, 'Preenche paciente, doença, onset date e status');
  }
  if (!in_array($status, $allowedStatus, true)) {
    go_edit_error($id, 'Status inválido');
  }

  // ===== Validar onset_date =====
  if (!is_valid_ymd($onset_date)) {
    go_edit_error($id, 'Onset date inválida');
  }

  $today = new DateTime('today');
  $onsetObj = new DateTime($onset_date);

  if ($onsetObj > $today) {
    go_edit_error($id, 'Onset date não pode ser futura');
  }

  // ===== Regra status ↔ resolution_date =====
  if ($status === 'resolved' && $resolution_date === '') {
    go_edit_error($id, 'Resolution date é obrigatória quando o status é resolved');
  }

  if ($resolution_date !== '') {
    if (!is_valid_ymd($resolution_date)) {
      go_edit_error($id, 'Resolution date inválida');
    }

    $resObj = new DateTime($resolution_date);

    if ($resObj > $today) {
      go_edit_error($id, 'Resolution date não pode ser futura');
    }

    if ($resObj < $onsetObj) {
      go_edit_error($id, 'Resolution date tem de ser igual ou posterior a onset date');
    }
  }

  $resolution_date = ($resolution_date === '' ? null : $resolution_date);


  if ($resolution_date !== '' && strcmp($resolution_date, $onset_date) <= 0) {
    go_edit_error($id, 'Resolution date tem de ser posterior ao onset date');
  }

  $_SESSION['diagnoses'][$index]['patient_id'] = $patient_id;
  $_SESSION['diagnoses'][$index]['icd11_code'] = $icd11_code;
  $_SESSION['diagnoses'][$index]['onset_date'] = $onset_date;
  $_SESSION['diagnoses'][$index]['resolution_date'] = $resolution_date;
  $_SESSION['diagnoses'][$index]['status'] = $status;
  $_SESSION['diagnoses'][$index]['notes'] = $notes;

  header('Location: ' . $BASE_URL . '/diagnoses.php?success=' . urlencode('Diagnóstico atualizado com sucesso'));
  exit;
}

$dg = $_SESSION['diagnoses'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar diagnóstico</h1>

  <form method="POST" action="<?= $BASE_URL ?>/diagnosis_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="patient_id">Paciente</label>
      <select id="patient_id" name="patient_id" required>
        <?php foreach ($_SESSION['patients'] as $p): ?>
          <option value="<?= htmlspecialchars((string)$p['patient_id']) ?>"
            <?= ((int)$p['patient_id'] === (int)$dg['patient_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="icd11_code">Doença</label>
      <select id="icd11_code" name="icd11_code" required>
        <?php foreach ($_SESSION['diseases'] as $d): ?>
          <option value="<?= htmlspecialchars((string)$d['icd11_code']) ?>"
            <?= ((string)$d['icd11_code'] === (string)$dg['icd11_code']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['icd11_code'] . ' — ' . $d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="onset_date">Onset date</label>
      <input type="date" name="onset_date" id="onset_date"
            min="1900-01-01" max="<?= date('Y-m-d') ?>"
            value="<?= htmlspecialchars((string)($diag['onset_date'] ?? '')) ?>"
            required>    
    </div>

    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status" required>
        <?php foreach (['active','inactive','resolved'] as $opt): ?>
          <option value="<?= $opt ?>" <?= ((string)($dg['status'] ?? '') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="resolution_date">Resolution date (opcional)</label>
      <input type="date" name="resolution_date" id="resolution_date"
            min="1900-01-01" max="<?= date('Y-m-d') ?>"
            value="<?= htmlspecialchars((string)($diag['resolution_date'] ?? '')) ?>">    
    </div>
    <div class="field">
      <label for="notes">Notas (opcional)</label>
      <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars((string)($dg['notes'] ?? '')) ?></textarea>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/diagnoses.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
