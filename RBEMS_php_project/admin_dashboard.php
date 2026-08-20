<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
$pageTitle = 'Admin Dashboard';
$notice = null; $error = null;

// Handle approve / reject actions on requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'approve') {
            $stmt = $pdo->prepare("UPDATE hospital_requests SET status='Approved' WHERE request_id = ?");
            $stmt->execute([$_POST['request_id']]);
            $notice = "Request #" . $_POST['request_id'] . " approved.";
        } elseif ($_POST['action'] === 'reject') {
            $stmt = $pdo->prepare("UPDATE hospital_requests SET status='Rejected' WHERE request_id = ?");
            $stmt->execute([$_POST['request_id']]);
            $notice = "Request #" . $_POST['request_id'] . " rejected.";
        } elseif ($_POST['action'] === 'approve_donor') {
            $stmt = $pdo->prepare("UPDATE donor_users SET is_approved = 1 WHERE donor_user_id = ?");
            $stmt->execute([$_POST['donor_user_id']]);
            $notice = "Donor account approved.";
        } elseif ($_POST['action'] === 'reject_donor') {
            $stmt = $pdo->prepare("DELETE FROM donor_users WHERE donor_user_id = ?");
            $stmt->execute([$_POST['donor_user_id']]);
            $notice = "Donor account rejected and removed.";
        } elseif ($_POST['action'] === 'delete_donor') {
          $stmt = $pdo->prepare("DELETE FROM donor_users WHERE donor_user_id = ?");
          $stmt->execute([$_POST['donor_user_id']]);
          $notice = $stmt->rowCount() ? "Donor account deleted." : "Donor account not found.";
        } elseif ($_POST['action'] === 'delete_patient') {
          $stmt = $pdo->prepare("DELETE FROM patient_users WHERE patient_id = ?");
          $stmt->execute([$_POST['patient_id']]);
          $notice = $stmt->rowCount() ? "Patient account deleted." : "Patient account not found.";
        } elseif ($_POST['action'] === 'create_transfer') {
            $stmt = $pdo->prepare("INSERT INTO transfers
                (request_id, unit_id, source_hospital_id, destination_hospital_id, quantity, status, approved_by)
                VALUES (?,?,?,?,?, 'Approved', ?)");
            $stmt->execute([
                $_POST['request_id'],
                $_POST['unit_id'] ?: null,
                $_POST['source_hospital_id'] ?: null,
                $_POST['destination_hospital_id'],
                $_POST['quantity'],
                $_SESSION['admin_username'],
            ]);
            if (!empty($_POST['unit_id'])) {
                $pdo->prepare("UPDATE blood_inventory SET status='Reserved' WHERE unit_id = ?")->execute([$_POST['unit_id']]);
            }
            $notice = "Transfer #" . $pdo->lastInsertId() . " created.";
        } elseif ($_POST['action'] === 'complete_transfer') {
            $stmt = $pdo->prepare("UPDATE transfers SET status='Completed' WHERE transfer_id = ?");
            $stmt->execute([$_POST['transfer_id']]);
            $notice = "Transfer #" . $_POST['transfer_id'] . " marked completed.";
        }
    } catch (PDOException $e) {
        $error = "Action failed: " . $e->getMessage();
    }
}

$requests = $pdo->query("
    SELECT r.*, h.name AS hospital_name FROM hospital_requests r
    JOIN hospitals h ON h.hospital_id = r.hospital_id
    WHERE r.status IN ('Pending','Approved')
    ORDER BY FIELD(r.priority,'Emergency','Urgent','Routine'), r.request_date ASC
")->fetchAll();

$pendingDonors = $pdo->query("SELECT * FROM donor_users WHERE is_approved = 0 ORDER BY created_at DESC")->fetchAll();
$approvedDonors = $pdo->query("SELECT * FROM donor_users WHERE is_approved = 1 ORDER BY name")->fetchAll();
$patients = $pdo->query("SELECT patient_id, name, dob, blood_group, phone FROM patient_users ORDER BY name")->fetchAll();
$availableUnits = $pdo->query("SELECT unit_id, blood_group, rh_type, hospital_id FROM blood_inventory WHERE status='Available'")->fetchAll();
$hospitals = $pdo->query("SELECT hospital_id, name FROM hospitals ORDER BY name")->fetchAll();
$transfers = $pdo->query("
    SELECT t.*, r.blood_group, r.rh_type, dh.name AS dest_name
    FROM transfers t
    JOIN hospital_requests r ON r.request_id = t.request_id
    JOIN hospitals dh ON dh.hospital_id = t.destination_hospital_id
    WHERE t.status <> 'Completed'
    ORDER BY t.transfer_date DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1>Admin Dashboard</h1>
<p class="muted">Signed in as <strong><?= e($_SESSION['admin_username']) ?></strong>. Approve requests, then arrange inter-hospital unit transfers.</p>
<?php if ($notice): ?><div class="ok-banner"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-banner"><?= e($error) ?></div><?php endif; ?>

<div class="card">
  <h2>Requests Awaiting Action</h2>
  <?php if (!$requests): ?>
    <p class="empty">No open requests.</p>
  <?php else: ?>
  <table>
    <tr><th>ID</th><th>Hospital</th><th>Need</th><th>Priority</th><th>Status</th><th>Actions</th></tr>
    <?php foreach ($requests as $r): ?>
    <tr>
      <td class="mono">#<?= (int)$r['request_id'] ?></td>
      <td><?= e($r['hospital_name']) ?></td>
      <td class="mono"><?= e($r['blood_group'].$r['rh_type'][0]) ?> &times; <?= (int)$r['units_needed'] ?></td>
      <td><span class="badge badge-<?= strtolower($r['priority']) ?>"><?= e($r['priority']) ?></span></td>
      <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td>
      <td>
        <?php if ($r['status'] === 'Pending'): ?>
          <form method="post" style="display:inline;">
            <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
            <button class="btn ghost" name="action" value="approve" type="submit">Approve</button>
            <button class="btn ghost" name="action" value="reject" type="submit">Reject</button>
          </form>
        <?php else: ?>
          <span class="muted">Ready for transfer below</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Donor Registrations Pending Approval</h2>
  <?php if (!$pendingDonors): ?>
    <p class="empty">No donor registrations require approval.</p>
  <?php else: ?>
  <table>
    <tr><th>Name</th><th>DOB</th><th>Phone</th><th>Habits</th><th>Action</th></tr>
    <?php foreach ($pendingDonors as $donor): ?>
      <tr>
        <td><?= e($donor['name']) ?></td>
        <td><?= e($donor['dob']) ?></td>
        <td><?= e($donor['phone']) ?></td>
        <td><?= e($donor['habits']) ?></td>
        <td>
          <form method="post" style="display:inline;">
            <input type="hidden" name="donor_user_id" value="<?= (int)$donor['donor_user_id'] ?>">
            <button class="btn ghost" name="action" value="approve_donor" type="submit">Approve</button>
            <button class="btn ghost" name="action" value="reject_donor" type="submit">Reject</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Approved Donors</h2>
  <?php if (!$approvedDonors): ?>
    <p class="empty">No approved donors yet.</p>
  <?php else: ?>
  <table>
    <tr><th>Name</th><th>Phone</th><th>Address</th><th>Action</th></tr>
    <?php foreach ($approvedDonors as $donor): ?>
      <tr>
        <td><?= e($donor['name']) ?></td>
        <td><?= e($donor['phone']) ?></td>
        <td><?= e($donor['address']) ?></td>
        <td>
          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this donor account permanently?');">
            <input type="hidden" name="donor_user_id" value="<?= (int)$donor['donor_user_id'] ?>">
            <button class="btn ghost" name="action" value="delete_donor" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Patient Accounts</h2>
  <?php if (!$patients): ?>
    <p class="empty">No patient accounts yet.</p>
  <?php else: ?>
  <table>
    <tr><th>Name</th><th>DOB</th><th>Blood Group</th><th>Phone</th><th>Action</th></tr>
    <?php foreach ($patients as $patient): ?>
      <tr>
        <td><?= e($patient['name']) ?></td>
        <td><?= e($patient['dob']) ?></td>
        <td><?= e($patient['blood_group']) ?></td>
        <td><?= e($patient['phone']) ?></td>
        <td>
          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this patient account permanently?');">
            <input type="hidden" name="patient_id" value="<?= (int)$patient['patient_id'] ?>">
            <button class="btn ghost" name="action" value="delete_patient" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Create Transfer / Fulfillment</h2>
  <form method="post">
    <input type="hidden" name="action" value="create_transfer">
    <div class="grid grid-4">
      <div><label>Request</label>
        <select name="request_id" required>
          <?php foreach ($requests as $r): if ($r['status'] !== 'Approved') continue; ?>
            <option value="<?= (int)$r['request_id'] ?>">#<?= (int)$r['request_id'] ?> - <?= e($r['hospital_name']) ?> (<?= e($r['blood_group'].$r['rh_type'][0]) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Inventory Unit (optional)</label>
        <select name="unit_id">
          <option value="">— mobilize donor instead —</option>
          <?php foreach ($availableUnits as $u): ?>
            <option value="<?= (int)$u['unit_id'] ?>">#<?= (int)$u['unit_id'] ?> (<?= e($u['blood_group'].$u['rh_type'][0]) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Source Hospital</label>
        <select name="source_hospital_id">
          <option value="">—</option>
          <?php foreach ($hospitals as $h): ?>
            <option value="<?= (int)$h['hospital_id'] ?>"><?= e($h['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Destination Hospital</label>
        <select name="destination_hospital_id" required>
          <?php foreach ($hospitals as $h): ?>
            <option value="<?= (int)$h['hospital_id'] ?>"><?= e($h['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Quantity</label><input type="number" name="quantity" min="1" value="1" required></div>
    </div>
    <button class="btn" type="submit">Create Transfer</button>
  </form>
</div>

<div class="card">
  <h2>Open Transfers</h2>
  <?php if (!$transfers): ?>
    <p class="empty">No transfers in progress.</p>
  <?php else: ?>
  <table>
    <tr><th>ID</th><th>Request</th><th>Need</th><th>Destination</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($transfers as $t): ?>
    <tr>
      <td class="mono">#<?= (int)$t['transfer_id'] ?></td>
      <td class="mono">#<?= (int)$t['request_id'] ?></td>
      <td class="mono"><?= e($t['blood_group'].$t['rh_type'][0]) ?></td>
      <td><?= e($t['dest_name']) ?></td>
      <td><span class="badge badge-<?= strtolower($t['status']) ?>"><?= e($t['status']) ?></span></td>
      <td>
        <form method="post">
          <input type="hidden" name="transfer_id" value="<?= (int)$t['transfer_id'] ?>">
          <button class="btn ghost" name="action" value="complete_transfer" type="submit">Mark Completed</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
