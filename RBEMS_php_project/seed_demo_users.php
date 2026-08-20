<?php
require_once __DIR__ . '/config/db.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)');
$adminStmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)');
$adminStmt->execute(['admin1', hash('sha256', '12345678')]);

$patients = [
    [
        'name' => 'Rahima Khatun',
        'username' => 'rahima',
        'dob' => '1992-04-18',
        'weight' => 58.5,
        'blood_group' => 'O',
        'address' => 'Dhaka',
        'phone' => '01710000010',
        'password' => 'patient123',
    ],
    [
        'name' => 'Imran Hossain',
        'username' => 'imran',
        'dob' => '1988-11-02',
        'weight' => 72.0,
        'blood_group' => 'A',
        'address' => 'Chattogram',
        'phone' => '01710000013',
        'password' => 'patient456',
    ],
];

$donors = [
    [
        'name' => 'Nabil Hasan',
        'username' => 'nabil',
        'dob' => '1995-09-22',
        'address' => 'Dhanmondi, Dhaka',
        'phone' => '01710000011',
        'habits' => 'Healthy and active, no smoking',
        'password' => 'donor123',
        'approved' => 1,
    ],
    [
        'name' => 'Samira Ahmed',
        'username' => 'samira',
        'dob' => '2000-01-10',
        'address' => 'Gulshan, Dhaka',
        'phone' => '01710000012',
        'habits' => 'Regular exercise and no recent illness',
        'password' => 'donor456',
        'approved' => 0,
    ],
];

foreach ($patients as $patient) {
    $stmt = $pdo->prepare('INSERT INTO patient_users (name, username, dob, weight, blood_group, address, phone, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), username = VALUES(username)');
    $stmt->execute([
        $patient['name'],
        $patient['username'],
        $patient['dob'],
        $patient['weight'],
        $patient['blood_group'],
        $patient['address'],
        $patient['phone'],
        password_hash($patient['password'], PASSWORD_DEFAULT),
    ]);
}

foreach ($donors as $donor) {
    $stmt = $pdo->prepare('INSERT INTO donor_users (name, username, dob, address, phone, habits, password_hash, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), username = VALUES(username)');
    $stmt->execute([
        $donor['name'],
        $donor['username'],
        $donor['dob'],
        $donor['address'],
        $donor['phone'],
        $donor['habits'],
        password_hash($donor['password'], PASSWORD_DEFAULT),
        $donor['approved'],
    ]);
}

echo "Demo patient and donor accounts seeded successfully.\n";
