<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /diagnoses.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['diagnoses'])) $_SESSION['diagnoses'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['diseases'])) $_SESSION['diseases'] = [];

$index = null;
for ($i = 0; $i < count($_SESSION['diagnoses']); $i++) {
  if ((int)$_SESSION['diagnoses'][$i]['diagnosis_id'] === $id) {
    $index = $i; break;
  }
}
if ($index === null) {
  header('Location: /diagnoses.php?error=Diagn%C3%B3stico+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $icd11_code = trim($_POST['icd11_code'] ?? '');
  $diagnosis_date = trim($_POST['diagnosis_date'] ?? '');
  $notes = trim($_POST['notes'] ?? '');

  if ($patient_id <= 0 || $icd11_code === '' || $diagnosis_date === '') {
    header('Location: /diagnosis_edit.php?id=' . urlencode((string)$id) . '&error=Preenche+paciente,+doen%C3%A7a+e+data');
    exit;
  }

  $_SESSION['diagnoses'][$index]['patient_id'] = $patient_id;
  $_SESSION['diagnoses'][$index]['icd11_code'] = $icd11_code;
  $_SESSION['diagnoses'][$index]['diagnosis_date'] = $diagnosis_date;
  $_SESSION['diagnoses'][$index]['notes'] = $notes;

  header('Location: /diagnoses.php?success=Diagn%C3%B3stico+atualizado+com+sucesso');
  exit;
}

$dg = $_SESSION['diagnoses'][$index];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar diagnóstico</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/diagnosis_edit.php?id=<?= urlencode((string)$id) ?>">
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
      <label for="icd11_code">Doença (ICD-11)</label>
      <select id="icd11_code" name="icd11_code" required>
        <?php foreach ($_SESSION['diseases'] as $d): ?>
          <option value="<?= htmlspecialchars($d['icd11_code']) ?>"
            <?= ((string)$d['icd11_code'] === (string)$dg['icd11_code']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['icd11_code'] . ' — ' . $d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="diagnosis_date">Data do diagnóstico</label>
      <input id="diagnosis_date" name="diagnosis_date" type="date" value="<?= htmlspecialchars($dg['diagnosis_date']) ?>" required>
    </div>

    <div class="field">
      <label for="notes">Notas</label>
      <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($dg['notes']) ?></textarea>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="/diagnoses.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>