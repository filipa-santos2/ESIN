<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['tests'])) $_SESSION['tests'] = [];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ' . $BASE_URL . '/tests.php?error=' . urlencode('ID inválido')); exit; }

$index = null;
for ($i = 0; $i < count($_SESSION['tests']); $i++) {
  if ((int)$_SESSION['tests'][$i]['test_id'] === $id) { $index = $i; break; }
}
if ($index === null) { header('Location: ' . $BASE_URL . '/tests.php?error=' . urlencode('Teste não encontrado')); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  array_splice($_SESSION['tests'], $index, 1);
  header('Location: ' . $BASE_URL . '/tests.php?success=' . urlencode('Teste apagado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar teste</h1>
  <p>Tens a certeza que queres apagar este teste?</p>

  <form method="POST" action="<?= $BASE_URL ?>/test_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/tests.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
