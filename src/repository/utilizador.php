<?php
require_once __DIR__ . '/../model/utilizador.php';
require_once __DIR__ . '/../model/nivelacesso.php';
require_once __DIR__ . '/connection.php';

class UtilizadorDAO
{
    public function inserir($utilizador)
    {
        $conn = connection::connectionDB();
        $sql = "INSERT INTO Utilizador (Nome, Apelido, UserName, Genero, Perfil, "
            . "Email, Contacto, Senha, Primeiro_Acesso, foto, foto_tipo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $ps = $conn->prepare($sql);

        $nome = $utilizador->getNome();
        $apelido = $utilizador->getApelido();
        $userName = $utilizador->getUser_name();
        $genero = $utilizador->getGenero();
        $perfil = $utilizador->getPerfil()->getCodigoNivel();
        $email = $utilizador->getEmail();
        $contacto = $utilizador->getContacto();
        $senha = $utilizador->getSenha();
        $primeiroAcesso = $utilizador->isPrimeiroAcesso() ? 1 : 0;
        $foto = $utilizador->getFoto();          // conteudo binario da imagem (longblob) ou null
        $fotoTipo = $utilizador->getFotoTipo();  // ex.: image/png
        $fotoParam = null;                       // o blob segue por send_long_data()

        $ps->bind_param(
            "ssssisssibs",
            $nome,
            $apelido,
            $userName,
            $genero,
            $perfil,
            $email,
            $contacto,
            $senha,
            $primeiroAcesso,
            $fotoParam,
            $fotoTipo
        );

        if ($foto !== null && $foto !== '') {
            $ps->send_long_data(9, $foto);
        }

        try {
            $ps->execute();
            return true;
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }

    public function atualizarUser($codigoUser, $utilizador)
    {
        $conn = connection::connectionDB();

        $foto = $utilizador->getFoto();
        $fotoTipo = $utilizador->getFotoTipo();
        $temNovaFoto = ($foto !== null && $foto !== '');

        // Sem nova imagem, mantem a que ja esta na base de dados
        if ($temNovaFoto) {
            $sql = "UPDATE Utilizador SET foto = ?, foto_tipo = ?, Perfil = ?, Email = ?, Contacto = ? WHERE Codigo_User = ?";
        } else {
            $sql = "UPDATE Utilizador SET Perfil = ?, Email = ?, Contacto = ? WHERE Codigo_User = ?";
        }
        $ps = $conn->prepare($sql);

        $perfil = $utilizador->getPerfil()->getCodigoNivel();
        $email = $utilizador->getEmail();
        $contacto = $utilizador->getContacto();

        if ($temNovaFoto) {
            $fotoParam = null; // o blob segue por send_long_data()
            $ps->bind_param("bssisi", $fotoParam, $fotoTipo, $perfil, $email, $contacto, $codigoUser);
            $ps->send_long_data(0, $foto);
        } else {
            $ps->bind_param("issi", $perfil, $email, $contacto, $codigoUser);
        }
        $ps->execute();
        return true;
    }

    // Devolve ['dados' => binario, 'tipo' => mime] ou null
    public function buscarFoto($codigoUser)
    {
        $conn = connection::connectionDB();
        $sql = "SELECT foto, foto_tipo FROM Utilizador WHERE Codigo_User = ?";
        $ps = $conn->prepare($sql);
        $ps->bind_param("i", $codigoUser);
        $ps->execute();
        $row = $ps->get_result()->fetch_assoc();

        if (!$row || $row['foto'] === null || $row['foto'] === '') {
            return null;
        }

        $tipo = $row['foto_tipo'];
        if ($tipo === null || $tipo === '') {
            $info = @getimagesizefromstring($row['foto']);
            $tipo = $info ? $info['mime'] : 'application/octet-stream';
        }

        return ['dados' => $row['foto'], 'tipo' => $tipo];
    }

    public function listarTodos()
    {
        $utilizadores = [];
        $conn = connection::connectionDB();
        $sql = "SELECT Codigo_User, Nome, Apelido, UserName, Genero, Email, Contacto, NomeNivel, Utilizador.Perfil "
            . "FROM Utilizador "
            . "INNER JOIN NivelAcesso ON Utilizador.Perfil = NivelAcesso.CodigoNivel";
        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()) {
            $perfil = new NivelAcesso($row['Perfil'], $row['NomeNivel']);
            $utilizadores[] = new Utilizador(
                codigo: $row['Codigo_User'],
                nome: $row['Nome'],
                apelido: $row['Apelido'],
                user_name: $row['UserName'],
                genero: $row['Genero'],
                perfil: $perfil,
                email: $row['Email'],
                contacto: $row['Contacto']
            );
        }
        return $utilizadores;
    }

    public function buscarPorId($codigoUser)
    {
        $conn = connection::connectionDB();
        $sql = "SELECT Codigo_User, Nome, Apelido, UserName, Genero, Email, Contacto, NomeNivel, CodigoNivel"
            . " FROM Utilizador"
            . " INNER JOIN NivelAcesso ON Utilizador.Perfil = NivelAcesso.CodigoNivel"
            . " WHERE Codigo_User = ?";
        $ps = $conn->prepare($sql);
        $ps->bind_param("i", $codigoUser);
        $ps->execute();
        $row = $ps->get_result()->fetch_assoc();

        if ($row) {
            $perfil = new NivelAcesso($row['CodigoNivel'], $row['NomeNivel']);
            $utilizador = new Utilizador(
                perfil: $perfil,
                email: $row['Email'],
                contacto: $row['Contacto']
            );
            $utilizador->setNome($row['Nome']);
            $utilizador->setApelido($row['Apelido']);
            $utilizador->setUser_name($row['UserName']);
            $utilizador->setGenero($row['Genero']);
            $utilizador->setCodigo($row['Codigo_User']);
            return $utilizador;
        }
        return null;
    }

   public function remover($codigoUser)
{
    $conn = Connection::connectionDB();

    // 1. Buscar ANTES de apagar
    $utilizador = $this->buscarPorId($codigoUser);
    if ($utilizador === null) {
        return false;   // não existe, nada a remover
    }

    // 2. Apagar
    $sql = "DELETE FROM Utilizador WHERE Codigo_User = ?";
    $ps = $conn->prepare($sql);
    $ps->bind_param("i", $codigoUser);
    $ps->execute();

    return true;
}


    public function resetarSenha($codigoUser, $novaSenha)
{
    $conn = connection::connectionDB();
    $ps = $conn->prepare("UPDATE Utilizador SET Senha = ?, Primeiro_Acesso = 1 WHERE Codigo_User = ?");
    $ps->bind_param("si", $novaSenha, $codigoUser);
    $ps->execute();

    return $ps->affected_rows > 0;
}
}
