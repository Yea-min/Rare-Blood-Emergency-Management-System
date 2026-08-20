<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
requireDonorLogin();
$pageTitle = 'Donor Dashboard';

$donorId = (int)$_SESSION['user_id'];
$error = null;
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hospital_id'], $_POST['patient_id'])) {
    try {
        $stmt = $pdo->prepare('INSERT INTO donor_appointments (donor_user_id, hospital_id, patient_id, appointment_status, notes) VALUES (?, ?, ?, "Confirmed", ?)');
        $stmt->execute([
            $donorId,
            (int)$_POST['hospital_id'],
            (int)$_POST['patient_id'],
            'Confirmed at selected hospital by donor.'
        ]);
        $notice = 'Your donation meeting has been confirmed.';
    } catch (PDOException $e) {
        $error = 'Unable to confirm appointment: ' . $e->getMessage();
    }
}

$searchTerm = trim((string)($_GET['hospital_search'] ?? ''));
$hospitals = $pdo->query('SELECT hospital_id, name, address, contact_phone FROM hospitals ORDER BY name')->fetchAll();
if ($searchTerm !== '') {
    $hospitals = array_filter($hospitals, function ($hospital) use ($searchTerm) {
        $text = strtolower($hospital['name'] . ' ' . $hospital['address'] . ' ' . $hospital['contact_phone']);
        return strpos($text, strtolower($searchTerm)) !== false;
    });
}

$patients = $pdo->query('SELECT patient_id, name, phone, blood_group FROM patient_users ORDER BY name')->fetchAll();
$appointmentsStmt = $pdo->prepare("
    SELECT da.*, h.name AS hospital_name, p.name AS patient_name
    FROM donor_appointments da
    JOIN hospitals h ON h.hospital_id = da.hospital_id
    LEFT JOIN patient_users p ON p.patient_id = da.patient_id
    WHERE da.donor_user_id = ?
    ORDER BY da.created_at DESC
");
$appointmentsStmt->execute([$donorId]);
$appointments = $appointmentsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1>Donor Portal</h1>
<p class="muted">Welcome, <strong><?= e($_SESSION['user_name'] ?? 'Donor') ?></strong>. Search for a nearby hospital and confirm your donation meeting.</p>

<?php if ($notice): ?><div class="ok-banner"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-banner"><?= e($error) ?></div><?php endif; ?>

<div class="card">
  <h2>Find and Confirm a Hospital</h2>
  <form method="get" style="margin-bottom:1rem;">
    <label>Search hospitals</label>
    <input type="text" name="hospital_search" value="<?= e($searchTerm) ?>" placeholder="Search by name, address, or contact">
    <button class="btn ghost" type="submit">Search</button>
  </form>

  <form method="post">
    <div class="grid grid-3">
      <div>
        <label>Select Patient</label>
        <select name="patient_id" required>
          <option value="">Choose a patient</option>
          <?php foreach ($patients as $patient): ?>
            <option value="<?= (int)$patient['patient_id'] ?>"><?= e($patient['name']) ?> (<?= e($patient['blood_group']) ?>, <?= e($patient['phone']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Select Hospital</label>
        <select name="hospital_id" required>
          <option value="">Choose a hospital</option>
          <?php foreach ($hospitals as $hospital): ?>
            <option value="<?= (int)$hospital['hospital_id'] ?>"><?= e($hospital['name']) ?> - <?= e($hospital['address']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex; align-items:flex-end;">
        <button class="btn" type="submit">Confirm Donation</button>
      </div>
    </div>
  </form>
</div>

<div class="card">
  <h2>Your Confirmed Visits</h2>
  <?php if (!$appointments): ?>
    <p class="empty">No confirmed visits yet.</p>
  <?php else: ?>
    <table>
      <tr><th>Patient</th><th>Hospital</th><th>Status</th><th>Confirmed At</th></tr>
      <?php foreach ($appointments as $appointment): ?>
        <tr>
          <td><?= e($appointment['patient_name'] ?: 'Unknown patient') ?></td>
          <td><?= e($appointment['hospital_name']) ?></td>
          <td><span class="badge badge-approved"><?= e($appointment['appointment_status']) ?></span></td>
          <td><?= e($appointment['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
