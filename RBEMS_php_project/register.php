<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Register';
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'patient';
    $password = (string)($_POST['password'] ?? '');
    $username = trim((string)($_POST['username'] ?? ''));
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (!preg_match('/^[A-Za-z]{3,50}$/', $username)) {
      $error = 'Username must contain only letters and be 3 to 50 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            if ($role === 'patient') {
              $stmt = $pdo->prepare('INSERT INTO patient_users (name, username, dob, weight, blood_group, address, phone, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    trim($_POST['name']),
                    $username,
                    $_POST['dob'],
                    $_POST['weight'],
                    $_POST['blood_group'],
                    trim($_POST['address']),
                    trim((string)($_POST['phone'] ?? '')),
                    password_hash($password, PASSWORD_DEFAULT),
                ]);
                $success = 'Patient account created successfully. You can now log in.';
            } elseif ($role === 'donor') {
              $stmt = $pdo->prepare('INSERT INTO donor_users (name, username, dob, address, phone, habits, password_hash, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, 0)');
                $stmt->execute([
                    trim($_POST['name']),
                    $username,
                    $_POST['dob'],
                    trim($_POST['address']),
                    trim($_POST['phone']),
                    trim($_POST['habits']),
                    password_hash($password, PASSWORD_DEFAULT),
                ]);
                $success = 'Donor account created successfully. An administrator must approve it before you can log in.';
            } else {
                $error = 'Please select a valid account type.';
            }
        } catch (PDOException $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1>Create an Account</h1>
<p class="muted">Register as a patient or donor. Donor accounts need admin approval before they can log in.</p>

<?php if ($error): ?><div class="alert-banner"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="ok-banner"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="max-width:700px;">
  <form method="post">
    <label>Account Type</label>
    <select name="role" id="roleSelect">
      <option value="patient">Patient</option>
      <option value="donor">Donor</option>
    </select>

    <div class="grid grid-2">
      <div>
        <label id="nameLabel">Full Name</label>
        <input type="text" name="name" required>
      </div>
      <div>
        <label>Username</label>
        <input type="text" name="username" minlength="3" maxlength="50" pattern="[A-Za-z]{3,50}" title="Use 3 to 50 letters only" required>
      </div>
      <div id="dobField">
        <label>Date of Birth</label>
        <input type="date" name="dob" required>
      </div>
    </div>

    <div id="patientFields">
      <div class="grid grid-2">
        <div>
          <label>Weight (kg)</label>
          <input type="number" step="0.01" min="1" name="weight" required>
        </div>
        <div>
          <label>Blood Group</label>
          <select name="blood_group" required>
            <option value="O">O</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="AB">AB</option>
          </select>
        </div>
      </div>
    </div>

    <div id="donorFields" style="display:none;">
      <label>Habits / Notes</label>
      <textarea name="habits" rows="3" placeholder="Any relevant health/habit information"></textarea>
    </div>

    <div class="grid grid-2">
      <div>
        <label>Address</label>
        <input type="text" name="address" required>
      </div>
      <div id="phoneField">
        <label>Phone Number</label>
        <input type="tel" name="phone">
      </div>
    </div>

    <div class="grid grid-2">
      <div>
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <div>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required>
      </div>
    </div>

    <button class="btn" type="submit">Create Account</button>
  </form>

  <p class="muted" style="margin-top:1rem;">Already have an account? <a href="admin_login.php">Log in here</a>.</p>
</div>

<script>
  const roleSelect = document.getElementById('roleSelect');
  const patientFields = document.getElementById('patientFields');
  const donorFields = document.getElementById('donorFields');
  const nameLabel = document.getElementById('nameLabel');
  const dobField = document.getElementById('dobField');
  const phoneField = document.getElementById('phoneField');
  function updateFields() {
    const isPatient = roleSelect.value === 'patient';
    const isDonor = roleSelect.value === 'donor';
    patientFields.style.display = isPatient ? 'block' : 'none';
    donorFields.style.display = isDonor ? 'block' : 'none';
    nameLabel.textContent = 'Full Name';
    dobField.style.display = 'block';
    phoneField.style.display = 'block';
    const weightInput = document.querySelector('input[name="weight"]');
    const bloodInput = document.querySelector('select[name="blood_group"]');
    const habitsInput = document.querySelector('textarea[name="habits"]');
    const dobInput = document.querySelector('input[name="dob"]');
    const phoneInput = document.querySelector('input[name="phone"]');
    if (weightInput) weightInput.required = isPatient;
    if (bloodInput) bloodInput.required = isPatient;
    if (habitsInput) habitsInput.required = isDonor;
    if (dobInput) dobInput.required = true;
    if (phoneInput) phoneInput.required = true;
  }
  roleSelect.addEventListener('change', updateFields);
  updateFields();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
