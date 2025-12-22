<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /manufacturers.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['manufacturers'])) {
  $_SESSION['manufacturers'] = [];
}

$index = null;
for ($i = 0; $i < count($_SESSION['manufacturers']); $i++) {
  if ((int)$_SESSION['manufacturers'][$i]['manufacturer_id'] === $id) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: /manufacturers.php?error=Fabricante+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  array_splice($_SESSION['manufacturers'], $index, 1);
  header('Location: /manufacturers.php?success=Fabricante+apagado+com+sucesso');
  exit;
}

$manufacturer = $_SESSION['manufacturers'][$index];

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar fabricante</h1>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($manufacturer['name']) ?></strong>?
  </p>

  <form method="POST" action="/manufacturer_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="/manufacturers.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
