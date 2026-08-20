<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Inventory';

$units = $pdo->query("
    SELECT bi.*, h.name AS hospital_name
    FROM blood_inventory bi JOIN hospitals h ON h.hospital_id = bi.hospital_id
    ORDER BY FIELD(bi.status,'Available','Reserved','Used','Expired','Discarded'), bi.expiry_date ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1>Blood Inventory</h1>
<p class="muted">Shelf-life is calculated automatically on entry (42 days fresh, 10 years cryopreserved) via a database trigger.</p>
<a href="inventory_add.php" class="btn">+ Add Unit</a>

<div class="vital-divider"></div>

<div class="card">
  <table>
    <tr><th>Unit</th><th>Type</th><th>Storage</th><th>Collected</th><th>Expires</th><th>Hospital</th><th>Status</th></tr>
    <?php foreach ($units as $u):
        $daysLeft = daysUntil($u['expiry_date']);
        $statusClass = strtolower($u['status']);
    ?>
    <tr>
      <td class="mono">#<?= (int)$u['unit_id'] ?></td>
      <td class="mono">
        <?= e($u['blood_group'] . $u['rh_type'][0]) ?>
        <?php if ($u['bombay_phenotype']): ?><span class="badge badge-rare">Bombay</span><?php endif; ?>
        <?php if ($u['rh_type']==='Rh-null'): ?><span class="badge badge-rare">Rh-null</span><?php endif; ?>
        <?php if ($u['kell_status']==='Positive'): ?><span class="badge badge-rare">Kell+</span><?php endif; ?>
      </td>
      <td><?= e($u['storage_type']) ?></td>
      <td class="muted"><?= e(date('d M Y', strtotime($u['collection_date']))) ?></td>
      <td>
        <?= e(date('d M Y', strtotime($u['expiry_date']))) ?>
        <?php if ($u['status'] === 'Available' && $daysLeft <= 7): ?>
          <span class="badge badge-pending"><?= $daysLeft ?>d left</span>
        <?php endif; ?>
      </td>
      <td><?= e($u['hospital_name']) ?></td>
      <td><span class="badge badge-<?= $statusClass ?>"><?= e($u['status']) ?></span></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
