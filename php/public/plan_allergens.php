<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$planId = (int)($_GET['plan_id'] ?? 0);
if ($planId <= 0) {
  header('Location: ' . $BASE_URL . '/plans.php?error=Plano+inv%C3%A1lido');
  exit;
}

// garantir estruturas de sessão
if (!isset($_SESSION['aitplans'])) $_SESSION['aitplans'] = [];
if (!isset($_SESSION['allergens'])) $_SESSION['allergens'] = [];
if (!isset($_SESSION['aitplan_allergens'])) $_SESSION['aitplan_allergens'] = []; 
// cada elemento: ['aitplan_id' => int, 'who_iuis_code' => string]

/**
 * Confirmar que o plano existe
 */
$planExists = false;
foreach ($_SESSION['aitplans'] as $pl) {
  if ((int)$pl['aitplan_id'] === $planId) {
    $planExists = true;
    break;
  }
}

if (!$planExists) {
  header('Location: ' . $BASE_URL . '/plans.php?error=Plano+n%C3%A3o+encontrado');
  exit;
}

/**
 * Ação: adicionar/remover associação
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add') {
  $code = trim($_POST['who_iuis_code'] ?? '');
  $start_dose_ug = $_POST['start_dose_ug'] ?? '';
  $target_dose_ug = $_POST['target_dose_ug'] ?? '';

  if ($code === '') {
    header('Location: ' . $BASE_URL . '/plan_allergens.php?plan_id=' . urlencode((string)$planId) . '&error=Escolhe+um+alerg%C3%A9nio');
    exit;
  }

  if ($start_dose_ug === '' || $target_dose_ug === '') {
    header('Location: ' . $BASE_URL . '/plan_allergens.php?plan_id=' . urlencode((string)$planId) . '&error=Preenche+as+doses');
    exit;
  }

  $start_dose_ug = (float)$start_dose_ug;
  $target_dose_ug = (float)$target_dose_ug;

  if ($start_dose_ug <= 0 || $target_dose_ug <= 0) {
    header('Location: ' . $BASE_URL . '/plan_allergens.php?plan_id=' . urlencode((string)$planId) . '&error=As+doses+t%C3%AAm+de+ser+maiores+que+0');
    exit;
  }

  if ($target_dose_ug < $start_dose_ug) {
    header('Location: ' . $BASE_URL . '/plan_allergens.php?plan_id=' . urlencode((string)$planId) . '&error=Target+dose+tem+de+ser+maior+ou+igual+%C3%A0+start+dose');
    exit;
  }

  // confirmar se o alergénio existe
  $allergenExists = false;
  foreach ($_SESSION['allergens'] as $a) {
    if ((string)$a['who_iuis_code'] === (string)$code) {
      $allergenExists = true;
      break;
    }
  }
  if (!$allergenExists) {
    header('Location: ' . $BASE_URL . '/plan_allergens.php?plan_id=' . urlencode((string)$planId) . '&error=Alerg%C3%A9nio+inv%C3%A1lido');
    exit;
  }

  // impedir duplicados (mesmo plano + mesmo alergénio)
  foreach ($_SESSION['aitplan_allergens'] as $link) {
    if ((int)$link['aitplan_id'] === $planId && (string)$link['who_iuis_code'] === (string)$code) {
      header('Location: ' . $BASE_URL . '/plan_allergens.php?plan_id=' . urlencode((string)$planId) . '&error=Esse+alerg%C3%A9nio+j%C3%A1+est%C3%A1+associado+ao+plano');
      exit;
    }
  }

  $_SESSION['aitplan_allergens'][] = [
    'aitplan_id' => $planId,
    'who_iuis_code' => $code,
    'start_dose_ug' => $start_dose_ug,
    'target_dose_ug' => $target_dose_ug,
  ];

  header('Location: ' . $BASE_URL . '/plan_allergens.php?plan_id=' . urlencode((string)$planId) . '&success=Alerg%C3%A9nio+associado+com+sucesso');
  exit;
}


  if ($action === 'remove') {
    $code = trim($_POST['who_iuis_code'] ?? '');

    $newLinks = [];
    $removed = false;

    foreach ($_SESSION['aitplan_allergens'] as $link) {
      if ((int)$link['aitplan_id'] === $planId && (string)$link['who_iuis_code'] === (string)$code) {
        $removed = true;
        continue;
      }
      $newLinks[] = $link;
    }

    $_SESSION['aitplan_allergens'] = $newLinks;

    if ($removed) {
      header('Location: ' . $BASE_URL . '/plan_allergens.php?plan_id=' . urlencode((string)$planId) . '&success=Alerg%C3%A9nio+removido+com+sucesso');
    } else {
      header('Location: ' . $BASE_URL . '/plan_allergens.php?plan_id=' . urlencode((string)$planId) . '&error=N%C3%A3o+foi+poss%C3%ADvel+remover+(associa%C3%A7%C3%A3o+n%C3%A3o+encontrada)');
    }
    exit;
  }

  header('Location: ' . $BASE_URL . '/plan_allergens.php?plan_id=' . urlencode((string)$planId) . '&error=A%C3%A7%C3%A3o+inv%C3%A1lida');
  exit;
}

/**
 * Preparar dados para mostrar:
 * - alergénios associados ao plano
 * - dropdown com alergénios ainda não associados
 */
$allergenMap = [];
foreach ($_SESSION['allergens'] as $a) {
  $allergenMap[(string)$a['who_iuis_code']] = [
    'common_name' => (string)$a['common_name'],
    'species' => (string)$a['species'],
    'category' => (string)$a['category'],
  ];
}

$linkedCodes = [];
foreach ($_SESSION['aitplan_allergens'] as $link) {
  if ((int)$link['aitplan_id'] === $planId) {
    $linkedCodes[] = (string)$link['who_iuis_code'];
  }
}

// lista de associados (detalhada)
$linkedAllergens = [];
foreach ($linkedCodes as $code) {
  if (isset($allergenMap[$code])) {
    $linkedAllergens[] = ['who_iuis_code' => $code] + $allergenMap[$code];
  } else {
    $linkedAllergens[] = ['who_iuis_code' => $code, 'common_name' => '—', 'species' => '—', 'category' => '—'];
  }
}

// opções disponíveis para adicionar (os que não estão associados)
$availableAllergens = [];
foreach ($_SESSION['allergens'] as $a) {
  $code = (string)$a['who_iuis_code'];
  if (!in_array($code, $linkedCodes, true)) {
    $availableAllergens[] = $a;
  }
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Alergénios do Plano AIT</h1>
  <p><small>Plano ID: <?= htmlspecialchars((string)$planId) ?></small></p>

  <div style="display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn btn-soft" href="<?= $BASE_URL ?>/plans.php">Voltar aos planos</a>
  </div>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error" style="margin-top:12px;"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (!empty($_GET['success'])): ?>
    <div class="msg msg-success" style="margin-top:12px;"><?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Associar novo alergénio</h2>

  <?php if (empty($_SESSION['allergens'])): ?>
    <div class="msg msg-error">
      Ainda não existem alergénios. Vai a <a href="<?= $BASE_URL ?>/allergens.php">Alergénios</a> e cria pelo menos um.
    </div>
  <?php elseif (empty($availableAllergens)): ?>
    <p>Já tens todos os alergénios disponíveis associados a este plano.</p>
  <?php else: ?>
    <form method="POST" action="<?= $BASE_URL ?>/plan_allergens.php?plan_id=<?= urlencode((string)$planId) ?>">
      <input type="hidden" name="action" value="add">

      <div class="field">
        <label for="who_iuis_code">Alergénio</label>
        <select id="who_iuis_code" name="who_iuis_code" required>
          <?php foreach ($availableAllergens as $a): ?>
            <option value="<?= htmlspecialchars($a['who_iuis_code']) ?>">
              <?= htmlspecialchars($a['who_iuis_code'] . ' — ' . $a['common_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="start_dose_ug">Start dose (µg)</label>
        <input
          id="start_dose_ug"
          name="start_dose_ug"
          type="number"
          min="0"
          step="0.01"
          required
        >
      </div>

      <div class="field">
        <label for="target_dose_ug">Target dose (µg)</label>
        <input
          id="target_dose_ug"
          name="target_dose_ug"
          type="number"
          min="0"
          step="0.01"
          required
        >
      </div>

      <button class="btn btn-primary" type="submit">Associar</button>
    </form>
  <?php endif; ?>
</section>


<section class="card">
  <h2>Alergénios associados</h2>

  <?php if (empty($linkedAllergens)): ?>
    <p>Este plano ainda não tem alergénios associados.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
         <th>Código</th>
         <th>Nome comum</th>
         <th>Espécie</th>
         <th>Categoria</th>
         <th>Start dose (µg)</th>
         <th>Target dose (µg)</th>
         <th>Ações</th>
        </tr>
      </thead>
      
      <tbody>
        <?php foreach ($linkedAllergens as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['who_iuis_code']) ?></td>
            <td><?= htmlspecialchars($a['common_name']) ?></td>
            <td><?= htmlspecialchars($a['species']) ?></td>
            <td><?= htmlspecialchars($a['category']) ?></td>
            <td><?= htmlspecialchars((string)($link['start_dose_ug'] ?? '—')) ?></td>
            <td><?= htmlspecialchars((string)($link['target_dose_ug'] ?? '—')) ?></td>
            <td>
              <form method="POST" action="<?= $BASE_URL ?>/plan_allergens.php?plan_id=<?= urlencode((string)$planId) ?>" style="margin:0;">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="who_iuis_code" value="<?= htmlspecialchars($a['who_iuis_code']) ?>">
                <button class="btn btn-danger" type="submit">Remover</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
