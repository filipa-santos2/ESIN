<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/config.php';

$CATEGORY_LABELS = [
  'pollen' => 'Pólen',
  'mite'   => 'Ácaros',
  'dander' => 'Epitélio animal',
];

$CATEGORY_OPTIONS = ['pollen', 'mite', 'dander']; // ordem que queres no select
 
$allergens = $pdo->query('
  SELECT
    "código_who_iuis",
    "espécie",
    "nome_comum",
    "nome_bioquímico",
    "categoria"
  FROM "Alergénios"
  ORDER BY "nome_comum" ASC
')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Alergénios</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Lista de alergénios registados no sistema.</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/allergen_create.php">Adicionar alergénio</a>
  </div>
</section>

<section class="card">
  <table class="table table-compact">
    <thead>
      <tr>
        <th>Código (WHO/IUIS)</th>
        <th>Espécie</th>
        <th>Nome comum</th>
        <th>Nome bioquímico</th>
        <th>Categoria</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($allergens as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['código_who_iuis']) ?></td>
          <td><?= htmlspecialchars($a['espécie']) ?></td>
          <td><?= htmlspecialchars($a['nome_comum']) ?></td>
          <td><?= htmlspecialchars($a['nome_bioquímico'] ?? '—') ?></td>
              <? $cat = (string)($a['categoria'] ?? ''); ?>
          <td><?= htmlspecialchars($CATEGORY_LABELS[$cat] ?? $cat) ?></td>
           <td>
            <div class="actions">
              <a class="btn btn-soft"
                 href="<?= $BASE_URL ?>/allergen_edit.php?code=<?= urlencode($a['código_who_iuis']) ?>">Editar</a>
              <a class="btn btn-danger"
                 href="<?= $BASE_URL ?>/allergen_delete.php?code=<?= urlencode($a['código_who_iuis']) ?>">Apagar</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
