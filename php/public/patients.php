<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$patients = $pdo->query('
  SELECT "id","nome_completo","data_nascimento","sexo","telefone","email"
  FROM "Pacientes"
  ORDER BY "id" DESC
')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Pacientes</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Lista de pacientes registados no sistema (SQLite).</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/patient_create.php">Criar paciente</a>
  </div>
</section>

<section class="card">
  <?php if (empty($patients)): ?>
    <p>Não existem pacientes.</p>
  <?php else: ?>
    <table class="table table-compact">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Data de nascimento</th>
          <th>Sexo</th>
          <th>Telefone</th>
          <th>Email</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($patients as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['nome_completo']) ?></td>
            <td><?= htmlspecialchars($p['data_nascimento']) ?></td>
            <td><?= htmlspecialchars($p['sexo']) ?></td>
            <td><?= htmlspecialchars((string)($p['telefone'] ?? '—')) ?></td>
            <td><?= htmlspecialchars((string)($p['email'] ?? '—')) ?></td>
            <td>
              <div class="actions">
                <a class="btn btn-soft" href="<?= $BASE_URL ?>/patient_edit.php?id=<?= urlencode((string)$p['id']) ?>">Editar</a>
                <a class="btn btn-danger" href="<?= $BASE_URL ?>/patient_delete.php?id=<?= urlencode((string)$p['id']) ?>">Apagar</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
