<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
requirePatientLogin();
$pageTitle = 'Patient Dashboard';

$patientId = (int)$_SESSION['user_id'];
$patient = $pdo->prepare('SELECT * FROM patient_users WHERE patient_id = ?');
$patient->execute([$patientId]);
$patient = $patient->fetch();

$donors = $pdo->query("SELECT * FROM donor_users WHERE is_approved = 1 ORDER BY name")->fetchAll();
$confirmedMeetings = $pdo->prepare("
    SELECT da.*, du.name AS donor_name, du.phone AS donor_phone, h.name AS hospital_name
    FROM donor_appointments da
    JOIN donor_users du ON du.donor_user_id = da.donor_user_id
    LEFT JOIN hospitals h ON h.hospital_id = da.hospital_id
    WHERE da.appointment_status = 'Confirmed' AND da.patient_id = ?
    ORDER BY da.created_at DESC
");
$confirmedMeetings->execute([$patientId]);
$confirmedMeetings = $confirmedMeetings->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1>Patient Portal</h1>
<p class="muted">Welcome, <strong><?= e($_SESSION['user_name'] ?? 'Patient') ?></strong>. Approved donors are listed below with contact information.</p>

<div class="card">
  <h2>My Profile</h2>
  <table>
    <tr><th>Name</th><th>Date of Birth</th><th>Weight</th><th>Blood Group</th><th>Address</th><th>Phone</th></tr>
    <?php if ($patient): ?>
      <tr>
        <td><?= e($patient['name']) ?></td>
        <td><?= e($patient['dob']) ?></td>
        <td><?= e((string)$patient['weight']) ?> kg</td>
        <td><?= e($patient['blood_group']) ?></td>
        <td><?= e($patient['address']) ?></td>
        <td><?= e($patient['phone']) ?></td>
      </tr>
    <?php endif; ?>
  </table>
</div>

<div class="card">
  <h2>Approved Donors</h2>
  <?php if (!$donors): ?>
    <p class="empty">No donors are approved yet.</p>
  <?php else: ?>
    <table>
      <tr><th>Name</th><th>Phone</th><th>Address</th><th>Habits</th></tr>
      <?php foreach ($donors as $donor): ?>
        <tr>
          <td><?= e($donor['name']) ?></td>
          <td><?= e($donor['phone']) ?></td>
          <td><?= e($donor['address']) ?></td>
          <td><?= e($donor['habits'] ?: '—') ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Donation Meeting Status</h2>
  <?php if (!$confirmedMeetings): ?>
    <p class="empty">No confirmed donation meeting yet.</p>
  <?php else: ?>
    <table>
      <tr><th>Donor</th><th>Phone</th><th>Hospital</th><th>Status</th></tr>
      <?php foreach ($confirmedMeetings as $meeting): ?>
        <tr>
          <td><?= e($meeting['donor_name']) ?></td>
          <td><?= e($meeting['donor_phone']) ?></td>
          <td><?= e($meeting['hospital_name'] ?: 'To be confirmed') ?></td>
          <td><span class="badge badge-approved"><?= e($meeting['appointment_status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
