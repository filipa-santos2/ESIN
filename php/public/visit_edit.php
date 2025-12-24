<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['visits'])) $_SESSION['visits'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['doctors'])) $_SESSION['doctors'] = [];
if (!isset($_SESSION['products'])) $_SESSION['products'] = [];
if (!isset($_SESSION['consultations'])) $_SESSION['consultations'] = [];
if (!isset($_SESSION['administrations'])) $_SESSION['administrations'] = [];
if (!isset($_SESSION['adverse_events'])) $_SESSION['adverse_events'] = [];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('ID inválido')); exit; }

$visitIndex = null;
for ($i = 0; $i < count($_SESSION['visits']); $i++) {
  if ((int)$_SESSION['visits'][$i]['visit_id'] === $id) { $visitIndex = $i; break; }
}
if ($visitIndex === null) { header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Visita não encontrada')); exit; }

$visit = $_SESSION['visits'][$visitIndex];
$type = (string)$visit['visit_type'];

function redirect_edit_error(int $id, string $msg): void {
  header('Location: ' . $BASE_URL . '/visit_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

function find_child_index(array $arr, int $visitId): ?int {
  for ($i = 0; $i < count($arr); $i++) {
    if ((int)$arr[$i]['visit_id'] === $visitId) return $i;
  }
  return null;
}

$consultIdx = find_child_index($_SESSION['consultations'], $id);
$adminIdx   = find_child_index($_SESSION['administrations'], $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $doctor_id  = (int)($_POST['doctor_id'] ?? 0);

  $dt_scheduled = trim($_POST['datetime_scheduled'] ?? '');
  $dt_start     = trim($_POST['datetime_start'] ?? '');
  $dt_end       = trim($_POST['datetime_end'] ?? '');

  if ($patient_id <= 0 || $doctor_id <= 0) redirect_edit_error($id, 'Seleciona paciente e médico');
  if ($dt_scheduled === '' || $dt_start === '') redirect_edit_error($id, 'Preenche data/hora agendada e início');

  // regra: end NULL ou end >= start (se quiseres estrito, troca < por <=)
  if ($dt_end !== '' && strcmp($dt_end, $dt_start) < 0) {
    redirect_edit_error($id, 'A data/hora de fim tem de ser igual ou posterior ao início');
  }

  // atualizar superclasse
  $_SESSION['visits'][$visitIndex]['patient_id'] = $patient_id;
  $_SESSION['visits'][$visitIndex]['doctor_id'] = $doctor_id;
  $_SESSION['visits'][$visitIndex]['datetime_scheduled'] = $dt_scheduled;
  $_SESSION['visits'][$visitIndex]['datetime_start'] = $dt_start;
  $_SESSION['visits'][$visitIndex]['datetime_end'] = ($dt_end === '' ? null : $dt_end);

  if ($type === 'consultation') {
    $subspecialty = trim($_POST['subspecialty'] ?? '');
    if ($subspecialty === '') redirect_edit_error($id, 'Preenche a subspecialidade');

    if ($consultIdx === null) {
      $_SESSION['consultations'][] = ['visit_id' => $id, 'subspecialty' => $subspecialty];
    } else {
      $_SESSION['consultations'][$consultIdx]['subspecialty'] = $subspecialty;
    }
  }

  if ($type === 'administration') {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $dose_no = (int)($_POST['dose_no'] ?? -1);
    $phase = trim($_POST['phase'] ?? '');
    $administration_site = trim($_POST['administration_site'] ?? '');
    $dose_ml_raw = trim($_POST['dose_ml'] ?? '');
    $observation_minutes = (int)($_POST['observation_minutes'] ?? 0);

    $dose_ml = (float)str_replace(',', '.', $dose_ml_raw);

    if ($product_id <= 0) redirect_edit_error($id, 'Escolhe um produto');

    // validar produto existe
    $productExists = false;
    foreach ($_SESSION['products'] as $pr) {
      if ((int)($pr['product_id'] ?? 0) === $product_id) { $productExists = true; break; }
    }
    if (!$productExists) redirect_edit_error($id, 'Produto inválido');

    // regra: máximo 5 administrações por produto (exclui esta própria visit)
    $count = 0;
    foreach ($_SESSION['visits'] as $v) {
      if (
        (int)$v['visit_id'] !== $id &&
        ($v['visit_type'] ?? '') === 'administration' &&
        (int)($v['product_id'] ?? 0) === $product_id
      ) {
        $count++;
      }
    }
    if ($count >= 5) {
      redirect_edit_error($id, 'Este produto já foi usado em 5 administrações. Escolhe outro frasco.');
    }

    if ($dose_no < 0) redirect_edit_error($id, 'Dose nº tem de ser >= 0');
    if ($phase !== 'build_up' && $phase !== 'maintenance') redirect_edit_error($id, 'Phase inválida');
    if ($administration_site === '') redirect_edit_error($id, 'Preenche o local de administração');
    if (!($dose_ml > 0)) redirect_edit_error($id, 'Dose (mL) tem de ser > 0');
    if ($observation_minutes <= 0) redirect_edit_error($id, 'Minutos de observação tem de ser > 0');

    // guardar product_id na superclasse
    $_SESSION['visits'][$visitIndex]['product_id'] = $product_id;

    // atualizar subclasse
    if ($adminIdx === null) {
      $_SESSION['administrations'][] = [
        'visit_id' => $id,
        'product_id' => $product_id,
        'dose_no' => $dose_no,
        'phase' => $phase,
        'administration_site' => $administration_site,
        'dose_ml' => $dose_ml,
        'observation_minutes' => $observation_minutes,
      ];
    } else {
      $_SESSION['administrations'][$adminIdx]['product_id'] = $product_id;
      $_SESSION['administrations'][$adminIdx]['dose_no'] = $dose_no;
      $_SESSION['administrations'][$adminIdx]['phase'] = $phase;
      $_SESSION['administrations'][$adminIdx]['administration_site'] = $administration_site;
      $_SESSION['administrations'][$adminIdx]['dose_ml'] = $dose_ml;
      $_SESSION['administrations'][$adminIdx]['observation_minutes'] = $observation_minutes;
    }
  }

  header('Location: ' . $BASE_URL . '/visits.php?success=' . urlencode('Visita atualizada com sucesso'));
  exit;
}

// valores da subclasse para preencher
$consult = ($consultIdx !== null) ? $_SESSION['consultations'][$consultIdx] : ['subspecialty' => ''];

$adminDefaultProduct = (int)($visit['product_id'] ?? 0);
if ($adminDefaultProduct === 0 && $adminIdx !== null) {
  $adminDefaultProduct = (int)($_SESSION['administrations'][$adminIdx]['product_id'] ?? 0);
}

$admin = ($adminIdx !== null) ? $_SESSION['administrations'][$adminIdx] : [
  'product_id' => $adminDefaultProduct,
  'dose_no' => 0,
  'phase' => 'build_up',
  'administration_site' => '',
  'dose_ml' => 0.1,
  'observation_minutes' => 30
];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar visita</h1>
  <p><small>Tipo: <?= htmlspecialchars($type) ?> | Visit ID: <?= htmlspecialchars((string)$id) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/visit_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="patient_id">Paciente</label>
      <select id="patient_id" name="patient_id" required>
        <?php foreach ($_SESSION['patients'] as $p): ?>
          <option value="<?= htmlspecialchars((string)$p['patient_id']) ?>"
            <?= ((int)$p['patient_id'] === (int)$visit['patient_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="doctor_id">Médico</label>
      <select id="doctor_id" name="doctor_id" required>
        <?php foreach ($_SESSION['doctors'] as $d): ?>
          <option value="<?= htmlspecialchars((string)$d['doctor_id']) ?>"
            <?= ((int)$d['doctor_id'] === (int)$visit['doctor_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="datetime_scheduled">Data/hora agendada</label>
      <input id="datetime_scheduled" name="datetime_scheduled" type="datetime-local"
             value="<?= htmlspecialchars((string)$visit['datetime_scheduled']) ?>" required>
    </div>

    <div class="field">
      <label for="datetime_start">Data/hora de início</label>
      <input id="datetime_start" name="datetime_start" type="datetime-local"
             value="<?= htmlspecialchars((string)$visit['datetime_start']) ?>" required>
    </div>

    <div class="field">
      <label for="datetime_end">Data/hora de fim (opcional)</label>
      <input id="datetime_end" name="datetime_end" type="datetime-local"
             value="<?= htmlspecialchars((string)($visit['datetime_end'] ?? '')) ?>">
    </div>

    <?php if ($type === 'consultation'): ?>
      <div class="card" style="margin-top:12px;">
        <h2>Consultation</h2>
        <div class="field">
          <label for="subspecialty">Subspecialidade</label>
          <input id="subspecialty" name="subspecialty"
                 value="<?= htmlspecialchars((string)($consult['subspecialty'] ?? '')) ?>" required>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($type === 'administration'): ?>
      <div class="card" style="margin-top:12px;">
        <h2>Administration</h2>

        <div class="field">
          <label for="product_id">Produto (frasco)</label>
          <select id="product_id" name="product_id" required>
            <?php foreach ($_SESSION['products'] as $p): ?>
              <option value="<?= htmlspecialchars((string)$p['product_id']) ?>"
                <?= ((int)$p['product_id'] === (int)$adminDefaultProduct) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small>Um produto pode ser usado no máximo em 5 administrações.</small>
        </div>

        <div class="field">
          <label for="dose_no">Dose nº</label>
          <input id="dose_no" name="dose_no" type="number" min="0"
                 value="<?= htmlspecialchars((string)($admin['dose_no'] ?? 0)) ?>" required>
        </div>

        <div class="field">
          <label for="phase">Phase</label>
          <select id="phase" name="phase" required>
            <option value="build_up" <?= (($admin['phase'] ?? '') === 'build_up') ? 'selected' : '' ?>>build_up</option>
            <option value="maintenance" <?= (($admin['phase'] ?? '') === 'maintenance') ? 'selected' : '' ?>>maintenance</option>
          </select>
        </div>

        <div class="field">
          <label for="administration_site">Local de administração</label>
          <input id="administration_site" name="administration_site"
                 value="<?= htmlspecialchars((string)($admin['administration_site'] ?? '')) ?>" required>
        </div>

        <div class="field">
          <label for="dose_ml">Dose (mL)</label>
          <input id="dose_ml" name="dose_ml" type="number" step="0.01" min="0.01"
                 value="<?= htmlspecialchars((string)($admin['dose_ml'] ?? 0.1)) ?>" required>
        </div>

        <div class="field">
          <label for="observation_minutes">Minutos de observação</label>
          <input id="observation_minutes" name="observation_minutes" type="number" min="1"
                 value="<?= htmlspecialchars((string)($admin['observation_minutes'] ?? 30)) ?>" required>
        </div>

        <div style="margin-top:8px;">
          <a class="btn" href="<?= $BASE_URL ?>/adverse_event.php?visit_id=<?= urlencode((string)$id) ?>">Gerir evento adverso</a>
        </div>
      </div>
    <?php endif; ?>

    <div style="display:flex; gap:10px; margin-top:12px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/visits.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
