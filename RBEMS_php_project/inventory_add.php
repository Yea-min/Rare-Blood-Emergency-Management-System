<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Add Inventory Unit';
$success = null; $error = null;

$hospitals = $pdo->query("SELECT hospital_id, name FROM hospitals ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO blood_inventory
            (blood_group, rh_type, kell_status, bombay_phenotype, storage_type, collection_date, hospital_id, status)
            VALUES (?,?,?,?,?,?,?, 'Available')");
        $stmt->execute([
            $_POST['blood_group'], $_POST['rh_type'], $_POST['kell_status'],
            isset($_POST['bombay_phenotype']) ? 1 : 0,
            $_POST['storage_type'], $_POST['collection_date'], $_POST['hospital_id'],
        ]);
        $success = "Unit #" . $pdo->lastInsertId() . " added. Expiry date was calculated automatically.";
    } catch (PDOException $e) {
        $error = "Could not add unit: " . $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1>Add Inventory Unit</h1>
<?php if ($success): ?><div class="ok-banner"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-banner"><?= e($error) ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <div class="grid grid-4">
      <div><label>ABO Group</label>
        <select name="blood_group" required><option>O</option><option>A</option><option>B</option><option>AB</option></select>
      </div>
      <div><label>Rh Type</label>
        <select name="rh_type" required>
          <option value="Positive">Positive</option><option value="Negative">Negative</option><option value="Rh-null">Rh-null</option>
        </select>
      </div>
      <div><label>Kell Status</label>
        <select name="kell_status"><option value="Negative">Negative</option><option value="Positive">Positive</option></select>
      </div>
      <div><label>&nbsp;</label>
        <label style="font-weight:400; display:flex; align-items:center; gap:.4rem; margin-top:.7rem;">
          <input type="checkbox" name="bombay_phenotype" value="1" style="width:auto;"> Bombay phenotype
        </label>
      </div>
      <div><label>Storage Type</label>
        <select name="storage_type" required><option value="Fresh">Fresh</option><option value="Cryopreserved">Cryopreserved</option></select>
      </div>
      <div><label>Collection Date</label><input type="date" name="collection_date" required value="<?= date('Y-m-d') ?>"></div>
      <div><label>Hospital</label>
        <select name="hospital_id" required>
          <?php foreach ($hospitals as $h): ?>
            <option value="<?= (int)$h['hospital_id'] ?>"><?= e($h['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn" type="submit">Add Unit</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
