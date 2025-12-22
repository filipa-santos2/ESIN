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

// mapas para mostrar nomes
$patientMap = [];
foreach ($_SESSION['patients'] as $p) {
  $patientMap[(int)$p['patient_id']] = (string)$p['full_name'];
}

$productMap = [];
foreach ($_SESSION['products'] as $pr) {
  $productMap[(int)$pr['product_id']] = (string)$pr['name'];
}

$plans = $_SESSION['aitplans'];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Planos de Imunoterapia (AIT)</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
    <p style="margin:0;">Planos AIT registados.</p>
    <a class="btn btn-primary" href="/plan_create.php">Criar plano</a>
  </div>

  <?php if (empty($_SESSION['patients']) || empty($_SESSION['products'])): ?>
    <div class="msg msg-error" style="margin-top:12px;">
      Para criar um plano é necessário ter pelo menos um paciente e um produto registados.
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Paciente</th>
        <th>Produto</th>
        <th>Início</th>
        <th>Fim</th>
        <th>Estado</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($plans as $pl): ?>
        <tr>
          <td><?= htmlspecialchars($patientMap[(int)$pl['patient_id']] ?? '—') ?></td>
          <td><?= htmlspecialchars($productMap[(int)$pl['product_id']] ?? '—') ?></td>
          <td><?= htmlspecialchars($pl['start_date']) ?></td>
          <td><?= htmlspecialchars($pl['end_date'] ?: '—') ?></td>
          <td><?= htmlspecialchars($pl['status']) ?></td>
          <td style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="/plan_allergens.php?plan_id=<?= urlencode((string)$pl['aitplan_id']) ?>">Alergénios</a>
            <a class="btn" href="/plan_edit.php?id=<?= urlencode((string)$pl['aitplan_id']) ?>">Editar</a>
            <a class="btn btn-danger" href="/plan_delete.php?id=<?= urlencode((string)$pl['aitplan_id']) ?>">Apagar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
