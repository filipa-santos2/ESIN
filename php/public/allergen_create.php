<?php
require_once __DIR__ . '/../../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../../includes/auth.php';
require_admin();

/**
 * Categorias: UI em PT, BD em EN (para cumprir CHECK da tabela).
 */
$CATEGORY_PT_TO_DB = [
  'ácaros' => 'mite',
  'pólen' => 'pollen',
  'epitélio animal' => 'dander',
];

$CATEGORY_DB_TO_PT = array_flip($CATEGORY_PT_TO_DB);

function go_error(string $BASE_URL, string $msg): void {
  header('Location: ' . $BASE_URL . '/allergen_create.php?error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $codigo = trim($_POST['código_who_iuis'] ?? '');
  $especie = trim($_POST['espécie'] ?? '');
  $nome_comum = trim($_POST['nome_comum'] ?? '');
  $nome_bioquimico = trim($_POST['nome_bioquímico'] ?? '');
  $categoria_pt = trim($_POST['categoria'] ?? '');

  if ($codigo === '' || $especie === '' || $nome_comum === '' || $categoria_pt === '') {
    go_error($BASE_URL, 'Preenche os campos obrigatórios.');
  }

  if (!array_key_exists($categoria_pt, $CATEGORY_PT_TO_DB)) {
    go_error($BASE_URL, 'Categoria inválida.');
  }

  $categoria_db = $CATEGORY_PT_TO_DB[$categoria_pt];

  try {
    // Nota: nomes com acentos -> manter aspas duplas
    $stmt = $pdo->prepare('
      INSERT INTO "Alergénios"
        ("código_who_iuis", "espécie", "nome_comum", "nome_bioquímico", "categoria")
      VALUES
        (:codigo, :especie, :nome_comum, :nome_bioquimico, :categoria)
    ');
    $stmt->execute([
      ':codigo' => $codigo,
      ':especie' => $especie,
      ':nome_comum' => $nome_comum,
      ':nome_bioquimico' => ($nome_bioquimico === '' ? null : $nome_bioquimico),
      ':categoria' => $categoria_db,
    ]);

    header('Location: ' . $BASE_URL . '/allergens.php?success=' . urlencode('Alergénio criado com sucesso.'));
    exit;

  } catch (PDOException $e) {
    // Se for duplicado (PK), costuma aparecer como constraint failed
    go_error($BASE_URL, 'Não foi possível criar o alergénio. Confirma se o código já existe.');
  }
}

require_once __DIR__ . '/../../includes/header.php';

$error = $_GET['error'] ?? '';
?>
<section class="card">
  <h1>Adicionar alergénio</h1>

  <?php if ($error !== ''): ?>
    <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/allergen_create.php">
    <div class="field">
      <label for="código_who_iuis">Código WHO/IUIS</label>
      <input id="código_who_iuis" name="código_who_iuis" required placeholder="Ex: t1">
    </div>

    <div class="field">
      <label for="espécie">Espécie</label>
      <input id="espécie" name="espécie" required placeholder="Ex: Dermatophagoides pteronyssinus">
    </div>

    <div class="field">
      <label for="nome_comum">Nome comum</label>
      <input id="nome_comum" name="nome_comum" required placeholder="Ex: Ácaro do pó">
    </div>

    <div class="field">
      <label for="nome_bioquímico">Nome bioquímico (opcional)</label>
      <input id="nome_bioquímico" name="nome_bioquímico" placeholder="Ex: Der p 1">
    </div>

    <div class="field">
      <label for="categoria">Categoria</label>
      <select id="categoria" name="categoria" required>
        <option value="">— Seleciona —</option>
        <option value="ácaros">ácaros</option>
        <option value="pólen">pólen</option>
        <option value="epitélio animal">epitélio animal</option>
      </select>
      <small style="opacity:.8;">A tabela vai mostrar em PT; a BD guarda os valores normalizados.</small>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/allergens.php">Cancelar</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
