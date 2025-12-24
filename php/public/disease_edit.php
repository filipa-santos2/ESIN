<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$code = strtoupper(trim($_GET['code'] ?? ''));
if ($code === '') {
  header('Location: ' . $BASE_URL . '/diseases.php?error=C%C3%B3digo+inv%C3%A1lido');
  exit;
}

if (!isset($_SESSION['diseases'])) {
  $_SESSION['diseases'] = [];
}

// encontrar índice pelo icd11_code
$index = null;
for ($i = 0; $i < count($_SESSION['diseases']); $i++) {
  if ((string)$_SESSION['diseases'][$i]['icd11_code'] === (string)$code) {
    $index = $i;
    break;
  }
}

if ($index === null) {
  header('Location: ' . $BASE_URL . '/diseases.php?error=Doen%C3%A7a+n%C3%A3o+encontrada');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Não deixamos editar o código (PK), só o nome
  $name = trim($_POST['name'] ?? '');

  if ($name === '') {
    header('Location: ' . $BASE_URL . '/disease_edit.php?code=' . urlencode($code) . '&error=Preenche+o+nome');
    exit;
  }

  // UNIQUE(name) (ignorando o próprio registo)
  foreach ($_SESSION['diseases'] as $d) {
    if ((string)$d['icd11_code'] !== (string)$code &&
        strtolower((string)$d['name']) === strtolower((string)$name)) {
      header('Location: ' . $BASE_URL . '/disease_edit.php?code=' . urlencode($code) . '&error=J%C3%A1+existe+uma+doen%C3%A7a+com+esse+nome');
      exit;
    }
  }

  $_SESSION['diseases'][$index]['name'] = $name;

  header('Location: ' . $BASE_URL . '/diseases.php?success=Doen%C3%A7a+atualizada+com+sucesso');
  exit;
}

$disease = $_SESSION['diseases'][$index];

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Editar doença</h1>
  <p><small>Código ICD-11: <?= htmlspecialchars($code) ?></small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/disease_edit.php?code=<?= urlencode($code) ?>">
    <div class="field">
      <label for="icd11_code">Código ICD-11</label>
      <input id="icd11_code" value="<?= htmlspecialchars($disease['icd11_code']) ?>" disabled>
      <small>O código é identificador e não é editável.</small>
    </div>

    <div class="field">
      <label for="name">Nome</label>
      <input id="name" name="name" value="<?= htmlspecialchars($disease['name']) ?>" required>
    </div>

    <div style="display:flex; gap:10px;">
      <button class="btn btn-primary" type="submit">Guardar alterações</button>
      <a class="btn" href="<?= $BASE_URL ?>/diseases.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
