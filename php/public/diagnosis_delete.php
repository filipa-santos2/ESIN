<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: /diagnoses.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['diagnoses'])) $_SESSION['diagnoses'] = [];

$index = null;
for ($i = 0; $i < count($_SESSION['diagnoses']); $i++) {
  if ((int)$_SESSION['diagnoses'][$i]['diagnosis_id'] === $id) { $index = $i; break; }
}
if ($index === null) {
  header('Location: /diagnoses.php?error=Diagn%C3%B3stico+n%C3%A3o+encontrado');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  array_splice($_SESSION['diagnoses'], $index, 1);
  header('Location: /diagnoses.php?success=Diagn%C3%B3stico+apagado+com+sucesso');
  exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar diagnóstico</h1>

  <p>Tens a certeza que queres apagar este diagnóstico?</p>

  <form method="POST" action="/diagnosis_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="/diagnoses.php">Cancelar</a>
    </div>
  </form>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
