<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Requests';

$requests = $pdo->query("
    SELECT r.*, h.name AS hospital_name
    FROM hospital_requests r JOIN hospitals h ON h.hospital_id = r.hospital_id
    ORDER BY FIELD(r.status,'Pending','Approved','Fulfilled','Rejected'),
             FIELD(r.priority,'Emergency','Urgent','Routine'), r.request_date DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1>Request Status Tracking</h1>
<p class="muted">All hospital requests, queued by priority and current status. Notifications are logged automatically on submission and fulfillment.</p>
<a href="hospital_request.php" class="btn">+ New Request</a>

<div class="vital-divider"></div>

<div class="card">
  <table>
    <tr><th>ID</th><th>Hospital</th><th>Need</th><th>Priority</th><th>Status</th><th>Needed By</th></tr>
    <?php foreach ($requests as $r): ?>
    <tr>
      <td class="mono">#<?= (int)$r['request_id'] ?></td>
      <td><?= e($r['hospital_name']) ?></td>
      <td class="mono">
        <?= e($r['blood_group'] . $r['rh_type'][0]) ?><?= $r['bombay_phenotype'] ? ' (Bombay)' : '' ?> &times; <?= (int)$r['units_needed'] ?>
      </td>
      <td><span class="badge badge-<?= strtolower($r['priority']) ?>"><?= e($r['priority']) ?></span></td>
      <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td>
      <td class="muted"><?= e(date('d M Y, H:i', strtotime($r['needed_by']))) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
