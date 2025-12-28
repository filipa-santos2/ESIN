<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Buscar fabricantes da BD
$stmt = $pdo->query('
  SELECT
    "id",
    "nome",
    "país",
    "telefone",
    "email"
  FROM "Fabricantes"
  ORDER BY "nome"
');
$manufacturers = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Fabricantes</h1>

  <div style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
    <p style="margin:0;">Lista de fabricantes registados no sistema.</p>
    <a class="btn btn-primary" href="<?= $BASE_URL ?>/manufacturer_create.php">
      Adicionar fabricante
    </a>
  </div>

  <?php if (empty($manufacturers)): ?>
    <p style="margin-top:12px; opacity:.85;">
      Ainda não existem fabricantes registados.
    </p>
  <?php endif; ?>
</section>

<section class="card">
  <?php if (!empty($manufacturers)): ?>
    <table>
      <thead>
        <tr>
          <th>Nome</th>
          <th>País</th>
          <th>Telefone</th>
          <th>Email</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($manufacturers as $m): ?>
          <tr>
            <td><?= htmlspecialchars($m['nome']) ?></td>
            <td><?= htmlspecialchars($m['país']) ?></td>
            <td><?= htmlspecialchars($m['telefone'] ?? '—') ?></td>
            <td><?= htmlspecialchars($m['email'] ?? '—') ?></td>
            <td>
              <div class="actions">
                <a class="btn btn-soft"
                   href="<?= $BASE_URL ?>/manufacturer_edit.php?id=<?= urlencode((string)$m['id']) ?>">
                  Editar
                </a>
                <a class="btn btn-danger"
                   href="<?= $BASE_URL ?>/manufacturer_delete.php?id=<?= urlencode((string)$m['id']) ?>">
                  Apagar
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
