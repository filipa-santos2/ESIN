<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$stmt = $pdo->query('
  SELECT
    p."id",
    p."nome",
    p."tipo",
    p."concentração",
    p."unidade",
    f."nome" AS fabricante_nome
  FROM "Produtos" p
  JOIN "Fabricantes" f ON f."id" = p."fabricante_id"
  ORDER BY p."id" DESC
');
$products = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Produtos</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
    <p style="margin:0;">Lista de produtos (SQLite).</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/product_create.php">Adicionar produto</a>
  </div>
</section>

<section class="card">
  <?php if (empty($products)): ?>
    <p>Não existem produtos.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Nome</th>
          <th>Tipo</th>
          <th>Concentração</th>
          <th>Unidade</th>
          <th>Fabricante</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['nome']) ?></td>
            <td><?= htmlspecialchars($p['tipo']) ?></td>
            <td><?= htmlspecialchars((string)($p['concentração'] ?? '—')) ?></td>
            <td><?= htmlspecialchars((string)($p['unidade'] ?? '—')) ?></td>
            <td><?= htmlspecialchars($p['fabricante_nome']) ?></td>
            <td>
              <div class="actions">
                <a class="btn btn-soft" href="<?= $BASE_URL ?>/product_edit.php?id=<?= urlencode((string)$p['id']) ?>">Editar</a>
                <a class="btn btn-danger" href="<?= $BASE_URL ?>/product_delete.php?id=<?= urlencode((string)$p['id']) ?>">Apagar</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
