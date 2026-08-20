<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Notifications';

$notes = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1>Notifications</h1>
<p class="muted">Auto-logged by database triggers whenever a request is submitted or a transfer completes.</p>

<div class="card">
  <?php if (!$notes): ?>
    <p class="empty">No notifications yet.</p>
  <?php else: ?>
    <table>
      <tr><th>Type</th><th>Message</th><th>When</th></tr>
      <?php foreach ($notes as $n): ?>
      <tr>
        <td><span class="badge badge-rare"><?= e($n['type']) ?></span></td>
        <td><?= e($n['message']) ?></td>
        <td class="muted"><?= e(date('d M Y, H:i', strtotime($n['created_at']))) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
