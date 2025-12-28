<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_admin();

require_once __DIR__ . '/../../includes/config.php';


$code = trim($_GET['code'] ?? '');
if ($code === '') {
  header('Location: ' . $BASE_URL . '/allergens.php?error=' . urlencode('Código inválido'));
  exit;
}

$stmt = $pdo->prepare('
  SELECT "código_who_iuis","nome_comum"
  FROM "Alergénios"
  WHERE "código_who_iuis" = :code
');
$stmt->execute([':code' => $code]);
$allergen = $stmt->fetch();

if (!$allergen) {
  header('Location: ' . $BASE_URL . '/allergens.php?error=' . urlencode('Alergénio não encontrado'));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $del = $pdo->prepare('DELETE FROM "Alergénios" WHERE "código_who_iuis" = :code');
    $del->execute([':code' => $code]);
  } catch (PDOException $e) {
    header('Location: ' . $BASE_URL . '/allergens.php?error=' . urlencode('Não foi possível apagar (pode estar a ser usado noutros registos).'));
    exit;
  }

  header('Location: ' . $BASE_URL . '/allergens.php?success=' . urlencode('Alergénio apagado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Apagar alergénio</h1>

  <p>
    Tens a certeza que queres apagar:
    <strong><?= htmlspecialchars($allergen['nome_comum']) ?></strong>
    (<?= htmlspecialchars($allergen['código_who_iuis']) ?>)?
  </p>

  <form method="POST" action="<?= $BASE_URL ?>/allergen_delete.php?code=<?= urlencode($code) ?>">
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-danger" type="submit">Confirmar</button>
      <a class="btn" href="<?= $BASE_URL ?>/allergens.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
