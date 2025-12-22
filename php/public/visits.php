<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['visits'])) $_SESSION['visits'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['doctors'])) $_SESSION['doctors'] = [];
if (!isset($_SESSION['products'])) $_SESSION['products'] = [];
if (!isset($_SESSION['consultations'])) $_SESSION['consultations'] = [];
if (!isset($_SESSION['administrations'])) $_SESSION['administrations'] = [];
if (!isset($_SESSION['adverse_events'])) $_SESSION['adverse_events'] = [];

// Maps
$patientMap = [];
foreach ($_SESSION['patients'] as $p) {
  $patientMap[(int)$p['patient_id']] = (string)$p['full_name'];
}

$doctorMap = [];
foreach ($_SESSION['doctors'] as $d) {
  $doctorMap[(int)$d['doctor_id']] = (string)$d['full_name'];
}

$productMap = [];
foreach ($_SESSION['products'] as $p) {
  $productMap[(int)$p['product_id']] = (string)$p['name'];
}

// Detalhes por visit_id
$consultationByVisit = [];
foreach ($_SESSION['consultations'] as $c) {
  $consultationByVisit[(int)$c['visit_id']] = $c;
}

$adminByVisit = [];
foreach ($_SESSION['administrations'] as $a) {
  $adminByVisit[(int)$a['visit_id']] = $a;
}

$aeByVisit = [];
foreach ($_SESSION['adverse_events'] as $ae) {
  $aeByVisit[(int)$ae['visit_id']] = $ae;
}

$visits = $_SESSION['visits'];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Visitas</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Visitas (Visit) com especializações (Consultation / Administration).</p>
    <a class="btn btn-primary" href="/visit_create.php">Adicionar visita</a>
  </div>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error" style="margin-top:12px;"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (!empty($_GET['success'])): ?>
    <div class="msg msg-success" style="margin-top:12px;"><?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>

  <?php if (empty($_SESSION['patients']) || empty($_SESSION['doctors'])): ?>
    <div class="msg msg-error" style="margin-top:12px;">
      Para criar uma visita precisas de pelo menos um paciente e um médico.
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Tipo</th>
        <th>Paciente</th>
        <th>Médico</th>
        <th>Agendada</th>
        <th>Início</th>
        <th>Fim</th>
        <th>Detalhes</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($visits as $v): ?>
        <?php
          $vid = (int)$v['visit_id'];
          $type = (string)$v['visit_type'];
          $details = '—';

          if ($type === 'consultation') {
            $sub = $consultationByVisit[$vid]['subspecialty'] ?? '—';
            $details = 'Subespecialidade: ' . $sub;

          } elseif ($type === 'administration') {
            $adm = $adminByVisit[$vid] ?? null;

            // product_id pode estar na superclasse (visits) ou na subclasse (administrations)
            $pid = (int)($v['product_id'] ?? ($adm['product_id'] ?? 0));
            $prodName = $pid > 0 ? ($productMap[$pid] ?? '—') : '—';

            if ($adm) {
              $details = 'Produto: ' . $prodName;
              $details .= ' | Dose ' . $adm['dose_no'];
              $details .= ' | ' . $adm['phase'];
              $details .= ' | ' . $adm['dose_ml'] . ' mL';
              $details .= ' | ' . $adm['administration_site'];
              $details .= ' | Obs: ' . $adm['observation_minutes'] . ' min';
              $details .= isset($aeByVisit[$vid]) ? ' | EA: sim' : ' | EA: não';
            } else {
              $details = 'Produto: ' . $prodName;
            }
          }
        ?>
        <tr>
          <td><?= htmlspecialchars($type) ?></td>
          <td><?= htmlspecialchars($patientMap[(int)$v['patient_id']] ?? '—') ?></td>
          <td><?= htmlspecialchars($doctorMap[(int)$v['doctor_id']] ?? '—') ?></td>
          <td><?= htmlspecialchars((string)$v['datetime_scheduled']) ?></td>
          <td><?= htmlspecialchars((string)$v['datetime_start']) ?></td>
          <td><?= htmlspecialchars(($v['datetime_end'] ?? null) ? (string)$v['datetime_end'] : '—') ?></td>
          <td><?= htmlspecialchars($details) ?></td>
          <td style="display:flex; gap:8px; flex-wrap:wrap;">
            <?php if ($type === 'administration'): ?>
              <a class="btn" href="/adverse_event.php?visit_id=<?= urlencode((string)$vid) ?>">Evento adverso</a>
            <?php endif; ?>
            <a class="btn" href="/visit_edit.php?id=<?= urlencode((string)$vid) ?>">Editar</a>
            <a class="btn btn-danger" href="/visit_delete.php?id=<?= urlencode((string)$vid) ?>">Apagar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
