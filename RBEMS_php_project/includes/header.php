<?php if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' · RBEMS' : 'RBEMS' ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="topbar">
  <a href="index.php" class="brand">
    <span class="brand-mark">RB</span>
    <span class="brand-text">RBEMS <em>Rare Blood Emergency &amp; Matching System</em></span>
  </a>
  <nav class="mainnav">
    <a href="index.php">Dashboard</a>
    <a href="donor_register.php">Register Donor</a>
    <a href="donor_list.php">Donors</a>
    <a href="compatibility_check.php">Cross-Match</a>
    <a href="inventory.php">Inventory</a>
    <a href="hospital_request.php">New Request</a>
    <a href="request_list.php">Requests</a>
    <a href="notifications.php">Notifications</a>
    <?php if (function_exists('isPatientLoggedIn') && isPatientLoggedIn()): ?>
      <a href="patient_dashboard.php">Patient Portal</a>
      <a href="logout.php">Log out (<?= e($_SESSION['user_name'] ?? 'Patient') ?>)</a>
    <?php elseif (function_exists('isDonorLoggedIn') && isDonorLoggedIn()): ?>
      <a href="donor_dashboard.php">Donor Portal</a>
      <a href="logout.php">Log out (<?= e($_SESSION['user_name'] ?? 'Donor') ?>)</a>
    <?php elseif (function_exists('isLoggedIn') && isLoggedIn()): ?>
      <a href="admin_dashboard.php">Admin</a>
      <a href="logout.php">Log out (<?= e($_SESSION['admin_username'] ?? 'Admin') ?>)</a>
    <?php else: ?>
      <a href="register.php">Register</a>
      <a href="admin_login.php">Login</a>
    <?php endif; ?>
  </nav>
</div>
<main class="page">
