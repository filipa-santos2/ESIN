<?php
require_once __DIR__ . '/../../includes/config.php';
require_admin();
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['products'])) {
  $_SESSION['products'] = [
    [
      'product_id' => 1,
      'manufacturer_id' => 1,
      'name' => 'Acarizax',
      'type' => 'tablet',
      'concentration' => '12 SQ-HDM',
      'unit' => 'tablet',
      'notes' => 'Exemplo (dados em sessão)',
    ],
  ];
}

if (!isset($_SESSION['manufacturers'])) {
  $_SESSION['manufacturers'] = [];
}

$products = $_SESSION['products'];

// Mapa manufacturer_id -> name (para mostrar na tabela)
$manufacturerMap = [];
foreach ($_SESSION['manufacturers'] as $m) {
  $manufacturerMap[(int)$m['manufacturer_id']] = (string)$m['name'];
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Produtos</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
    <p style="margin:0;">Lista de produtos (dados em sessão nesta fase).</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/product_create.php">Adicionar produto</a>
  </div>

  <?php if (empty($_SESSION['manufacturers'])): ?>
    <div class="msg msg-error" style="margin-top:12px;">
      Ainda não existem fabricantes. Cria um fabricante primeiro para poderes associar produtos.
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <table>
    <thead>
      <tr>
        <th>Produto</th>
        <th>Fabricante</th>
        <th>Tipo</th>
        <th>Concentração</th>
        <th>Unidade</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td>
            <?= htmlspecialchars($manufacturerMap[(int)$p['manufacturer_id']] ?? '—') ?>
          </td>
          <td><?= htmlspecialchars($p['type']) ?></td>
          <td><?= htmlspecialchars($p['concentration']) ?></td>
          <td><?= htmlspecialchars($p['unit']) ?></td>
          <td>
            <div class="actions">
              <a class="btn btn-soft" href="<?= $BASE_URL ?>/product_edit.php?id=<?= urlencode((string)$p['product_id']) ?>">Editar</a>
              <a class="btn btn-danger" href="<?= $BASE_URL ?>/product_delete.php?id=<?= urlencode((string)$p['product_id']) ?>">Apagar</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>