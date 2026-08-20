<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Register Donor';
$success = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO donors
            (full_name, phone, email, gender, dob, blood_group, rh_type, kell_status, bombay_phenotype,
           address)
          VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            trim($_POST['full_name']),
            trim($_POST['phone']),
            trim($_POST['email']) ?: null,
            $_POST['gender'],
            $_POST['dob'],
            $_POST['blood_group'],
            $_POST['rh_type'],
            $_POST['kell_status'],
            isset($_POST['bombay_phenotype']) ? 1 : 0,
            trim($_POST['address']),
        ]);
        $success = "Donor registered successfully (ID #" . $pdo->lastInsertId() . ").";
    } catch (PDOException $e) {
        $error = "Could not register donor: " . $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1>Donor Registration</h1>
<p class="muted">Capture donor identity, contact details and rare phenotype profile.</p>

<?php if ($success): ?><div class="ok-banner"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-banner"><?= e($error) ?></div><?php endif; ?>

<div class="card">
  <form method="post">
    <fieldset>
      <legend>Identity</legend>
      <div class="grid grid-3">
        <div><label>Full Name</label><input type="text" name="full_name" required></div>
        <div><label>Phone</label><input type="text" name="phone" required></div>
        <div><label>Email</label><input type="email" name="email"></div>
        <div><label>Gender</label>
          <select name="gender" required>
            <option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option>
          </select>
        </div>
        <div><label>Date of Birth</label><input type="date" name="dob" required></div>
        <div><label>Address</label><input type="text" name="address"></div>
      </div>
    </fieldset>

    <fieldset>
      <legend>Phenotype Profile</legend>
      <div class="grid grid-4">
        <div><label>ABO Group</label>
          <select name="blood_group" id="blood_group" required>
            <option value="O" data-rh="Negative">O- (uncommon)</option><option value="A">A</option><option value="B">B</option><option value="AB">AB (least common ABO)</option>
          </select>
        </div>
        <div><label>Rh Type</label>
          <select name="rh_type" id="rh_type" required>
            <option value="Positive">Positive</option>
            <option value="Negative">Negative</option>
            <option value="Rh-null">Rh-null (rarest known blood type)</option>
          </select>
        </div>
        <div><label>Kell Status</label>
          <select name="kell_status" required>
            <option value="Negative">Negative</option>
            <option value="Positive">Positive</option>
          </select>
        </div>
        <div><label>&nbsp;</label>
          <label style="font-weight:400; display:flex; align-items:center; gap:.4rem; margin-top:.7rem;">
            <input type="checkbox" name="bombay_phenotype" value="1" style="width:auto;"> Bombay phenotype (very rare)
          </label>
        </div>
      </div>
    </fieldset>

    <button type="submit" class="btn">Register Donor</button>
  </form>
</div>

<script>
const bloodGroupInput = document.getElementById('blood_group');
const rhTypeInput = document.getElementById('rh_type');
bloodGroupInput.addEventListener('change', () => {
  const selectedOption = bloodGroupInput.options[bloodGroupInput.selectedIndex];
  if (selectedOption.dataset.rh) rhTypeInput.value = selectedOption.dataset.rh;
});
rhTypeInput.value = 'Negative';
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
