<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: ' . $BASE_URL . '/doctors.php?error=ID+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['doctors'])) {
  $_SESSION['doctors'] = [];
}

// encontrar índice
$index = null;
for ($i = 0; $i < count($_SESSION['doctors']); $i++) {
  if ((int)$_SESSION['doctors'][$i]['doctor_id'] === $id) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: ' . $BASE_URL . '/doctors.php?error=M%C3%A9dico+n%C3%A3o+encontrado');
  exit;
}

// POST: apagar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  array_splice($_SESSION['doctors'], $index, 1);
  header('Location: ' . $BASE_URL . '/doctors.php?success=M%C3%A9dico+apagado+com+sucesso');
  exit;
}

// GET: mostrar confirmação
$doctor = $_SESSION['doctors'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar médico</h1>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($doctor['full_name']) ?></strong>
    (Nº ordem <?= htmlspecialchars($doctor['license_no']) ?>)?
  </p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/doctor_delete.php?id=<?= urlencode((string)$id) ?>">
    <div style="display:flex; gap:10px;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/doctors.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>