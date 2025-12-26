<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['tests'])) $_SESSION['tests'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['allergens'])) $_SESSION['allergens'] = [];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ' . $BASE_URL . '/tests.php?error=' . urlencode('ID inválido')); exit; }

$index = null;
for ($i = 0; $i < count($_SESSION['tests']); $i++) {
  if ((int)$_SESSION['tests'][$i]['test_id'] === $id) { $index = $i; break; }
}
if ($index === null) { header('Location: ' . $BASE_URL . '/tests.php?error=' . urlencode('Teste não encontrado')); exit; }

function go_edit_error(int $id, string $msg): void {
  header('Location: ' . $BASE_URL . '/test_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

function is_valid_ymd(string $date): bool {
  $dt = DateTime::createFromFormat('Y-m-d', $date);
  return $dt && $dt->format('Y-m-d') === $date;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $who_iuis_code = trim($_POST['who_iuis_code'] ?? '');
  $test_date = trim($_POST['test_date'] ?? '');
  $test_type = trim($_POST['test_type'] ?? '');
  $test_result = trim($_POST['test_result'] ?? '');

  $allowed = ['positive', 'negative', 'inconclusive'];

  if ($patient_id <= 0 || $who_iuis_code === '' || $test_date === '' || $test_type === '' || $test_result === '') {
    go_edit_error($id, 'Preenche todos os campos');
  }

  $test_date = trim($_POST['test_date'] ?? '');

  if ($test_date === '') {
    go_edit_error($id, 'Data do teste é obrigatória');
  }

  if (!is_valid_ymd($test_date)) {
    go_edit_error($id, 'Data do teste inválida');
  }

  $testObj = new DateTime($test_date);
  $today   = new DateTime('today');

  if ($testObj > $today) {
    go_edit_error($id, 'Data do teste não pode ser futura');
  }


  if (!in_array($test_result, $allowed, true)) {
    go_edit_error($id, 'Resultado inválido');
  }

  $_SESSION['tests'][$index]['patient_id'] = $patient_id;
  $_SESSION['tests'][$index]['who_iuis_code'] = $who_iuis_code;
  $_SESSION['tests'][$index]['test_date'] = $test_date;
  $_SESSION['tests'][$index]['test_type'] = $test_type;
  $_SESSION['tests'][$index]['test_result'] = $test_result;

  header('Location: ' . $BASE_URL . '/tests.php?success=' . urlencode('Teste atualizado com sucesso'));
  exit;
}

$t = $_SESSION['tests'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar teste</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/test_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="patient_id">Paciente</label>
      <select id="patient_id" name="patient_id" required>
        <?php foreach ($_SESSION['patients'] as $p): ?>
          <option value="<?= htmlspecialchars((string)$p['patient_id']) ?>"
            <?= ((int)$p['patient_id'] === (int)$t['patient_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="who_iuis_code">Alergénio (WHO/IUIS)</label>
      <select id="who_iuis_code" name="who_iuis_code" required>
        <?php foreach ($_SESSION['allergens'] as $a): ?>
          <option value="<?= htmlspecialchars((string)$a['who_iuis_code']) ?>"
            <?= ((string)$a['who_iuis_code'] === (string)$t['who_iuis_code']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($a['who_iuis_code'] . ' — ' . ($a['common_name'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="test_date">Data do teste</label>
      <input
        id="test_date"
        name="test_date"
        type="date"
        min="1900-01-01"
        max="<?= date('Y-m-d') ?>"
        value="<?= htmlspecialchars((string)($test['test_date'] ?? '')) ?>"
        required
      >
    </div>

    <div class="field">
      <label for="test_type">Tipo de teste</label>
      <input id="test_type" name="test_type" value="<?= htmlspecialchars((string)$t['test_type']) ?>" required>
    </div>

    <div class="field">
      <label for="test_result">Resultado</label>
      <select id="test_result" name="test_result" required>
        <?php foreach (['positive','negative','inconclusive'] as $opt): ?>
          <option value="<?= $opt ?>" <?= ((string)$t['test_result'] === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/tests.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>