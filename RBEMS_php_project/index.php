<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Dashboard';

$totalDonors      = $pdo->query("SELECT COUNT(*) FROM donors WHERE status='Active'")->fetchColumn();
$eligibleDonors   = $pdo->query("SELECT COUNT(*) FROM v_eligible_donors WHERE is_eligible=1")->fetchColumn();
$availableUnits   = $pdo->query("SELECT COUNT(*) FROM blood_inventory WHERE status='Available'")->fetchColumn();
$expiringSoon     = $pdo->query("SELECT * FROM v_expiring_soon")->fetchAll();
$pendingRequests  = $pdo->query("SELECT * FROM v_pending_requests")->fetchAll();
$emergencyCount   = $pdo->query("SELECT COUNT(*) FROM hospital_requests WHERE status='Pending' AND priority='Emergency'")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<h1>System Overview</h1>
<p class="muted">Live status of donors, inventory and hospital requests across the network.</p>

<div class="grid grid-4">
  <div class="stat"><div class="num"><?= (int)$totalDonors ?></div><div class="label">Registered Donors</div></div>
  <div class="stat"><div class="num"><?= (int)$eligibleDonors ?></div><div class="label">Eligible Right Now</div></div>
  <div class="stat"><div class="num"><?= (int)$availableUnits ?></div><div class="label">Units Available</div></div>
  <div class="stat alert"><div class="num"><?= (int)$emergencyCount ?></div><div class="label">Emergency Requests</div></div>
</div>

<div class="vital-divider"></div>

<div class="grid grid-3" style="grid-template-columns: 1.3fr 1fr;">
  <div class="card">
    <h2>Pending Hospital Requests</h2>
    <?php if (!$pendingRequests): ?>
      <p class="empty">No pending requests right now.</p>
    <?php else: ?>
      <table>
        <tr><th>ID</th><th>Hospital</th><th>Need</th><th>Priority</th><th>Requested</th></tr>
        <?php foreach ($pendingRequests as $r): ?>
        <tr>
          <td class="mono">#<?= (int)$r['request_id'] ?></td>
          <td><?= e($r['hospital_name']) ?></td>
          <td class="mono"><?= e($r['blood_group'] . $r['rh_type'][0]) ?><?= $r['bombay_phenotype'] ? ' (Bombay)' : '' ?> &times; <?= (int)$r['units_needed'] ?></td>
          <td><span class="badge badge-<?= strtolower($r['priority']) ?>"><?= e($r['priority']) ?></span></td>
          <td class="muted"><?= e(date('d M, H:i', strtotime($r['request_date']))) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
    <a href="request_list.php" class="btn secondary">View all requests</a>
  </div>

  <div class="card">
    <h2>Expiring Within 7 Days</h2>
    <?php if (!$expiringSoon): ?>
      <p class="empty">Nothing expiring soon.</p>
    <?php else: ?>
      <table>
        <tr><th>Unit</th><th>Type</th><th>Expires</th></tr>
        <?php foreach ($expiringSoon as $u): ?>
        <tr>
          <td class="mono">#<?= (int)$u['unit_id'] ?></td>
          <td class="mono"><?= e($u['blood_group'] . $u['rh_type'][0]) ?></td>
          <td><?= (int)daysUntil($u['expiry_date']) ?> day(s)</td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
    <a href="inventory.php" class="btn secondary">View inventory</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
