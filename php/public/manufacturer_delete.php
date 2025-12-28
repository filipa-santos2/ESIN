<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function go_list_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/manufacturers.php?error=' . urlencode($msg));
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  go_list_error('ID inválido');
}

// Confirmar que o fabricante existe
$stmt = $pdo->prepare('
  SELECT "id","nome"
  FROM "Fabricantes"
  WHERE "id" = ?
');
$stmt->execute([$id]);
$manufacturer = $stmt->fetch();

if (!$manufacturer) {
  go_list_error('Fabricante não encontrado');
}

// POST → apagar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $del = $pdo->prepare('DELETE FROM "Fabricantes" WHERE "id" = ?');
    $del->execute([$id]);

    header('Location: ' . $BASE_URL . '/manufacturers.php?success=' . urlencode('Fabricante apagado com sucesso'));
    exit;

  } catch (PDOException $e) {
    // FK constraint (ex.: produtos associados)
    if ($e->getCode() === '23000') {
      go_list_error('Não é possível apagar o fabricante porque existem produtos associados');
    }
    // Outro erro inesperado
    go_list_error('Erro ao apagar fabricante');
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar fabricante</h1>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($manufacturer['nome']) ?></strong>?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/manufacturer_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/manufacturers.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
