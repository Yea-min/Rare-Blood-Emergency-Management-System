<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Donors';

$donors = $pdo->query("SELECT * FROM v_eligible_donors ORDER BY full_name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1>Donor Registry &amp; Eligibility</h1>
<p class="muted">Eligibility is computed automatically from donation history using the interval rule (90 days for male donors, 120 days for others).</p>

<div class="card">
  <table>
    <tr>
      <th>ID</th><th>Name</th><th>Phone</th><th>Profile</th><th>Last Donation</th><th>Eligibility</th>
    </tr>
    <?php foreach ($donors as $d): ?>
    <tr>
      <td class="mono">#<?= (int)$d['donor_id'] ?></td>
      <td><?= e($d['full_name']) ?></td>
      <td class="mono"><?= e($d['phone']) ?></td>
      <td class="mono">
        <?= e($d['blood_group'] . $d['rh_type'][0]) ?>
        <?php if ($d['rh_type'] === 'Rh-null'): ?><span class="badge badge-rare">Rh-null</span><?php endif; ?>
        <?php if ($d['bombay_phenotype']): ?><span class="badge badge-rare">Bombay</span><?php endif; ?>
        <?php if ($d['kell_status'] === 'Positive'): ?><span class="badge badge-rare">Kell+</span><?php endif; ?>
      </td>
      <td><?= $d['last_donation_date'] ? e(date('d M Y', strtotime($d['last_donation_date']))) : '<span class="muted">Never</span>' ?></td>
      <td>
        <?php if ($d['is_eligible']): ?>
          <span class="badge badge-available">Eligible</span>
        <?php else: ?>
          <span class="badge badge-pending">In <?= (int)$d['days_until_eligible'] ?> day(s)</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
