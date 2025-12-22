<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /plans.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['aitplans'])) {
  $_SESSION['aitplans'] = [];
}
if (!isset($_SESSION['patients'])) {
  $_SESSION['patients'] = [];
}
if (!isset($_SESSION['products'])) {
  $_SESSION['products'] = [];
}

$index = null;
for ($i = 0; $i < count($_SESSION['aitplans']); $i++) {
  if ((int)$_SESSION['aitplans'][$i]['aitplan_id'] === $id) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: /plans.php?error=Plano+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $product_id = (int)($_POST['product_id'] ?? 0);
  $start_date = trim($_POST['start_date'] ?? '');
  $end_date   = trim($_POST['end_date'] ?? '');
  $status     = trim($_POST['status'] ?? '');
  $notes      = trim($_POST['notes'] ?? '');

  if ($patient_id <= 0 || $product_id <= 0 || $start_date === '' || $status === '') {
    header('Location: /plan_edit.php?id=' . urlencode((string)$id) . '&error=Preenche+os+campos+obrigat%C3%B3rios');
    exit;
  }

  $_SESSION['aitplans'][$index]['patient_id'] = $patient_id;
  $_SESSION['aitplans'][$index]['product_id'] = $product_id;
  $_SESSION['aitplans'][$index]['start_date'] = $start_date;
  $_SESSION['aitplans'][$index]['end_date']   = $end_date;
  $_SESSION['aitplans'][$index]['status']     = $status;
  $_SESSION['aitplans'][$index]['notes']      = $notes;

  header('Location: /plans.php?success=Plano+atualizado+com+sucesso');
  exit;
}

$plan = $_SESSION['aitplans'][$index];

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
            <?= ((int)$p['patient_id'] === (int)$plan['patient_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="product_id">Produto</label>
      <select id="product_id" name="product_id" required>
        <?php foreach ($_SESSION['products'] as $pr): ?>
          <option value="<?= htmlspecialchars((string)$pr['product_id']) ?>"
            <?= ((int)$pr['product_id'] === (int)$plan['product_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($pr['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="start_date">Data de início</label>
      <input id="start_date" name="start_date" type="date" value="<?= htmlspecialchars($plan['start_date']) ?>" required>
    </div>

    <div class="field">
      <label for="end_date">Data de fim</label>
      <input id="end_date" name="end_date" type="date" value="<?= htmlspecialchars($plan['end_date']) ?>">
    </div>

    <div class="field">
      <label for="status">Estado</label>
      <select id="status" name="status" required>
        <option value="active" <?= $plan['status']==='active' ? 'selected' : '' ?>>Ativo</option>
        <option value="suspended" <?= $plan['status']==='suspended' ? 'selected' : '' ?>>Suspenso</option>
        <option value="completed" <?= $plan['status']==='completed' ? 'selected' : '' ?>>Concluído</option>
      </select>
    </div>

    <div class="field">
      <label for="notes">Notas</label>
      <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($plan['notes']) ?></textarea>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="/plans.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>