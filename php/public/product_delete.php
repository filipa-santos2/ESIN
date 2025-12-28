<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/products.php?error=' . urlencode('ID inválido'));
  exit;
}

$stmt = $pdo->prepare('SELECT "id","nome" FROM "Produtos" WHERE "id" = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
  header('Location: ' . $BASE_URL . '/products.php?error=' . urlencode('Produto não encontrado'));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $del = $pdo->prepare('DELETE FROM "Produtos" WHERE "id" = ?');
  $del->execute([$id]);

  header('Location: ' . $BASE_URL . '/products.php?success=' . urlencode('Produto apagado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar produto</h1>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($product['nome']) ?></strong>?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/product_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/products.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
