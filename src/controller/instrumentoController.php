<?php

require_once __DIR__ . '/../model/instrumento.php';
require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../repository/instrumento.php';
require_once __DIR__ . '/logsController.php';

class InstrumentoController
{
    private $instrumentoDAO;

    public function __construct()
    {
        $this->instrumentoDAO = new InstrumentoDAO();
    }

    public function adicionarInstrumento($instrumento)
    {
        return $this->instrumentoDAO->inserir($instrumento);
    }

    public function listarInstrumentos()
    {
        return $this->instrumentoDAO->listarTodos();
    }

    public function buscarPorMusico($codigoMusico)
    {
        return $this->instrumentoDAO->listarPorMusico($codigoMusico);
    }

    public function remover($codigo)
    {
        return $this->instrumentoDAO->remover($codigo);
    }

    public function inserirRelacaoMusicoInstrumento($codigoMusico, $codigoInstrumento)
    {
        return $this->instrumentoDAO->inserirRelacaoMusicoInstrumento($codigoMusico, $codigoInstrumento);
    }

    public function removerRelacoesPorMusico($codigoMusico)
    {
        return $this->instrumentoDAO->removerRelacoesPorMusico($codigoMusico);
    }

    /**
     * Valida e executa as acções do formulário.
     * Devolve ['msg' => ...] ou ['erro' => ...].
     */
    public function processarPost($utilizador, $post)
    {
        return match ($post['acao'] ?? '') {
            'salvar' => $this->guardarInstrumento($utilizador, $post),
            'remover' => $this->eliminarInstrumento($utilizador, $post),
            default => [],
        };
    }

    private function guardarInstrumento($utilizador, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'adicionar')) {
            return ['erro' => $bloqueio];
        }

        $nome = trim($post['nome'] ?? '');
        if ($nome === '') {
            return ['erro' => 'O nome do instrumento é obrigatório.'];
        }

        $novoId = $this->adicionarInstrumento(new Instrumento(null, $nome));
        if ($novoId <= 0) {
            return ['erro' => 'Erro ao adicionar (já existe?).'];
        }

        LogsController::registar('Registou o instrumento: ' . $nome);
        return ['msg' => 'Instrumento adicionado com sucesso.'];
    }

    private function eliminarInstrumento($utilizador, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'remover')) {
            return ['erro' => $bloqueio];
        }

        $id = (int)($post['sel'] ?? 0);
        if ($id === 0) {
            return ['erro' => 'Seleccione um instrumento.'];
        }

        if (!$this->remover($id)) {
            return ['erro' => 'Não foi possível remover (o instrumento está associado a músicos).'];
        }

        LogsController::registar('Removeu o instrumento ID ' . $id);
        return ['msg' => 'Instrumento removido.'];
    }

    /**
     * Lista já filtrada pela pesquisa em memória.
     */
    public function listarComPesquisa($termoPesquisa)
    {
        $instrumentos = $this->listarInstrumentos();
        $termoPesquisa = trim($termoPesquisa);

        if ($termoPesquisa === '') {
            return $instrumentos;
        }

        return array_values(array_filter($instrumentos, function ($instrumento) use ($termoPesquisa) {
            $alvo = mb_strtolower($instrumento->getCodigo() . ' ' . $instrumento->getNome());
            return str_contains($alvo, mb_strtolower($termoPesquisa));
        }));
    }
}
