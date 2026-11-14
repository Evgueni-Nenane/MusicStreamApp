#!/usr/bin/env php
<?php
require_once __DIR__ . '/../src/repository/connection.php';
require_once __DIR__ . '/../src/repository/utilizador.php';
require_once __DIR__ . '/../src/model/nivelacesso.php';
require_once __DIR__ . '/../src/model/utilizador.php';

$contas = [
    ['Administrador', 'Demo', 'admin', 'admin123', 3, 'admin@discogest.local', '900000001'],
    ['Operador', 'Demo', 'operador', 'operador123', 1, 'operador@discogest.local', '900000002'],
    ['Super', 'Operador', 'superoperador', 'super123', 2, 'super@discogest.local', '900000003'],
    ['Auditor', 'Demo', 'auditor', 'auditor123', 4, 'auditor@discogest.local', '900000004'],
];

$conn = Connection::connectionDB();
$dao = new UtilizadorDAO();

foreach ($contas as [$nome, $apelido, $username, $senha, $perfil, $email, $contacto]) {
    $ps = $conn->prepare('SELECT Codigo_User FROM Utilizador WHERE UserName = ?');
    $ps->bind_param('s', $username);
    $ps->execute();

    if ($ps->get_result()->fetch_assoc()) {
        echo "Conta '{$username}' já existe.\n";
        continue;
    }

    $utilizador = new Utilizador(
        null,
        $nome,
        $apelido,
        $username,
        null,
        new NivelAcesso($perfil, ''),
        $email,
        $contacto,
        $senha,
        false
    );

    if (!$dao->inserir($utilizador)) {
        fwrite(STDERR, "Falha ao criar '{$username}'.\n");
        continue;
    }

    echo "Criada: {$username} / {$senha} (perfil {$perfil})\n";
}

$conn->query('UPDATE Utilizador SET Primeiro_Acesso = 0');
echo "Primeiro acesso desativado nas contas de demonstração.\n";
