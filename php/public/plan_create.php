<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $product_id = (int)($_POST['product_id'] ?? 0);
  $start_date = trim($_POST['start_date'] ?? '');
  $end_date   = trim($_POST['end_date'] ?? '');
  $status     = trim($_POST['status'] ?? '');
  $notes      = trim($_POST['notes'] ?? '');

  if ($patient_id <= 0 || $product_id <= 0 || $start_date === '' || $status === '') {
    header('Location: /plan_create.php?error=Preenche+todos+os+campos+obrigat%C3%B3rios');
    exit;
  }

  // gerar id
  $maxId = 0;
  foreach ($_SESSION['aitplans'] as $pl) {
    $maxId = max($maxId, (int)$pl['aitplan_id']);
  }
  $newId = $maxId + 1;

  $_SESSION['aitplans'][] = [
    'aitplan_id' => $newId,
    'patient_id' => $patient_id,
    'product_id' => $product_id,
    'start_date' => $start_date,
    'end_date'   => $end_date,
    'status'     => $status,
    'notes'      => $notes,
  ];

  header('Location: /plans.php?success=Plano+criado+com+sucesso');
  exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Criar plano AIT</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (empty($_SESSION['patients']) || empty($_SESSION['products'])): ?>
    <div class="msg msg-error">
      Não é possível criar planos sem pacientes e produtos.
    </div>
  <?php else: ?>
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
            <option value="<?= htmlspecialchars((string)$pr['product_id']) ?>">
              <?= htmlspecialchars($pr['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="start_date">Data de início</label>
        <input id="start_date" name="start_date" type="date" required>
      </div>

      <div class="field">
        <label for="end_date">Data de fim (opcional)</label>
        <input id="end_date" name="end_date" type="date">
      </div>

      <div class="field">
        <label for="status">Estado</label>
        <select id="status" name="status" required>
          <option value="active">Ativo</option>
          <option value="suspended">Suspenso</option>
          <option value="completed">Concluído</option>
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
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>