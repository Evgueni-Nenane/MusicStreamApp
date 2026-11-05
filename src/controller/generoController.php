<?php

require_once __DIR__ . '/../model/genero.php';
require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../repository/generorepository.php';
require_once __DIR__ . '/logsController.php';

class GeneroController
{
    private $generoDAO;

    public function __construct()
    {
        $this->generoDAO = new GeneroDAO();
    }

    public function adicionarGenero($genero)
    {
        return $this->generoDAO->inserir($genero);
    }

    public function listarGeneros()
    {
        return $this->generoDAO->listarTodos();
    }

    public function removerGenero($codigo)
    {
        return $this->generoDAO->remover($codigo);
    }

    /**
     * Valida e executa as acções do formulário.
     * Devolve ['msg' => ...] ou ['erro' => ...].
     */
    public function processarPost($utilizador, $post)
    {
        return match ($post['acao'] ?? '') {
            'salvar' => $this->guardarGenero($utilizador, $post),
            'remover' => $this->eliminarGenero($utilizador, $post),
            default => [],
        };
    }

    private function guardarGenero($utilizador, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'adicionar')) {
            return ['erro' => $bloqueio];
        }

        $nome = trim($post['nome'] ?? '');
        if ($nome === '') {
            return ['erro' => 'O nome do género é obrigatório.'];
        }

        $novoId = $this->adicionarGenero(new Genero(null, $nome));
        if ($novoId <= 0) {
            return ['erro' => 'Erro ao adicionar (já existe?).'];
        }

        LogsController::registar('Registou o género: ' . $nome);
        return ['msg' => 'Género adicionado com sucesso.'];
    }

    private function eliminarGenero($utilizador, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'remover')) {
            return ['erro' => $bloqueio];
        }

        $id = (int) ($post['sel'] ?? 0);
        if ($id === 0) {
            return ['erro' => 'Seleccione um género.'];
        }

        if (!$this->removerGenero($id)) {
            return ['erro' => 'Não foi possível remover (o género está associado a discos).'];
        }

        LogsController::registar('Removeu o género ID ' . $id);
        return ['msg' => 'Género removido.'];
    }

    /**
     * Lista já filtrada pela pesquisa em memória.
     */
    public function listarComPesquisa($termoPesquisa)
    {
        $generos = $this->listarGeneros();
        $termoPesquisa = trim($termoPesquisa);

        if ($termoPesquisa === '') {
            return $generos;
        }

        return array_values(array_filter($generos, function ($genero) use ($termoPesquisa) {
            $alvo = mb_strtolower($genero->getCodigoGenero() . ' ' . $genero->getNomeGenero());
            return str_contains($alvo, mb_strtolower($termoPesquisa));
        }));
    }
}
