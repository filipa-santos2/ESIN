<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

require_once __DIR__ . '/../../includes/header.php';

try {
  // mapa de pacientes para mostrar nome
  $patients = $pdo->query('SELECT "id","nome_completo" FROM "Pacientes" ORDER BY "nome_completo"')->fetchAll();
  $patientMap = [];
  foreach ($patients as $p) $patientMap[(int)$p['id']] = (string)$p['nome_completo'];

  $tests = $pdo->query('SELECT "id","paciente_id","tipo","data","resultado","notas" FROM "Testes" ORDER BY "data" DESC, "id" DESC')->fetchAll();
} catch (Throwable $e) {
  $tests = [];
  $patientMap = [];
  $loadError = $e->getMessage();
}
?>

<section class="card">
  <h1>Testes</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Lista de testes registados.</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/test_create.php">Adicionar teste</a>
  </div>

  <?php if (!empty($loadError)): ?>
    <div class="msg msg-error" style="margin-top:12px;">
      Erro ao carregar testes: <?= htmlspecialchars($loadError) ?>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <?php if (empty($tests)): ?>
    <p>Sem testes registados.</p>
  <?php else: ?>
    <table class="table table-compact">
      <thead>
        <tr>
          <th>ID</th>
          <th>Paciente</th>
          <th>Tipo</th>
          <th>Data</th>
          <th>Resultado</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tests as $t): ?>
          <tr>
            <td><?= htmlspecialchars((string)$t['id']) ?></td>
            <td><?= htmlspecialchars($patientMap[(int)$t['paciente_id']] ?? ('#' . $t['paciente_id'])) ?></td>
            <td><?= htmlspecialchars((string)$t['tipo']) ?></td>
            <td><?= htmlspecialchars((string)$t['data']) ?></td>
            <td><?= htmlspecialchars((string)($t['resultado'] ?? '—')) ?></td>
            <td>
              <div class="actions">
                <a class="btn btn-soft" href="<?= $BASE_URL ?>/test_edit.php?id=<?= urlencode((string)$t['id']) ?>">Editar</a>
                <a class="btn btn-danger" href="<?= $BASE_URL ?>/test_delete.php?id=<?= urlencode((string)$t['id']) ?>">Apagar</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
