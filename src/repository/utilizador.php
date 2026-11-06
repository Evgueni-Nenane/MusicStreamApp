<?php

require_once __DIR__ . '/../model/utilizador.php';
require_once __DIR__ . '/../model/nivelacesso.php';
require_once __DIR__ . '/connection.php';

class UtilizadorDAO
{
    public function inserir($utilizador)
    {
        $conn = Connection::connectionDB();
        $sql = "INSERT INTO Utilizador (Nome, Apelido, UserName, Genero, Perfil, "
            . "Email, Contacto, Senha, Primeiro_Acesso, foto, foto_tipo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $ps = $conn->prepare($sql);

        $nome = $utilizador->getNome();
        $apelido = $utilizador->getApelido();
        $nomeUtilizador = $utilizador->getUserName();
        $genero = $utilizador->getGenero();
        $perfil = $utilizador->getPerfil()->getCodigoNivel();
        $email = $utilizador->getEmail();
        $contacto = $utilizador->getContacto();
        $senha = $utilizador->getSenha();
        $primeiroAcesso = $utilizador->isPrimeiroAcesso() ? 1 : 0;
        $foto = $utilizador->getFoto();          // conteúdo binário da imagem (longblob) ou null
        $fotoTipo = $utilizador->getFotoTipo();  // ex.: image/png
        $parametroFoto = null;                   // o blob segue por send_long_data()

        $ps->bind_param(
            "ssssisssibs",
            $nome,
            $apelido,
            $nomeUtilizador,
            $genero,
            $perfil,
            $email,
            $contacto,
            $senha,
            $primeiroAcesso,
            $parametroFoto,
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

    public function atualizar($codigoUtilizador, $utilizador)
    {
        $conn = Connection::connectionDB();

        $foto = $utilizador->getFoto();
        $fotoTipo = $utilizador->getFotoTipo();
        $temNovaFoto = ($foto !== null && $foto !== '');

        // Sem nova imagem, mantém a que já está na base de dados.
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
            $parametroFoto = null; // o blob segue por send_long_data()
            $ps->bind_param("bssisi", $parametroFoto, $fotoTipo, $perfil, $email, $contacto, $codigoUtilizador);
            $ps->send_long_data(0, $foto);
        } else {
            $ps->bind_param("issi", $perfil, $email, $contacto, $codigoUtilizador);
        }

        $ps->execute();
        return true;
    }

    /**
     * Devolve ['dados' => binário, 'tipo' => mime] ou null.
     */
    public function buscarFoto($codigoUtilizador)
    {
        $conn = Connection::connectionDB();
        $sql = "SELECT foto, foto_tipo FROM Utilizador WHERE Codigo_User = ?";
        $ps = $conn->prepare($sql);
        $ps->bind_param("i", $codigoUtilizador);
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
        $conn = Connection::connectionDB();
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

    public function buscarPorId($codigoUtilizador)
    {
        $conn = Connection::connectionDB();
        $sql = "SELECT Codigo_User, Nome, Apelido, UserName, Genero, Email, Contacto, NomeNivel, CodigoNivel"
            . " FROM Utilizador"
            . " INNER JOIN NivelAcesso ON Utilizador.Perfil = NivelAcesso.CodigoNivel"
            . " WHERE Codigo_User = ?";
        $ps = $conn->prepare($sql);
        $ps->bind_param("i", $codigoUtilizador);
        $ps->execute();
        $row = $ps->get_result()->fetch_assoc();

        if (!$row) {
            return null;
        }

        $perfil = new NivelAcesso($row['CodigoNivel'], $row['NomeNivel']);
        $utilizador = new Utilizador(
            perfil: $perfil,
            email: $row['Email'],
            contacto: $row['Contacto']
        );
        $utilizador->setNome($row['Nome']);
        $utilizador->setApelido($row['Apelido']);
        $utilizador->setUserName($row['UserName']);
        $utilizador->setGenero($row['Genero']);
        $utilizador->setCodigo($row['Codigo_User']);

        return $utilizador;
    }

    public function remover($codigoUtilizador)
    {
        $conn = Connection::connectionDB();

        // Confirma que o utilizador existe antes de apagar.
        if ($this->buscarPorId($codigoUtilizador) === null) {
            return false;
        }

        $sql = "DELETE FROM Utilizador WHERE Codigo_User = ?";
        $ps = $conn->prepare($sql);
        $ps->bind_param("i", $codigoUtilizador);
        $ps->execute();

        return true;
    }

    public function resetarSenha($codigoUtilizador, $novaSenha)
    {
        $conn = Connection::connectionDB();
        $ps = $conn->prepare("UPDATE Utilizador SET Senha = ?, Primeiro_Acesso = 1 WHERE Codigo_User = ?");
        $ps->bind_param("si", $novaSenha, $codigoUtilizador);
        $ps->execute();

        return $ps->affected_rows > 0;
    }
}
