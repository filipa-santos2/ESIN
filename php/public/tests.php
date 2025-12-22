<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['tests'])) $_SESSION['tests'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['allergens'])) $_SESSION['allergens'] = [];

$patientMap = [];
foreach ($_SESSION['patients'] as $p) {
  $patientMap[(int)$p['patient_id']] = (string)$p['full_name'];
}

$allergenMap = [];
foreach ($_SESSION['allergens'] as $a) {
  $code = (string)$a['who_iuis_code'];
  $name = (string)($a['common_name'] ?? '');
  $allergenMap[$code] = $name !== '' ? ($code . ' — ' . $name) : $code;
}

$tests = $_SESSION['tests'];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Testes a Alergénios</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Registo de AllergenTesting (Paciente ↔ Alergénio).</p>
    <a class="btn btn-primary" href="/test_create.php">Adicionar teste</a>
  </div>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error" style="margin-top:12px;"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (!empty($_GET['success'])): ?>
    <div class="msg msg-success" style="margin-top:12px;"><?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>

  <?php if (empty($_SESSION['patients']) || empty($_SESSION['allergens'])): ?>
    <div class="msg msg-error" style="margin-top:12px;">
      Para criar testes precisas de pelo menos um <b>paciente</b> e um <b>alergénio</b>.
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Paciente</th>
        <th>Alergénio</th>
        <th>Data</th>
        <th>Tipo</th>
        <th>Resultado</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tests as $t): ?>
        <tr>
          <td><?= htmlspecialchars($patientMap[(int)$t['patient_id']] ?? '—') ?></td>
          <td><?= htmlspecialchars($allergenMap[(string)$t['who_iuis_code']] ?? (string)$t['who_iuis_code']) ?></td>
          <td><?= htmlspecialchars((string)$t['test_date']) ?></td>
          <td><?= htmlspecialchars((string)$t['test_type']) ?></td>
          <td><?= htmlspecialchars((string)$t['test_result']) ?></td>
          <td style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="/test_edit.php?id=<?= urlencode((string)$t['test_id']) ?>">Editar</a>
            <a class="btn btn-danger" href="/test_delete.php?id=<?= urlencode((string)$t['test_id']) ?>">Apagar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
