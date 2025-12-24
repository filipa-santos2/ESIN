<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['allergens'])) {
  $_SESSION['allergens'] = [
    ['who_iuis_code' => 't1', 'species' => 'Dermatophagoides pteronyssinus', 'common_name' => 'Ácaro do pó', 'category' => 'mite'],
    ['who_iuis_code' => 'g6', 'species' => 'Lolium perenne', 'common_name' => 'Azevém', 'category' => 'pollen'],
  ];
}

$allergens = $_SESSION['allergens'];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Alergénios</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
    <p style="margin:0;">Catálogo de alergénios (dados em sessão nesta fase).</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/allergen_create.php">Adicionar alergénio</a>
  </div>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Código (WHO/IUIS)</th>
        <th>Espécie</th>
        <th>Nome comum</th>
        <th>Categoria</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($allergens as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['who_iuis_code']) ?></td>
          <td><?= htmlspecialchars($a['species']) ?></td>
          <td><?= htmlspecialchars($a['common_name']) ?></td>
          <td><?= htmlspecialchars($a['category']) ?></td>
          <td style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="<?= $BASE_URL ?>/allergen_edit.php?code=<?= urlencode($a['who_iuis_code']) ?>">Editar</a>
            <a class="btn btn-danger" href="<?= $BASE_URL ?>/allergen_delete.php?code=<?= urlencode($a['who_iuis_code']) ?>">Apagar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>