<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role(['admin','doctor']);

if (session_status() === PHP_SESSION_NONE) session_start();

$diseases = $pdo->query('
  SELECT "código","designação"
  FROM "Doenças"
  ORDER BY "código" ASC
')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Doenças</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Lista de doenças (SQLite).</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/disease_create.php">Adicionar doença</a>
  </div>
</section>

<section class="card">
  <?php if (empty($diseases)): ?>
    <p>Não existem doenças.</p>
  <?php else: ?>
    <table class="table table-compact">
      <thead>
        <tr>
          <th>Código</th>
          <th>Designação</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($diseases as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['código']) ?></td>
            <td><?= htmlspecialchars($d['designação']) ?></td>
            <td>
              <div class="actions">
                <a class="btn btn-soft" href="<?= $BASE_URL ?>/disease_edit.php?código=<?= urlencode((string)$d['código']) ?>">Editar</a>
                <a class="btn btn-danger" href="<?= $BASE_URL ?>/disease_delete.php?código=<?= urlencode((string)$d['código']) ?>">Apagar</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
