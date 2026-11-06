<?php

require_once __DIR__ . '/../repository/nivelAcesso.php';
require_once __DIR__ . '/../model/nivelacesso.php';

class NivelController
{
    private $nivelDAO;

    public function __construct()
    {
        $this->nivelDAO = new NivelAcessoDAO();
    }

    public function adicionarNivel($nome)
    {
        return $this->nivelDAO->inserir($nome);
    }

    public function listarNiveis()
    {
        return $this->nivelDAO->listarNiveis();
    }

    public function listarPerfis()
    {
        return $this->nivelDAO->listarPerfis();
    }

    public function buscarPorCodigo($codigo)
    {
        return $this->nivelDAO->buscarPorCodigo($codigo);
    }

    public function criar($nome, $perm)
    {
        return $this->nivelDAO->criar($nome, $perm);
    }

    public function atualizar($codigo, $nome, $perm)
    {
        return $this->nivelDAO->atualizar($codigo, $nome, $perm);
    }

    public function remover($codigo)
    {
        return $this->nivelDAO->remover($codigo);
    }
}
