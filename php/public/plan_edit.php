<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /plans.php?error=' . urlencode('ID inválido'));
  exit;
}

if (!isset($_SESSION['aitplans'])) $_SESSION['aitplans'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['products'])) $_SESSION['products'] = [];

$index = null;
for ($i = 0; $i < count($_SESSION['aitplans']); $i++) {
  if ((int)$_SESSION['aitplans'][$i]['aitplan_id'] === $id) { $index = $i; break; }
}
if ($index === null) {
  header('Location: /plans.php?error=' . urlencode('Plano não encontrado'));
  exit;
}

function go_edit_error(int $id, string $msg): void {
  header('Location: /plan_edit.php?id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $product_id = (int)($_POST['product_id'] ?? 0);
  $start_date = trim($_POST['start_date'] ?? '');
  $end_date   = trim($_POST['end_date'] ?? '');
  $status     = trim($_POST['status'] ?? '');
  $route      = trim($_POST['route'] ?? '');
  $build_up_protocol = trim($_POST['build_up_protocol'] ?? '');
  $maintenance_protocol = trim($_POST['maintenance_protocol'] ?? '');
  $notes      = trim($_POST['notes'] ?? '');

  if ($patient_id <= 0 || $product_id <= 0 || $start_date === '' || $status === '' ||
      $route === '' || $build_up_protocol === '' || $maintenance_protocol === '') {
    go_edit_error($id, 'Preenche todos os campos obrigatórios');
  }

  $allowedStatus = ['not_started','build_up','maintenance','concluded','cancelled','lost_follow_up'];
  $allowedRoute  = ['subcutaneous','intramuscular','sublingual','oral'];
  $allowedBuild  = ['standard','rush','semi-rush','ultra-rush','continuous'];
  $allowedMaint  = ['standard','extended-interval','shortened-interval'];

  if (!in_array($status, $allowedStatus, true)) go_edit_error($id, 'Status inválido');
  if (!in_array($route, $allowedRoute, true)) go_edit_error($id, 'Route inválida');
  if (!in_array($build_up_protocol, $allowedBuild, true)) go_edit_error($id, 'Build-up protocol inválido');
  if (!in_array($maintenance_protocol, $allowedMaint, true)) go_edit_error($id, 'Maintenance protocol inválido');

  if ($end_date !== '' && strcmp($end_date, $start_date) <= 0) {
    go_edit_error($id, 'End date tem de ser posterior a start date');
  }
  $end_date = ($end_date === '' ? null : $end_date);

  $_SESSION['aitplans'][$index]['patient_id'] = $patient_id;
  $_SESSION['aitplans'][$index]['product_id'] = $product_id;
  $_SESSION['aitplans'][$index]['start_date'] = $start_date;
  $_SESSION['aitplans'][$index]['end_date'] = $end_date;
  $_SESSION['aitplans'][$index]['route'] = $route;
  $_SESSION['aitplans'][$index]['build_up_protocol'] = $build_up_protocol;
  $_SESSION['aitplans'][$index]['maintenance_protocol'] = $maintenance_protocol;
  $_SESSION['aitplans'][$index]['status'] = $status;
  $_SESSION['aitplans'][$index]['notes'] = $notes;

  header('Location: /plans.php?success=' . urlencode('Plano atualizado com sucesso'));
  exit;
}

$pl = $_SESSION['aitplans'][$index];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar plano AIT</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/plan_edit.php?id=<?= urlencode((string)$id) ?>">
    <div class="field">
      <label for="patient_id">Paciente</label>
      <select id="patient_id" name="patient_id" required>
        <?php foreach ($_SESSION['patients'] as $p): ?>
          <option value="<?= htmlspecialchars((string)$p['patient_id']) ?>"
            <?= ((int)$p['patient_id'] === (int)$pl['patient_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="product_id">Produto</label>
      <select id="product_id" name="product_id" required>
        <?php foreach ($_SESSION['products'] as $pr): ?>
          <?php
            $pid = $pr['product_id'] ?? $pr['serial_number'] ?? '';
            $label = '';
            if (isset($pr['serial_number'])) $label .= $pr['serial_number'];
            if (isset($pr['brand'])) $label .= ($label ? ' — ' : '') . $pr['brand'];
            if ($label === '') $label = (string)$pid;
          ?>
          <option value="<?= htmlspecialchars((string)$pid) ?>"
            <?= ((string)$pid === (string)($pl['product_id'] ?? '')) ? 'selected' : '' ?>>
            <?= htmlspecialchars($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="start_date">Start date</label>
      <input id="start_date" name="start_date" type="date"
             value="<?= htmlspecialchars((string)($pl['start_date'] ?? '')) ?>" required>
    </div>

    <div class="field">
      <label for="end_date">End date (opcional)</label>
      <input id="end_date" name="end_date" type="date"
             value="<?= htmlspecialchars((string)($pl['end_date'] ?? '')) ?>">
    </div>

    <div class="field">
      <label for="route">Route</label>
      <select id="route" name="route" required>
        <?php foreach (['subcutaneous','intramuscular','sublingual','oral'] as $opt): ?>
          <option value="<?= $opt ?>" <?= ((string)($pl['route'] ?? '') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="build_up_protocol">Build-up protocol</label>
      <select id="build_up_protocol" name="build_up_protocol" required>
        <?php foreach (['standard','rush','semi-rush','ultra-rush','continuous'] as $opt): ?>
          <option value="<?= $opt ?>" <?= ((string)($pl['build_up_protocol'] ?? '') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="maintenance_protocol">Maintenance protocol</label>
      <select id="maintenance_protocol" name="maintenance_protocol" required>
        <?php foreach (['standard','extended-interval','shortened-interval'] as $opt): ?>
          <option value="<?= $opt ?>" <?= ((string)($pl['maintenance_protocol'] ?? '') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status" required>
        <?php foreach (['not_started','build_up','maintenance','concluded','cancelled','lost_follow_up'] as $opt): ?>
          <option value="<?= $opt ?>" <?= ((string)($pl['status'] ?? '') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="notes">Notas (opcional)</label>
      <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars((string)($pl['notes'] ?? '')) ?></textarea>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="/plans.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>