<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Cross-Match';

$results = null; $matchedDonors = null;
$group = $_GET['blood_group'] ?? ''; $rh = $_GET['rh_type'] ?? '';
$kell = $_GET['kell_status'] ?? 'Negative'; $bombay = isset($_GET['bombay_phenotype']);

if ($group && $rh) {
    // Compatible inventory via stored procedure
    $stmt = $pdo->prepare("CALL sp_find_compatible_inventory(?,?,?,?)");
    $stmt->execute([$group, $rh, $kell, $bombay ? 1 : 0]);
    $results = $stmt->fetchAll();
    $stmt->closeCursor();

    // Compatible eligible donors, checked in PHP using the same rule engine
    $donors = $pdo->query("SELECT * FROM v_eligible_donors WHERE is_eligible = 1")->fetchAll();
    $matchedDonors = array_filter($donors, function ($d) use ($group, $rh, $kell, $bombay) {
        return isCompatible(
            $d['blood_group'], $d['rh_type'], $d['kell_status'], (bool)$d['bombay_phenotype'],
            $group, $rh, $kell, $bombay
        );
    });
}

require_once __DIR__ . '/includes/header.php';
?>

<h1>Rare Blood Compatibility Cross-Matching</h1>
<p class="muted">Enter the recipient's phenotype to find compatible inventory units and eligible donors, including Rh-null, Bombay phenotype and Kell overrides.</p>

<div class="card">
  <form method="get">
    <div class="grid grid-4">
      <div><label>Recipient ABO Group</label>
        <select name="blood_group" required>
          <?php foreach (['O','A','B','AB'] as $g): ?>
            <option <?= $group === $g ? 'selected' : '' ?>><?= $g ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Rh Type</label>
        <select name="rh_type" required>
          <?php foreach (['Positive','Negative','Rh-null'] as $r): ?>
            <option value="<?= $r ?>" <?= $rh === $r ? 'selected' : '' ?>><?= $r ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Kell Status</label>
        <select name="kell_status">
          <option value="Negative" <?= $kell === 'Negative' ? 'selected' : '' ?>>Negative</option>
          <option value="Positive" <?= $kell === 'Positive' ? 'selected' : '' ?>>Positive</option>
        </select>
      </div>
      <div><label>&nbsp;</label>
        <label style="font-weight:400; display:flex; align-items:center; gap:.4rem; margin-top:.7rem;">
          <input type="checkbox" name="bombay_phenotype" value="1" style="width:auto;" <?= $bombay ? 'checked' : '' ?>> Bombay phenotype
        </label>
      </div>
    </div>
    <button class="btn" type="submit">Find Matches</button>
  </form>
</div>

<?php if ($results !== null): ?>
  <div class="vital-divider"></div>

  <div class="card">
    <h2>Compatible Inventory Units</h2>
    <?php if (!$results): ?>
      <p class="empty">No compatible units currently in stock for this profile.</p>
    <?php else: ?>
      <table>
        <tr><th>Unit</th><th>Type</th><th>Storage</th><th>Expires</th><th>Hospital</th></tr>
        <?php foreach ($results as $u): ?>
        <tr>
          <td class="mono">#<?= (int)$u['unit_id'] ?></td>
          <td class="mono"><?= e($u['blood_group'] . $u['rh_type'][0]) ?>
            <?php if ($u['bombay_phenotype']): ?><span class="badge badge-rare">Bombay</span><?php endif; ?>
            <?php if ($u['rh_type']==='Rh-null'): ?><span class="badge badge-rare">Rh-null</span><?php endif; ?>
          </td>
          <td><?= e($u['storage_type']) ?></td>
          <td><?= (int)daysUntil($u['expiry_date']) ?> day(s)</td>
          <td><?= e($u['hospital_name']) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Compatible Eligible Donors (fallback if stock unavailable)</h2>
    <?php if (!$matchedDonors): ?>
      <p class="empty">No eligible donors match this profile right now.</p>
    <?php else: ?>
      <table>
        <tr><th>ID</th><th>Name</th><th>Phone</th><th>Profile</th></tr>
        <?php foreach ($matchedDonors as $d): ?>
        <tr>
          <td class="mono">#<?= (int)$d['donor_id'] ?></td>
          <td><?= e($d['full_name']) ?></td>
          <td class="mono"><?= e($d['phone']) ?></td>
          <td class="mono"><?= e($d['blood_group'] . $d['rh_type'][0]) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
