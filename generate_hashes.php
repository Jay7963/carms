<?php
// Run this once: php generate_hashes.php
// Then copy the hashes into carms_seed.sql

$passwords = [
    'admin'   => 'Admin123!',
    'leader'  => 'Leader123!',
    'student' => 'Student123!',
];

foreach ($passwords as $user => $pass) {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    echo "$user ($pass): $hash\n";
}
