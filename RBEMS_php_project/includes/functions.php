<?php
// ---------------------------------------------------------------------
// Helper functions used across RBEMS
// ---------------------------------------------------------------------

/**
 * Determines whether a DONOR's blood profile is compatible as a source
 * for a RECIPIENT profile, applying rare-phenotype override rules first.
 */
function isCompatible(
    string $donorGroup, string $donorRh, string $donorKell, bool $donorBombay,
    string $recGroup,   string $recRh,   string $recKell,   bool $recBombay
): bool {
    // Rule 1: Bombay phenotype recipients can only receive Bombay phenotype blood
    if ($recBombay) {
        return $donorBombay;
    }
    if ($donorBombay) {
        // Bombay donor blood lacks the H antigen entirely - only safe for another Bombay recipient
        return false;
    }

    // Rule 2: Rh-null recipients can only receive Rh-null blood
    if ($recRh === 'Rh-null') {
        return $donorRh === 'Rh-null';
    }
    if ($donorRh === 'Rh-null') {
        // Rh-null blood is the universal Rh donor (lacks all Rh antigens)
        // safe for any Rh recipient, subject to ABO/Kell rules below
    }

    // Rule 3: standard ABO compatibility (recipient can receive from...)
    $aboMap = [
        'O'  => ['O'],
        'A'  => ['A', 'O'],
        'B'  => ['B', 'O'],
        'AB' => ['A', 'B', 'AB', 'O'],
    ];
    if (!in_array($donorGroup, $aboMap[$recGroup] ?? [], true)) {
        return false;
    }

    // Rule 4: Rh compatibility (Positive recipients can take either, Negative recipients need Negative)
    if ($recRh === 'Negative' && $donorRh !== 'Negative' && $donorRh !== 'Rh-null') {
        return false;
    }

    // Rule 5: Kell compatibility (Kell-negative recipients should not receive Kell-positive blood)
    if ($recKell === 'Negative' && $donorKell === 'Positive') {
        return false;
    }

    return true;
}

/**
 * Donation eligibility interval rule: 90 days for male donors, 120 for others.
 * Returns ['eligible' => bool, 'days_left' => int]
 */
function checkEligibility(?string $lastDonationDate, string $gender): array
{
    if (!$lastDonationDate) {
        return ['eligible' => true, 'days_left' => 0];
    }
    $intervalDays = ($gender === 'Male') ? 90 : 120;
    $daysSince = (int) floor((time() - strtotime($lastDonationDate)) / 86400);
    $daysLeft = max(0, $intervalDays - $daysSince);
    return ['eligible' => $daysLeft === 0, 'days_left' => $daysLeft];
}

function daysUntil(string $date): int
{
    return (int) ceil((strtotime($date) - time()) / 86400);
}

function isLoggedIn(): bool
{
    return isset($_SESSION['admin_id']);
}

function isPatientLoggedIn(): bool
{
    return !empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'patient';
}

function isDonorLoggedIn(): bool
{
    return !empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'donor';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: admin_login.php');
        exit;
    }
}

function requirePatientLogin(): void
{
    if (!isPatientLoggedIn()) {
        header('Location: admin_login.php');
        exit;
    }
}

function requireDonorLogin(): void
{
    if (!isDonorLoggedIn()) {
        header('Location: admin_login.php');
        exit;
    }
}

function e(string $val): string
{
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}
