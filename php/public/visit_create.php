<?php
require_once __DIR__ . '/../../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['visits'])) $_SESSION['visits'] = [];
if (!isset($_SESSION['patients'])) $_SESSION['patients'] = [];
if (!isset($_SESSION['doctors'])) $_SESSION['doctors'] = [];
if (!isset($_SESSION['products'])) $_SESSION['products'] = [];
if (!isset($_SESSION['consultations'])) $_SESSION['consultations'] = [];
if (!isset($_SESSION['administrations'])) $_SESSION['administrations'] = [];
if (!isset($_SESSION['adverse_events'])) $_SESSION['adverse_events'] = [];

function redirect_with_error(string $msg): void {
  header('Location: ' . $BASE_URL . '/visit_create.php?error=' . urlencode($msg));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $visit_type = trim($_POST['visit_type'] ?? '');
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $doctor_id  = (int)($_POST['doctor_id'] ?? 0);

  $dt_scheduled = trim($_POST['datetime_scheduled'] ?? '');
  $dt_start     = trim($_POST['datetime_start'] ?? '');
  $dt_end       = trim($_POST['datetime_end'] ?? ''); // pode vir vazio

  // só obrigatório para administration
  $product_id = (int)($_POST['product_id'] ?? 0);

  if ($visit_type !== 'consultation' && $visit_type !== 'administration') {
    redirect_with_error('Tipo de visita inválido');
  }

  if ($patient_id <= 0 || $doctor_id <= 0) {
    redirect_with_error('Seleciona paciente e médico');
  }

  if ($dt_scheduled === '' || $dt_start === '') {
    redirect_with_error('Preenche data/hora agendada e data/hora de início');
  }

  // Regra do modelo: datetime_end é NULL ou end >= start
  if ($dt_end !== '' && strcmp($dt_end, $dt_start) < 0) {
    redirect_with_error('A data/hora de fim tem de ser igual ou posterior ao início');
  }

  // Validar existência FK
  $patientExists = false;
  foreach ($_SESSION['patients'] as $p) {
    if ((int)$p['patient_id'] === $patient_id) { $patientExists = true; break; }
  }
  if (!$patientExists) redirect_with_error('Paciente inválido');

  $doctorExists = false;
  foreach ($_SESSION['doctors'] as $d) {
    if ((int)$d['doctor_id'] === $doctor_id) { $doctorExists = true; break; }
  }
  if (!$doctorExists) redirect_with_error('Médico inválido');

  // Se for administration: produto obrigatório + existe + regra das 5 utilizações
  if ($visit_type === 'administration') {
    if ($product_id <= 0) {
      redirect_with_error('Escolhe um produto');
    }

    $productExists = false;
    foreach ($_SESSION['products'] as $pr) {
      if ((int)($pr['product_id'] ?? 0) === $product_id) { $productExists = true; break; }
    }
    if (!$productExists) {
      redirect_with_error('Produto inválido');
    }

    // Regra: máximo 5 administrações por produto
    $count = 0;
    foreach ($_SESSION['visits'] as $v) {
      if (($v['visit_type'] ?? '') === 'administration' && (int)($v['product_id'] ?? 0) === $product_id) {
        $count++;
      }
    }
    if ($count >= 5) {
      redirect_with_error('Este produto já foi usado em 5 administrações. Escolhe outro frasco.');
    }
  }

  // Gerar visit_id
  $maxId = 0;
  foreach ($_SESSION['visits'] as $v) $maxId = max($maxId, (int)$v['visit_id']);
  $newId = $maxId + 1;

  // Guardar Visit (superclasse)
  $visitRow = [
    'visit_id' => $newId,
    'patient_id' => $patient_id,
    'doctor_id' => $doctor_id,
    'visit_type' => $visit_type,
    'datetime_scheduled' => $dt_scheduled,
    'datetime_start' => $dt_start,
    'datetime_end' => ($dt_end === '' ? null : $dt_end),
  ];

  // adicionar product_id só em administration (modelo)
  if ($visit_type === 'administration') {
    $visitRow['product_id'] = $product_id;
  }

  $_SESSION['visits'][] = $visitRow;

  // Guardar subclasse (disjoint, complete)
  if ($visit_type === 'consultation') {
    $subspecialty = trim($_POST['subspecialty'] ?? '');
    if ($subspecialty === '') {
      array_pop($_SESSION['visits']);
      redirect_with_error('Preenche a subspecialidade');
    }

    $_SESSION['consultations'][] = [
      'visit_id' => $newId,
      'subspecialty' => $subspecialty,
    ];
  }

  if ($visit_type === 'administration') {
    $dose_no = (int)($_POST['dose_no'] ?? -1);
    $phase = trim($_POST['phase'] ?? '');
    $administration_site = trim($_POST['administration_site'] ?? '');
    $dose_ml_raw = trim($_POST['dose_ml'] ?? '');
    $observation_minutes = (int)($_POST['observation_minutes'] ?? 0);

    $dose_ml = (float)str_replace(',', '.', $dose_ml_raw);

    if ($dose_no < 0) {
      array_pop($_SESSION['visits']);
      redirect_with_error('Dose nº tem de ser >= 0');
    }
    if ($phase !== 'build_up' && $phase !== 'maintenance') {
      array_pop($_SESSION['visits']);
      redirect_with_error('Phase inválida');
    }
    if ($administration_site === '') {
      array_pop($_SESSION['visits']);
      redirect_with_error('Preenche o local de administração');
    }
    if (!($dose_ml > 0)) {
      array_pop($_SESSION['visits']);
      redirect_with_error('Dose (mL) tem de ser > 0');
    }
    if ($observation_minutes <= 0) {
      array_pop($_SESSION['visits']);
      redirect_with_error('Minutos de observação tem de ser > 0');
    }

    $_SESSION['administrations'][] = [
      'visit_id' => $newId,
      'dose_no' => $dose_no,
      'phase' => $phase,
      'administration_site' => $administration_site,
      'dose_ml' => $dose_ml,
      'observation_minutes' => $observation_minutes,
      'product_id' => $product_id, // guarda também na subclasse para facilitar
    ];
  }

  header('Location: ' . $BASE_URL . '/visits.php?success=' . urlencode('Visita criada com sucesso'));
  exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="card">
  <h1>Adicionar visita</h1>

  <?php if (!empty($_GET['error'])): ?>
    <div class="msg msg-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if (empty($_SESSION['patients']) || empty($_SESSION['doctors'])): ?>
    <div class="msg msg-error">Não é possível criar visitas sem pacientes e médicos.</div>
  <?php elseif (empty($_SESSION['products'])): ?>
    <div class="msg msg-error">Não é possível criar administrações sem produtos. Cria um produto primeiro.</div>
  <?php else: ?>
    <form method="POST" action="<?= $BASE_URL ?>/visit_create.php">
      <div class="field">
        <label for="visit_type">Tipo de visita</label>
        <select id="visit_type" name="visit_type" required>
          <option value="consultation">Consultation</option>
          <option value="administration">Administration</option>
        </select>
      </div>

      <div class="field">
        <label for="patient_id">Paciente</label>
        <select id="patient_id" name="patient_id" required>
          <?php foreach ($_SESSION['patients'] as $p): ?>
            <option value="<?= htmlspecialchars((string)$p['patient_id']) ?>"><?= htmlspecialchars($p['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="doctor_id">Médico</label>
        <select id="doctor_id" name="doctor_id" required>
          <?php foreach ($_SESSION['doctors'] as $d): ?>
            <option value="<?= htmlspecialchars((string)$d['doctor_id']) ?>"><?= htmlspecialchars($d['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="datetime_scheduled">Data/hora agendada</label>
        <input id="datetime_scheduled" name="datetime_scheduled" type="datetime-local" required>
      </div>

      <div class="field">
        <label for="datetime_start">Data/hora de início</label>
        <input id="datetime_start" name="datetime_start" type="datetime-local" required>
      </div>

      <div class="field">
        <label for="datetime_end">Data/hora de fim (opcional)</label>
        <input id="datetime_end" name="datetime_end" type="datetime-local">
      </div>

      <!-- CONSULTATION -->
      <div id="section_consultation" class="card" style="margin-top:12px;">
        <h2>Consultation</h2>
        <div class="field">
          <label for="subspecialty">Subspecialidade</label>
          <input id="subspecialty" name="subspecialty" placeholder="Ex: Imunoalergologia">
        </div>
      </div>

      <!-- ADMINISTRATION -->
      <div id="section_administration" class="card" style="margin-top:12px; display:none;">
        <h2>Administration</h2>

        <div class="field">
          <label for="dose_no">Dose nº</label>
          <input id="dose_no" name="dose_no" type="number" min="0" value="0">
        </div>

        <div class="field">
          <label for="phase">Phase</label>
          <select id="phase" name="phase">
            <option value="build_up">build_up</option>
            <option value="maintenance">maintenance</option>
          </select>
        </div>

        <div class="field">
          <label for="administration_site">Local de administração</label>
          <input id="administration_site" name="administration_site" placeholder="Ex: Braço esquerdo">
        </div>

        <div class="field">
          <label for="dose_ml">Dose (mL)</label>
          <input id="dose_ml" name="dose_ml" type="number" step="0.01" min="0.01" placeholder="Ex: 0.20">
        </div>

        <div class="field">
          <label for="observation_minutes">Minutos de observação</label>
          <input id="observation_minutes" name="observation_minutes" type="number" min="1" value="30">
        </div>

        <div class="field">
          <label for="product_id">Produto (frasco)</label>
          <select id="product_id" name="product_id">
            <?php foreach ($_SESSION['products'] as $p): ?>
              <option value="<?= htmlspecialchars((string)$p['product_id']) ?>">
                <?= htmlspecialchars($p['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small>Nota: um produto pode ser usado no máximo em 5 administrações.</small>
        </div>
      </div>

      <div style="display:flex; gap:10px; margin-top:12px;">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= $BASE_URL ?>/visits.php">Cancelar</a>
      </div>
    </form>

    <script>
      const typeSelect = document.getElementById('visit_type');
      const secC = document.getElementById('section_consultation');
      const secA = document.getElementById('section_administration');

      function toggleSections() {
        const v = typeSelect.value;
        if (v === 'consultation') {
          secC.style.display = '';
          secA.style.display = 'none';
        } else {
          secC.style.display = 'none';
          secA.style.display = '';
        }
      }

      typeSelect.addEventListener('change', toggleSections);
      toggleSections();
    </script>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
