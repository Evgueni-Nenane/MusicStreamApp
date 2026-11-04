<?php

class NivelAcesso
{
    public const OPERADOR = 1;
    public const SUPEROPERADOR = 2;
    public const ADMINISTRADOR = 3;
    public const AUDITOR = 4;

    private $codigoNivel;
    private $nome;

    public function __construct($codigoNivel = null, $nome = null)
    {
        $this->codigoNivel = $codigoNivel;
        $this->nome = $nome;
    }

    public function getCodigoNivel()
    {
        return $this->codigoNivel;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;
    }

    public function toString()
    {
        return $this->getNome();
    }
}
