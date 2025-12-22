<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['diagnoses'])) $_SESSION['diagnoses'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['diseases'])) $_SESSION['diseases'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $icd11_code = trim($_POST['icd11_code'] ?? '');
  $diagnosis_date = trim($_POST['diagnosis_date'] ?? '');
  $notes = trim($_POST['notes'] ?? '');

  if ($patient_id <= 0 || $icd11_code === '' || $diagnosis_date === '') {
    header('Location: /diagnosis_create.php?error=Preenche+paciente,+doen%C3%A7a+e+data');
    exit;
  }

  // validar existência de patient
  $patientExists = false;
  foreach ($_SESSION['patients'] as $p) {
    if ((int)$p['patient_id'] === $patient_id) { $patientExists = true; break; }
  }
  if (!$patientExists) {
    header('Location: /diagnosis_create.php?error=Paciente+inv%C3%A1lido');
    exit;
  }

  // validar existência de disease
  $diseaseExists = false;
  foreach ($_SESSION['diseases'] as $d) {
    if ((string)$d['icd11_code'] === (string)$icd11_code) { $diseaseExists = true; break; }
  }
  if (!$diseaseExists) {
    header('Location: /diagnosis_create.php?error=Doen%C3%A7a+inv%C3%A1lida');
    exit;
  }

  // gerar id
  $maxId = 0;
  foreach ($_SESSION['diagnoses'] as $dg) {
    $maxId = max($maxId, (int)$dg['diagnosis_id']);
  }
  $newId = $maxId + 1;

  $_SESSION['diagnoses'][] = [
    'diagnosis_id' => $newId,
    'patient_id' => $patient_id,
    'icd11_code' => $icd11_code,
    'diagnosis_date' => $diagnosis_date,
    'notes' => $notes,
  ];

  header('Location: /diagnoses.php?success=Diagn%C3%B3stico+criado+com+sucesso');
  exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar diagnóstico</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (empty($_SESSION['patients']) || empty($_SESSION['diseases'])): ?>
    <div class="msg msg-error">
      Não é possível criar diagnósticos sem pacientes e doenças.
    </div>
  <?php else: ?>
    <form method="POST" action="/diagnosis_create.php">
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
        <label for="icd11_code">Doença (ICD-11)</label>
        <select id="icd11_code" name="icd11_code" required>
          <?php foreach ($_SESSION['diseases'] as $d): ?>
            <option value="<?= htmlspecialchars($d['icd11_code']) ?>">
              <?= htmlspecialchars($d['icd11_code'] . ' — ' . $d['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="diagnosis_date">Data do diagnóstico</label>
        <input id="diagnosis_date" name="diagnosis_date" type="date" required>
      </div>

      <div class="field">
        <label for="notes">Notas (opcional)</label>
        <textarea id="notes" name="notes" rows="3"></textarea>
      </div>

      <div style="display:flex; gap:10px;">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="/diagnoses.php">Cancelar</a>
      </div>
    </form>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>