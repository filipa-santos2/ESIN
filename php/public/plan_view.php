<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/plans.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['aitplans'])) { $_SESSION['aitplans'] = []; }
if (!isset($_SESSION['patients'])) { $_SESSION['patients'] = []; }
if (!isset($_SESSION['products'])) { $_SESSION['products'] = []; }

$plan = null;
foreach ($_SESSION['aitplans'] as $pl) {
  if ((int)$pl['aitplan_id'] === $id) { $plan = $pl; break; }
}

if (!$plan) {
  header('Location: ' . $BASE_URL . '/plans.php?error=Plano+n%C3%A3o+encontrado');
  exit;
}

/* Mapas para mostrar nomes bonitos */
$patientName = '—';
foreach ($_SESSION['patients'] as $p) {
  if ((int)$p['patient_id'] === (int)$plan['patient_id']) { $patientName = $p['full_name']; break; }
}

$productLabel = (string)($plan['product_id'] ?? '—');
foreach ($_SESSION['products'] as $pr) {
  // ajusta aqui às chaves reais do teu produto (ex: 'serial_number' / 'product_id' / 'name')
  if ((string)($pr['serial_number'] ?? '') === (string)$plan['product_id'] || (string)($pr['product_id'] ?? '') === (string)$plan['product_id']) {
    $productLabel = (string)($pr['name'] ?? $pr['serial_number'] ?? $pr['product_id'] ?? $productLabel);
    break;
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
    <div>
      <h1>Plano AIT</h1>
      <p><small>ID: <?= htmlspecialchars((string)$id) ?></small></p>
    </div>

    <div class="actions">
      <a class="btn" href="<?= $BASE_URL ?>/plans.php">Voltar</a>
      <a class="btn btn-soft" href="<?= $BASE_URL ?>/plan_edit.php?id=<?= urlencode((string)$id) ?>">Editar</a>
      <a class="btn btn-danger" href="<?= $BASE_URL ?>/plan_delete.php?id=<?= urlencode((string)$id) ?>">Apagar</a>
    </div>
  </div>
</section>

<section class="card">
  <h2>Detalhes</h2>

  <table class="table table-compact">
    <tbody>
      <tr>
        <th style="width:260px;">Paciente</th>
        <td><?= htmlspecialchars($patientName) ?></td>
      </tr>
      <tr>
        <th>Produto</th>
        <td><?= htmlspecialchars($productLabel) ?></td>
      </tr>
      <tr>
        <th>Início</th>
        <td><?= htmlspecialchars((string)($plan['start_date'] ?? '—')) ?></td>
      </tr>
      <tr>
        <th>Fim</th>
        <td><?= htmlspecialchars((string)($plan['end_date'] ?? '—')) ?></td>
      </tr>
      <tr>
        <th>Via</th>
        <td><?= htmlspecialchars((string)($plan['route'] ?? '—')) ?></td>
      </tr>
      <tr>
        <th>Protocolo (indução)</th>
        <td><?= htmlspecialchars((string)($plan['build_up_protocol'] ?? '—')) ?></td>
      </tr>
      <tr>
        <th>Protocolo (manutenção)</th>
        <td><?= htmlspecialchars((string)($plan['maintenance_protocol'] ?? '—')) ?></td>
      </tr>
      <tr>
        <th>Estado</th>
        <td><?= htmlspecialchars((string)($plan['status'] ?? '—')) ?></td>
      </tr>
    </tbody>
  </table>
</section>

<?php
$notes = trim((string)($plan['notes'] ?? ''));
if ($notes !== ''):
?>
<section class="card">
  <h2>Notas</h2>
  <p><?= nl2br(htmlspecialchars($notes)) ?></p>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
