<?php
/**
 * User Seeder — Run once after importing the SQL schema.
 * This generates correct bcrypt hashes and inserts demo users.
 *
 * Usage: php seed_users.php  (from project/database/ directory)
 *   OR visit: http://localhost/project/database/seed_users.php
 *   (then DELETE this file immediately after use)
 */

require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance()->getConnection();

$users = [
    [
        'name'     => 'System Admin',
        'email'    => 'admin@hazina-asili.com',
        'password' => 'Admin@1234',
        'role'     => 'admin',
    ],
    [
        'name'     => 'Dr. Jane Smith',
        'email'    => 'researcher@hazina-asili.com',
        'password' => 'Admin@1234',
        'role'     => 'researcher',
    ],
];

$inserted = 0;
foreach ($users as $u) {
    // Skip if email already exists
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$u['email']]);
    if ($check->fetch()) {
        echo "⚠️  Skipped (already exists): {$u['email']}\n";
        continue;
    }

    $hash = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare(
        "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->execute([$u['name'], $u['email'], $hash, $u['role']]);
    echo "✅ Created {$u['role']}: {$u['email']} / {$u['password']}\n";
    $inserted++;
}

echo "\nDone. {$inserted} user(s) created.\n";
echo "⚠️  DELETE this file now: project/database/seed_users.php\n";
