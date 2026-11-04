<?php

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/../model/faixa.php';
require_once __DIR__ . '/../model/cantor.php';
require_once __DIR__ . '/../model/compositor.php';
require_once __DIR__ . '/../model/musico.php';
require_once __DIR__ . '/../model/produtor.php';
require_once __DIR__ . '/../model/instrumento.php';

class FaixaDAO
{
    public function inserir($faixa, $codigoDisco)
    {
        $conn = Connection::connectionDB();
        $sql = "INSERT INTO Faixa (Nome_Faixa, Artista_Principal, Duracao, Numero_Faixa, idDisco) VALUES (?, ?, ?, ?, ?)";
        $ps = $conn->prepare($sql);

        $nomeFaixa = $faixa->getNomeFaixa();
        $artistaPrincipal = $faixa->getArtistaPrincipal();
        $duracao = $faixa->getDuracao();
        $numeroFaixa = $faixa->getNumeroFaixa();

        $ps->bind_param("sssii", $nomeFaixa, $artistaPrincipal, $duracao, $numeroFaixa, $codigoDisco);

        try {
            if ($ps->execute()) {
                return $conn->insert_id;
            }
        } catch (mysqli_sql_exception $e) {
            return -1;
        }

        return -1;
    }

    public function listarTodos()
    {
        $conn = Connection::connectionDB();
        $ps = $conn->prepare("SELECT * FROM Faixa");
        $ps->execute();
        $rows = $ps->get_result();

        $faixas = [];
        while ($row = $rows->fetch_assoc()) {
            $faixas[] = $this->mapearFaixa($row);
        }

        return $faixas;
    }

    public function listarPorCodigo($codigoDisco)
    {
        $conn = Connection::connectionDB();
        $ps = $conn->prepare("SELECT * FROM Faixa WHERE idDisco = ?");
        $ps->bind_param("i", $codigoDisco);
        $ps->execute();
        $rows = $ps->get_result();

        $faixas = [];
        while ($row = $rows->fetch_assoc()) {
            $faixas[] = $this->mapearFaixa($row);
        }

        return $faixas;
    }

    public function listarFaixaPorCodigo($codigoFaixa)
    {
        $conn = Connection::connectionDB();
        $ps = $conn->prepare("SELECT * FROM Faixa WHERE idFaixa = ?");
        $ps->bind_param("i", $codigoFaixa);
        $ps->execute();
        $row = $ps->get_result()->fetch_assoc();

        return $row ? $this->mapearFaixa($row) : new Faixa();
    }

    public function atualizar($faixa, $codigoFaixa)
    {
        $conn = Connection::connectionDB();
        $sql = "UPDATE Faixa SET Nome_Faixa = ?, Artista_Principal = ?, Duracao = ? WHERE idFaixa = ?";
        $ps = $conn->prepare($sql);

        $nomeFaixa = $faixa->getNomeFaixa();
        $artistaPrincipal = $faixa->getArtistaPrincipal();
        $duracao = $faixa->getDuracao();
        $ps->bind_param("sssi", $nomeFaixa, $artistaPrincipal, $duracao, $codigoFaixa);

        return $ps->execute();
    }

    public function remover($idFaixa)
    {
        if ($this->temRelacionamento($idFaixa)) {
            return false;
        }

        $conn = Connection::connectionDB();
        $ps = $conn->prepare("DELETE FROM Faixa WHERE idFaixa = ?");
        $ps->bind_param("i", $idFaixa);
        $ps->execute();

        return $ps->affected_rows > 0;
    }

    public function temRelacionamento($idFaixa)
    {
        $conn = Connection::connectionDB();
        $sql = "SELECT
                (SELECT COUNT(*) FROM Faixa_Compositor WHERE Codigo_Faixa = ?) +
                (SELECT COUNT(*) FROM Faixa_Cantor WHERE Codigo_Faixa = ?) +
                (SELECT COUNT(*) FROM Faixa_Musico WHERE Codigo_Faixa = ?) +
                (SELECT COUNT(*) FROM Faixa_Produtor WHERE Codigo_Faixa = ?)";
        $ps = $conn->prepare($sql);
        $ps->bind_param("iiii", $idFaixa, $idFaixa, $idFaixa, $idFaixa);
        $ps->execute();
        $ps->bind_result($total);
        $ps->fetch();

        return $total > 0;
    }

    public function removerCompositor($idFaixa, $idCompositor)
    {
        return $this->executarRemocao(
            "DELETE FROM Faixa_Compositor WHERE Codigo_Faixa = ? AND Codigo_Compositor = ?",
            $idFaixa,
            $idCompositor
        );
    }

    public function removerCantor($idFaixa, $idCantor)
    {
        return $this->executarRemocao(
            "DELETE FROM Faixa_Cantor WHERE Codigo_Faixa = ? AND Codigo_Cantor = ?",
            $idFaixa,
            $idCantor
        );
    }

    public function removerMusico($idFaixa, $idMusico)
    {
        return $this->executarRemocao(
            "DELETE FROM Faixa_Musico WHERE Codigo_Faixa = ? AND Codigo_Musico = ?",
            $idFaixa,
            $idMusico
        );
    }

    public function removerProdutor($idFaixa, $idProdutor)
    {
        return $this->executarRemocao(
            "DELETE FROM Faixa_Produtor WHERE Codigo_Faixa = ? AND Codigo_Produtor = ?",
            $idFaixa,
            $idProdutor
        );
    }

    public function listarCompositoresPorFaixa($idFaixa)
    {
        $conn = Connection::connectionDB();
        $sql = "SELECT c.* FROM Compositor c "
            . "INNER JOIN Faixa_Compositor fc ON c.Codigo_Compositor = fc.Codigo_Compositor "
            . "WHERE fc.Codigo_Faixa = ?";
        $ps = $conn->prepare($sql);
        $ps->bind_param("i", $idFaixa);
        $ps->execute();
        $rows = $ps->get_result();

        $compositores = [];
        while ($row = $rows->fetch_assoc()) {
            $compositores[] = new Compositor(
                $row['Codigo_Compositor'],
                $row['Nome_Compositor'],
                $row['Apelido_Compositor'],
                $row['Contacto_Compositor'],
                $row['Email_Compositor']
            );
        }

        return $compositores;
    }

    public function listarCantoresPorFaixa($idFaixa)
    {
        $conn = Connection::connectionDB();
        $sql = "SELECT c.* FROM Cantor c "
            . "INNER JOIN Faixa_Cantor fc ON c.Codigo_Cantor = fc.Codigo_Cantor "
            . "WHERE fc.Codigo_Faixa = ?";
        $ps = $conn->prepare($sql);
        $ps->bind_param("i", $idFaixa);
        $ps->execute();
        $rows = $ps->get_result();

        $cantores = [];
        while ($row = $rows->fetch_assoc()) {
            $cantores[] = new Cantor(
                $row['Codigo_Cantor'],
                $row['Nome_Cantor'],
                $row['Apelido_Cantor'],
                $row['Contacto_Cantor'],
                $row['Email_Cantor']
            );
        }

        return $cantores;
    }

    public function listarMusicosPorFaixa($idFaixa)
    {
        $conn = Connection::connectionDB();
        $sql = "SELECT m.* FROM Musico m "
            . "INNER JOIN Faixa_Musico fm ON m.Codigo_Musico = fm.Codigo_Musico "
            . "WHERE fm.Codigo_Faixa = ?";
        $ps = $conn->prepare($sql);
        $ps->bind_param("i", $idFaixa);
        $ps->execute();
        $rows = $ps->get_result();

        $musicos = [];
        while ($row = $rows->fetch_assoc()) {
            $musicos[] = new Musico(
                $row['Codigo_Musico'],
                $row['Nome_Musico'],
                $row['Apelido_Musico'],
                $this->buscarInstrumentosDoMusico($row['Codigo_Musico']),
                $row['Contacto_Musico'],
                $row['Email_Musico']
            );
        }

        return $musicos;
    }

    public function listarProdutoresPorFaixa($idFaixa)
    {
        $conn = Connection::connectionDB();
        $sql = "SELECT p.* FROM Produtor p "
            . "INNER JOIN Faixa_Produtor fp ON p.Codigo_Prod = fp.Codigo_Produtor "
            . "WHERE fp.Codigo_Faixa = ?";
        $ps = $conn->prepare($sql);
        $ps->bind_param("i", $idFaixa);
        $ps->execute();
        $rows = $ps->get_result();

        $produtores = [];
        while ($row = $rows->fetch_assoc()) {
            $produtores[] = new Produtor(
                $row['Codigo_Prod'],
                $row['Nome_Prod'],
                $row['Apelido_Produtor'],
                $row['Contacto_Prod'],
                $row['Email_Prod']
            );
        }

        return $produtores;
    }

    public function inserirRelacaoCompositor($idFaixa, $idCompositor)
    {
        $sql = "INSERT INTO Faixa_Compositor (Codigo_Faixa, Codigo_Compositor) VALUES (?, ?)";
        return $this->executarRelacao($sql, $idFaixa, $idCompositor);
    }

    public function inserirRelacaoCantor($idFaixa, $idCantor)
    {
        $sql = "INSERT INTO Faixa_Cantor (Codigo_Faixa, Codigo_Cantor) VALUES (?, ?)";
        return $this->executarRelacao($sql, $idFaixa, $idCantor);
    }

    public function inserirRelacaoMusico($idFaixa, $idMusico)
    {
        $sql = "INSERT INTO Faixa_Musico (Codigo_Faixa, Codigo_Musico) VALUES (?, ?)";
        return $this->executarRelacao($sql, $idFaixa, $idMusico);
    }

    public function inserirRelacaoProdutor($idFaixa, $idProdutor)
    {
        $sql = "INSERT INTO Faixa_Produtor (Codigo_Faixa, Codigo_Produtor) VALUES (?, ?)";
        return $this->executarRelacao($sql, $idFaixa, $idProdutor);
    }

    public function buscarInstrumentosDoMusico($codigoMusico)
    {
        $sql = "SELECT i.Codigo, i.NomeInstrumento "
            . "FROM Instrumento i "
            . "INNER JOIN Musico_Instrumento mi "
            . "ON i.Codigo = mi.Codigo_Instr "
            . "WHERE mi.Codigo_Musico = ?";

        $conn = Connection::connectionDB();
        $ps = $conn->prepare($sql);
        $ps->bind_param("i", $codigoMusico);
        $ps->execute();
        $rows = $ps->get_result();

        $instrumentos = [];
        while ($row = $rows->fetch_assoc()) {
            $instrumentos[] = new Instrumento(
                $row['Codigo'],
                $row['NomeInstrumento']
            );
        }

        return $instrumentos;
    }

    private function mapearFaixa($row)
    {
        return new Faixa(
            $row['Nome_Faixa'],
            $row['Artista_Principal'],
            $row['Duracao'],
            $row['Numero_Faixa'],
            $row['idFaixa']
        );
    }

    private function executarRemocao($sql, $idFaixa, $idPessoa)
    {
        $conn = Connection::connectionDB();
        $ps = $conn->prepare($sql);
        $ps->bind_param("ii", $idFaixa, $idPessoa);
        $ps->execute();

        return $ps->affected_rows > 0;
    }

    private function executarRelacao($sql, $idFaixa, $idPessoa)
    {
        $conn = Connection::connectionDB();
        $ps = $conn->prepare($sql);
        $ps->bind_param("ii", $idFaixa, $idPessoa);

        return $ps->execute();
    }
}
