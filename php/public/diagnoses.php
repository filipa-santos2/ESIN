<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

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

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Diagnósticos</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
    <p style="margin:0;">Registos de diagnósticos (Paciente ↔ Doença).</p>
    <a class="btn btn-primary" href="/diagnosis_create.php">Adicionar diagnóstico</a>
  </div>

  <?php if (empty($_SESSION['patients']) || empty($_SESSION['diseases'])): ?>
    <div class="msg msg-error" style="margin-top:12px;">
      Para criar um diagnóstico precisas de pelo menos um paciente e uma doença.
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Paciente</th>
        <th>Doença</th>
        <th>Data</th>
        <th>Notas</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($diagnoses as $dg): ?>
        <tr>
          <td><?= htmlspecialchars($patientMap[(int)$dg['patient_id']] ?? '—') ?></td>
          <td><?= htmlspecialchars($diseaseMap[(string)$dg['icd11_code']] ?? $dg['icd11_code']) ?></td>
          <td><?= htmlspecialchars($dg['diagnosis_date']) ?></td>
          <td><?= htmlspecialchars($dg['notes']) ?></td>
          <td style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="/diagnosis_edit.php?id=<?= urlencode((string)$dg['diagnosis_id']) ?>">Editar</a>
            <a class="btn btn-danger" href="/diagnosis_delete.php?id=<?= urlencode((string)$dg['diagnosis_id']) ?>">Apagar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
