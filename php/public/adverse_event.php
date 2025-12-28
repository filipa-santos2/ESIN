<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

function go_err(string $baseUrl, int $id, string $msg): void {
  header('Location: ' . $baseUrl . '/adverse_event.php?visita_id=' . urlencode((string)$id) . '&error=' . urlencode($msg));
  exit;
}

$visita_id = (int)($_GET['visita_id'] ?? 0);
if ($visita_id <= 0) {
  header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('ID inválido'));
  exit;
}

try {
  // só faz sentido se for administração
  $st = $pdo->prepare('SELECT "id","tipo" FROM "Visitas" WHERE "id"=?');
  $st->execute([$visita_id]);
  $visit = $st->fetch();
  if (!$visit) {
    header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Visita não encontrada'));
    exit;
  }
  if (($visit['tipo'] ?? '') !== 'administração') {
    header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Evento adverso só existe em visitas de administração'));
    exit;
  }

  $st2 = $pdo->prepare('SELECT * FROM "Evento adverso" WHERE "visita_id"=?');
  $st2->execute([$visita_id]);
  $ae = $st2->fetch();
} catch (Throwable $e) {
  header('Location: ' . $BASE_URL . '/visits.php?error=' . urlencode('Erro: ' . $e->getMessage()));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'save';

  if ($action === 'delete') {
    try {
      $del = $pdo->prepare('DELETE FROM "Evento adverso" WHERE "visita_id"=?');
      $del->execute([$visita_id]);
      header('Location: ' . $BASE_URL . '/visits.php?success=' . urlencode('Evento adverso removido'));
      exit;
    } catch (Throwable $e) {
      go_err($BASE_URL, $visita_id, 'Erro ao remover: ' . $e->getMessage());
    }
  }

  // save
  $tipo = trim($_POST['tipo'] ?? '');
  $inicio_min = (int)($_POST['início_minutos'] ?? -1);
  $desfecho = trim($_POST['desfecho'] ?? '');
  if ($desfecho === '') $desfecho = null;

  if ($tipo === '') go_err($BASE_URL, $visita_id, 'Preenche o tipo');
  if ($inicio_min < 0) go_err($BASE_URL, $visita_id, 'Início (minutos) tem de ser >= 0');

  try {
    if ($ae) {
      $up = $pdo->prepare('
        UPDATE "Evento adverso"
        SET "tipo"=?, "início_minutos"=?, "desfecho"=?
        WHERE "visita_id"=?
      ');
      $up->execute([$tipo, $inicio_min, $desfecho, $visita_id]);
    } else {
      $ins = $pdo->prepare('
        INSERT INTO "Evento adverso" ("visita_id","tipo","início_minutos","desfecho")
        VALUES (?,?,?,?)
      ');
      $ins->execute([$visita_id, $tipo, $inicio_min, $desfecho]);
    }

    header('Location: ' . $BASE_URL . '/visits.php?success=' . urlencode('Evento adverso guardado'));
    exit;
  } catch (Throwable $e) {
    go_err($BASE_URL, $visita_id, 'Erro ao guardar: ' . $e->getMessage());
  }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Evento adverso</h1>
  <p><small>Visita #<?= htmlspecialchars((string)$visita_id) ?> (administração)</small></p>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= $BASE_URL ?>/adverse_event.php?visita_id=<?= urlencode((string)$visita_id) ?>">
    <input type="hidden" name="action" value="save">

    <div class="field">
      <label for="tipo">Tipo</label>
      <input id="tipo" name="tipo" value="<?= htmlspecialchars($ae['tipo'] ?? '') ?>" required>
    </div>

    <div class="field">
      <label for="início_minutos">Início (minutos)</label>
      <input id="início_minutos" name="início_minutos" type="number" min="0"
             value="<?= htmlspecialchars((string)($ae['início_minutos'] ?? 0)) ?>" required>
    </div>

    <div class="field">
      <label for="desfecho">Desfecho (opcional)</label>
      <input id="desfecho" name="desfecho" value="<?= htmlspecialchars($ae['desfecho'] ?? '') ?>">
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn" href="<?= $BASE_URL ?>/visits.php">Voltar</a>

      <?php if ($ae): ?>
        <button class="btn btn-danger" type="submit" name="action" value="delete"
                onclick="return confirm('Remover evento adverso desta visita?');">
          Remover
        </button>
      <?php endif; ?>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
