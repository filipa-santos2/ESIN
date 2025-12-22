<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['aitplans'])) $_SESSION['aitplans'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['products'])) $_SESSION['products'] = [];

function go_error(string $msg): void {
  header('Location: /plan_create.php?error=' . urlencode($msg));
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

  // obrigatórios
  if ($patient_id <= 0 || $product_id <= 0 || $start_date === '' || $status === '' ||
      $route === '' || $build_up_protocol === '' || $maintenance_protocol === '') {
    go_error('Preenche todos os campos obrigatórios');
  }

  // enums do modelo
  $allowedStatus = ['not_started','build_up','maintenance','concluded','cancelled','lost_follow_up'];
  $allowedRoute  = ['subcutaneous','intramuscular','sublingual','oral'];
  $allowedBuild  = ['standard','rush','semi-rush','ultra-rush','continuous'];
  $allowedMaint  = ['standard','extended-interval','shortened-interval'];

  if (!in_array($status, $allowedStatus, true)) go_error('Status inválido');
  if (!in_array($route, $allowedRoute, true)) go_error('Route inválida');
  if (!in_array($build_up_protocol, $allowedBuild, true)) go_error('Build-up protocol inválido');
  if (!in_array($maintenance_protocol, $allowedMaint, true)) go_error('Maintenance protocol inválido');

  // regra do modelo: end_date NULL ou > start_date
  if ($end_date !== '' && strcmp($end_date, $start_date) <= 0) {
    go_error('End date tem de ser posterior a start date');
  }
  $end_date = ($end_date === '' ? null : $end_date);

  // validar paciente existe
  $patientExists = false;
  foreach ($_SESSION['patients'] as $p) {
    if ((int)$p['patient_id'] === $patient_id) { $patientExists = true; break; }
  }
  if (!$patientExists) go_error('Paciente inválido');

  // validar produto existe
  $productExists = false;
  foreach ($_SESSION['products'] as $pr) {
    // aceita tanto product_id como serial_number, para não partir se o teu array for diferente
    if (isset($pr['product_id']) && (int)$pr['product_id'] === $product_id) { $productExists = true; break; }
    if (isset($pr['serial_number']) && (string)$pr['serial_number'] === (string)$product_id) { $productExists = true; break; }
  }
  if (!$productExists) go_error('Produto inválido');

  // gerar plan_id
  $maxId = 0;
  foreach ($_SESSION['aitplans'] as $pl) $maxId = max($maxId, (int)$pl['aitplan_id']);
  $newId = $maxId + 1;

  $_SESSION['aitplans'][] = [
    'aitplan_id' => $newId,
    'patient_id' => $patient_id,
    'product_id' => $product_id,
    'start_date' => $start_date,
    'end_date' => $end_date,
    'route' => $route,
    'build_up_protocol' => $build_up_protocol,
    'maintenance_protocol' => $maintenance_protocol,
    'status' => $status,
    'notes' => $notes,
];


  header('Location: /plans.php?success=' . urlencode('Plano criado com sucesso'));
  exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar plano AIT</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/plan_create.php">
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
      <label for="product_id">Produto</label>
      <select id="product_id" name="product_id" required>
        <?php foreach ($_SESSION['products'] as $pr): ?>
          <?php
            $id = $pr['product_id'] ?? $pr['serial_number'] ?? '';
            $label = '';
            if (isset($pr['serial_number'])) $label .= $pr['serial_number'];
            if (isset($pr['brand'])) $label .= ($label ? ' — ' : '') . $pr['brand'];
            if ($label === '') $label = (string)$id;
          ?>
          <option value="<?= htmlspecialchars((string)$id) ?>"><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="start_date">Start date</label>
      <input id="start_date" name="start_date" type="date" required>
    </div>

    <div class="field">
      <label for="end_date">End date (opcional)</label>
      <input id="end_date" name="end_date" type="date">
    </div>

    <div class="field">
      <label for="route">Route</label>
      <select id="route" name="route" required>
        <option value="subcutaneous">subcutaneous</option>
        <option value="intramuscular">intramuscular</option>
        <option value="sublingual">sublingual</option>
        <option value="oral">oral</option>
      </select>
    </div>

    <div class="field">
      <label for="build_up_protocol">Build-up protocol</label>
      <select id="build_up_protocol" name="build_up_protocol" required>
        <option value="standard">standard</option>
        <option value="rush">rush</option>
        <option value="semi-rush">semi-rush</option>
        <option value="ultra-rush">ultra-rush</option>
        <option value="continuous">continuous</option>
      </select>
    </div>

    <div class="field">
      <label for="maintenance_protocol">Maintenance protocol</label>
      <select id="maintenance_protocol" name="maintenance_protocol" required>
        <option value="standard">standard</option>
        <option value="extended-interval">extended-interval</option>
        <option value="shortened-interval">shortened-interval</option>
      </select>
    </div>

    <div class="field">
      <label for="status">Status</label>
      <select id="status" name="status" required>
        <option value="not_started">not_started</option>
        <option value="build_up">build_up</option>
        <option value="maintenance">maintenance</option>
        <option value="concluded">concluded</option>
        <option value="cancelled">cancelled</option>
        <option value="lost_follow_up">lost_follow_up</option>
      </select>
    </div>

    <div class="field">
      <label for="notes">Notas (opcional)</label>
      <textarea id="notes" name="notes" rows="3"></textarea>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="/plans.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
