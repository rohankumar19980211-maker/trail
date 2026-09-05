<?php
// Run: php test_jwt.php   (no framework; asserts only)
require_once __DIR__ . '/auth.php';

function bearer($t) { $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $t; }

$good = generate_jwt(['username' => 'admin', 'role' => 'admin', 'exp' => time() + 60]);
bearer($good);
$u = get_auth_user();
assert($u && $u['role'] === 'admin', 'valid token must authenticate');

// Forged payload, no valid signature -> the pre-fix privilege escalation.
$b64 = fn($s) => str_replace(['+','/','='], ['-','_',''], base64_encode($s));
$forged = $b64('{"typ":"JWT","alg":"HS256"}') . '.'
        . $b64(json_encode(['username' => 'mallory', 'role' => 'admin', 'exp' => time() + 60]))
        . '.' . $b64('not-a-signature');
bearer($forged);
assert(get_auth_user() === null, 'unsigned/forged token must be rejected');

// Correctly signed but expired.
bearer(generate_jwt(['username' => 'admin', 'role' => 'admin', 'exp' => time() - 1]));
assert(get_auth_user() === null, 'expired token must be rejected');

echo "ok\n";
