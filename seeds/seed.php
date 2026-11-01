#!/usr/bin/env php
<?php

/**
 * Creates the initial administrator account.
 *
 * The database dump ships without user accounts, so a fresh install needs a
 * bootstrap user. This script is idempotent: if the username already exists it
 * does nothing and exits successfully.
 *
 * Usage:
 *   php seeds/seed.php [username] [password]
 *
 * Defaults: admin / admin123 (the account is flagged for a password change on
 * first login, so the default only unlocks the application once).
 */

require_once __DIR__ . '/../src/repository/connection.php';
require_once __DIR__ . '/../src/repository/utilizador.php';
require_once __DIR__ . '/../src/model/nivelacesso.php';
require_once __DIR__ . '/../src/model/utilizador.php';

const ADMIN_PROFILE_ID = 3; // NivelAcesso: Administrador
const MIN_PASSWORD_LENGTH = 8;

$username = $argv[1] ?? 'admin';
$password = $argv[2] ?? 'admin123';

if (trim($username) === '') {
    fwrite(STDERR, "Username cannot be empty.\n");
    exit(1);
}

if (strlen($password) < MIN_PASSWORD_LENGTH) {
    fwrite(STDERR, 'Password must have at least ' . MIN_PASSWORD_LENGTH . " characters.\n");
    exit(1);
}

$conn = Connection::connectionDB();

$ps = $conn->prepare('SELECT Codigo_User FROM Utilizador WHERE UserName = ?');
$ps->bind_param('s', $username);
$ps->execute();

if ($ps->get_result()->fetch_assoc()) {
    echo "User '{$username}' already exists, nothing to do.\n";
    exit(0);
}

$utilizador = new Utilizador(
    null,
    'Administrador',
    'Sistema',
    $username,
    null,
    new NivelAcesso(ADMIN_PROFILE_ID, ''),
    null,
    null,
    $password,
    true // Primeiro_Acesso: forces a password change on first login
);

$dao = new UtilizadorDAO();
if (!$dao->inserir($utilizador)) {
    fwrite(STDERR, "Failed to create the administrator account.\n");
    exit(1);
}

echo "Administrator account created.\n";
echo "  username: {$username}\n";
echo "  password: {$password}\n";
echo "The application will require a password change on the first login.\n";
