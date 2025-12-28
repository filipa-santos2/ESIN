<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_admin(); // ou require_login(), conforme a tua regra

require_once __DIR__ . '/../../includes/config.php';


function go_error(string $msg): void {
  global $BASE_URL;
  header('Location: ' . $BASE_URL . '/allergen_create.php?error=' . urlencode($msg));
  exit;
}

/**
 * Ajusta aqui se a tua BD tiver outros valores permitidos no CHECK.
 */
$CATEGORIES = ['mite', 'pollen', 'dander'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code = trim($_POST['código_who_iuis'] ?? '');
  $species = trim($_POST['espécie'] ?? '');
  $common = trim($_POST['nome_comum'] ?? '');
  $biochem = trim($_POST['nome_bioquímico'] ?? '');
  $category = trim($_POST['categoria'] ?? '');

  if ($code === '' || $species === '' || $common === '' || $category === '') {
    go_error('Preenche os campos obrigatórios.');
  }

  if (!in_array($category, $CATEGORIES, true)) {
    go_error('Categoria inválida.');
  }

  // Inserir
  try {
    $stmt = $pdo->prepare('
      INSERT INTO "Alergénios" ("código_who_iuis","espécie","nome_comum","nome_bioquímico","categoria")
      VALUES (:code,:species,:common,:biochem,:category)
    ');
    $stmt->execute([
      ':code' => $code,
      ':species' => $species,
      ':common' => $common,
      ':biochem' => ($biochem === '' ? null : $biochem),
      ':category' => $category,
    ]);
  } catch (PDOException $e) {
    // PK duplicada, etc.
    go_error('Não foi possível criar o alergénio. Confirma se o código já existe.');
  }

  header('Location: ' . $BASE_URL . '/allergens.php?success=' . urlencode('Alergénio criado com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar alergénio</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/allergen_create.php">
    <div class="field">
      <label for="código_who_iuis">Código WHO/IUIS</label>
      <input id="código_who_iuis" name="código_who_iuis" placeholder="Ex: t1, g6" required>
    </div>

    <div class="field">
      <label for="espécie">Espécie</label>
      <input id="espécie" name="espécie" placeholder="Ex: Dermatophagoides pteronyssinus" required>
    </div>

    <div class="field">
      <label for="nome_comum">Nome comum</label>
      <input id="nome_comum" name="nome_comum" placeholder="Ex: Ácaro do pó" required>
    </div>

    <div class="field">
      <label for="nome_bioquímico">Nome bioquímico (opcional)</label>
      <input id="nome_bioquímico" name="nome_bioquímico" placeholder="Ex: Der p 1">
    </div>

    <div class="field">
      <label for="categoria">Categoria</label>
      <select id="categoria" name="categoria" required>
          <option value="mite">ácaros</option>
          <option value="pollen">pólen</option>
          <option value="dander">epitélio animal</option> 
        
        <?php foreach ($CATEGORIES as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/allergens.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
