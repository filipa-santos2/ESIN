<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['diagnoses'])) $_SESSION['diagnoses'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['diseases'])) $_SESSION['diseases'] = [];

$patientMap = [];
foreach ($_SESSION['patients'] as $p) {
  $patientMap[(int)$p['patient_id']] = (string)$p['full_name'];
}

$diseaseMap = [];
foreach ($_SESSION['diseases'] as $d) {
  $diseaseMap[(string)$d['icd11_code']] = (string)$d['name'];
}

$diagnoses = $_SESSION['diagnoses'];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Diagnósticos</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Associação Paciente ↔ Doença com onset/status/resolution.</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/diagnosis_create.php">Adicionar diagnóstico</a>
  </div>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error" style="margin-top:12px;"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (!empty($_GET['success'])): ?>
    <div class="msg msg-success" style="margin-top:12px;"><?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Paciente</th>
        <th>Doença</th>
        <th>Onset date</th>
        <th>Status</th>
        <th>Resolution date</th>
        <th>Notas</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($diagnoses as $dg): ?>
        <tr>
          <td><?= htmlspecialchars($patientMap[(int)$dg['patient_id']] ?? '—') ?></td>
          <td><?= htmlspecialchars(($diseaseMap[(string)$dg['icd11_code']] ?? '') !== ''
                ? ($dg['icd11_code'] . ' — ' . $diseaseMap[(string)$dg['icd11_code']])
                : (string)$dg['icd11_code']) ?></td>
          <td><?= htmlspecialchars((string)($dg['onset_date'] ?? '—')) ?></td>
          <td><?= htmlspecialchars((string)($dg['status'] ?? '—')) ?></td>
          <td><?= htmlspecialchars((string)($dg['resolution_date'] ?? '—')) ?></td>
          <td><?= htmlspecialchars((string)($dg['notes'] ?? '')) ?></td>
          <td style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="<?= $BASE_URL ?>/diagnosis_edit.php?id=<?= urlencode((string)$dg['diagnosis_id']) ?>">Editar</a>
            <a class="btn btn-danger" href="<?= $BASE_URL ?>/diagnosis_delete.php?id=<?= urlencode((string)$dg['diagnosis_id']) ?>">Apagar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
