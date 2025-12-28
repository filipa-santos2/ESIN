<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

if (session_status() === PHP_SESSION_NONE) session_start();

// mapas para mostrar nomes bonitos
$patientMap = [];
foreach ($pdo->query('SELECT "id","nome_completo" FROM "Pacientes"') as $p) {
  $patientMap[(int)$p['id']] = (string)$p['nome_completo'];
}

$diseaseMap = [];
foreach ($pdo->query('SELECT "código","designação" FROM "Doenças"') as $d) {
  $diseaseMap[(string)$d['código']] = (string)$d['designação'];
}

$diagnoses = $pdo->query('
  SELECT "id","paciente_id","doença_código","data_início","data_fim","estado","notas"
  FROM "Diagnósticos"
  ORDER BY "id" DESC
')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Diagnósticos</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Diagnósticos associados a pacientes (SQLite).</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/diagnosis_create.php">Adicionar diagnóstico</a>
  </div>

  <?php if (empty($patientMap) || empty($diseaseMap)): ?>
    <div class="msg msg-error" style="margin-top:12px;">
      Para criar diagnósticos precisas de ter pelo menos um <strong>Paciente</strong> e uma <strong>Doença</strong>.
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <?php if (empty($diagnoses)): ?>
    <p>Não existem diagnósticos.</p>
  <?php else: ?>
    <table class="table table-compact">
      <thead>
        <tr>
          <th>Paciente</th>
          <th>Doença</th>
          <th>Início</th>
          <th>Fim</th>
          <th>Estado</th>
          <th>Notas</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($diagnoses as $dx): ?>
          <?php
            $pid = (int)$dx['paciente_id'];
            $code = (string)$dx['doença_código'];
            $pname = $patientMap[$pid] ?? '—';
            $dname = $diseaseMap[$code] ?? '—';
          ?>
          <tr>
            <td><?= htmlspecialchars($pname) ?></td>
            <td><?= htmlspecialchars($code . ' — ' . $dname) ?></td>
            <td><?= htmlspecialchars($dx['data_início']) ?></td>
            <td><?= htmlspecialchars($dx['data_fim'] ?: '—') ?></td>
            <td><?= htmlspecialchars($dx['estado']) ?></td>
            <td><?= htmlspecialchars((string)($dx['notas'] ?? '')) ?></td>
            <td>
              <div class="actions">
                <a class="btn btn-soft" href="<?= $BASE_URL ?>/diagnosis_edit.php?id=<?= urlencode((string)$dx['id']) ?>">Editar</a>
                <a class="btn btn-danger" href="<?= $BASE_URL ?>/diagnosis_delete.php?id=<?= urlencode((string)$dx['id']) ?>">Apagar</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
