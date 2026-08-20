<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'New Hospital Request';
$success = null; $error = null;

$hospitals = $pdo->query("SELECT hospital_id, name FROM hospitals ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO hospital_requests
            (hospital_id, blood_group, rh_type, kell_status, bombay_phenotype, units_needed, priority, needed_by)
            VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $_POST['hospital_id'], $_POST['blood_group'], $_POST['rh_type'], $_POST['kell_status'],
            isset($_POST['bombay_phenotype']) ? 1 : 0,
            $_POST['units_needed'], $_POST['priority'], $_POST['needed_by'],
        ]);
        $success = "Request #" . $pdo->lastInsertId() . " submitted and queued by priority.";
    } catch (PDOException $e) {
        $error = "Could not submit request: " . $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1>Submit Hospital Request</h1>
<p class="muted">Only authorized hospitals can submit requests. Requests are automatically queued: Emergency &gt; Urgent &gt; Routine.</p>

<?php if ($success): ?><div class="ok-banner"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-banner"><?= e($error) ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <div class="grid grid-4">
      <div><label>Requesting Hospital</label>
        <select name="hospital_id" required>
          <?php foreach ($hospitals as $h): ?>
            <option value="<?= (int)$h['hospital_id'] ?>"><?= e($h['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>ABO Group Needed</label>
        <select name="blood_group" required><option>O</option><option>A</option><option>B</option><option>AB</option></select>
      </div>
      <div><label>Rh Type Needed</label>
        <select name="rh_type" required>
          <option value="Positive">Positive</option><option value="Negative">Negative</option><option value="Rh-null">Rh-null</option>
        </select>
      </div>
      <div><label>Kell Status Needed</label>
        <select name="kell_status"><option value="Negative">Negative</option><option value="Positive">Positive</option></select>
      </div>
      <div><label>&nbsp;</label>
        <label style="font-weight:400; display:flex; align-items:center; gap:.4rem; margin-top:.7rem;">
          <input type="checkbox" name="bombay_phenotype" value="1" style="width:auto;"> Bombay phenotype
        </label>
      </div>
      <div><label>Units Needed</label><input type="number" name="units_needed" min="1" value="1" required></div>
      <div><label>Priority</label>
        <select name="priority" required>
          <option value="Emergency">Emergency</option><option value="Urgent">Urgent</option><option value="Routine">Routine</option>
        </select>
      </div>
      <div><label>Needed By</label><input type="datetime-local" name="needed_by" required></div>
    </div>
    <button class="btn" type="submit">Submit Request</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
