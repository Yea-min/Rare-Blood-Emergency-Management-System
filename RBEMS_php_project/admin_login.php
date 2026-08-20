<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Login';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'patient';
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($role !== 'admin' && !preg_match('/^[A-Za-z]{3,50}$/', $username)) {
      $error = 'Username must contain only letters and be 3 to 50 characters long.';
    } elseif ($role === 'patient') {
      $stmt = $pdo->prepare('SELECT * FROM patient_users WHERE username = ? LIMIT 1');
      $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
          session_regenerate_id(true);
          unset($_SESSION['admin_id'], $_SESSION['admin_username']);
            $_SESSION['user_type'] = 'patient';
            $_SESSION['user_id'] = (int)$user['patient_id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: patient_dashboard.php');
            exit;
        }

        $error = 'Invalid patient credentials.';
    } elseif ($role === 'donor') {
      $stmt = $pdo->prepare('SELECT * FROM donor_users WHERE username = ? AND is_approved = 1 LIMIT 1');
      $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
          session_regenerate_id(true);
          unset($_SESSION['admin_id'], $_SESSION['admin_username']);
            $_SESSION['user_type'] = 'donor';
            $_SESSION['user_id'] = (int)$user['donor_user_id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: donor_dashboard.php');
            exit;
        }

        $error = 'Invalid donor account or donor not approved yet.';
    } else {
      $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
      $stmt->execute([$username]);
        $admin = $stmt->fetch();

      if ($admin && hash_equals($admin['password_hash'], hash('sha256', $password))) {
          session_regenerate_id(true);
          unset($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['user_name']);
          $_SESSION['user_type'] = 'admin';
          $_SESSION['admin_id'] = (int)$admin['admin_id'];
          $_SESSION['admin_username'] = $admin['username'];
            header('Location: admin_dashboard.php');
            exit;
        }

        $error = 'Invalid admin credentials.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1>Login</h1>
<p class="muted">Sign in as a patient, donor, or system admin.</p>
<?php if ($error): ?><div class="alert-banner"><?= e($error) ?></div><?php endif; ?>

<div class="card" style="max-width:420px;">
  <form method="post">
    <label>Account Type</label>
    <select name="role" required>
      <option value="patient">Patient</option>
      <option value="donor">Donor</option>
      <option value="admin">Admin</option>
    </select>

    <div>
      <label>Username</label>
      <input type="text" name="username" required>
    </div>

    <label>Password</label>
    <input type="password" name="password" required>
    <button class="btn" type="submit">Log In</button>
  </form>

  <p class="muted" style="margin-top:1rem;">
    Need a patient or donor account? <a href="register.php">Create one here</a>.
  </p>

  <p class="muted" style="margin-top:1rem;">Admin login: <strong>admin1</strong> / <strong>12345678</strong></p>
</div>

<script>
  const roleSelect = document.querySelector('select[name="role"]');
  const usernameInput = document.querySelector('input[name="username"]');
  function updateLoginFields() {
    const role = roleSelect.value;
    const isAdmin = role === 'admin';
    usernameInput.removeAttribute('pattern');
    usernameInput.removeAttribute('minlength');
    usernameInput.removeAttribute('maxlength');
    usernameInput.removeAttribute('title');
    if (!isAdmin) {
      usernameInput.minLength = 3;
      usernameInput.maxLength = 50;
      usernameInput.pattern = '[A-Za-z]{3,50}';
      usernameInput.title = 'Use 3 to 50 letters only';
    }
  }
  roleSelect.addEventListener('change', updateLoginFields);
  updateLoginFields();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
