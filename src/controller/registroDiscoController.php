<?php

require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../model/discocompacto.php';
require_once __DIR__ . '/../model/edicao.php';
require_once __DIR__ . '/../repository/disco.php';
require_once __DIR__ . '/../repository/generorepository.php';
require_once __DIR__ . '/../repository/compositor.php';
require_once __DIR__ . '/../repository/musico.php';
require_once __DIR__ . '/../repository/cantor.php';
require_once __DIR__ . '/../repository/produtor.php';
require_once __DIR__ . '/../repository/gravadora.php';
require_once __DIR__ . '/../repository/editora.php';
require_once __DIR__ . '/../repository/edicao.php';
require_once __DIR__ . '/logsController.php';

/**
 * Controller da página de registo de discos.
 *
 * Concentra a validação do formulário, a gravação do disco e das relações e a
 * montagem das listas usadas nos modais de selecção.
 */
class RegistoDiscoController
{
    private $discoDAO;
    private $generoDAO;
    private $compositorDAO;
    private $musicoDAO;
    private $cantorDAO;
    private $produtorDAO;
    private $gravadoraDAO;
    private $editoraDAO;
    private $edicaoDAO;

    public function __construct()
    {
        $this->discoDAO      = new DiscoDAO();
        $this->generoDAO     = new GeneroDAO();
        $this->compositorDAO = new CompositorDAO();
        $this->musicoDAO     = new MusicoDAO();
        $this->cantorDAO     = new CantorDAO();
        $this->produtorDAO   = new ProdutorDAO();
        $this->gravadoraDAO  = new GravadoraDAO();
        $this->editoraDAO    = new EditoraDAO();
        $this->edicaoDAO     = new EdicaoDAO();
    }

    /**
     * Valida e grava o disco submetido.
     * Devolve ['redirect' => ...] em caso de sucesso ou ['erro' => ...].
     */
    public function processarPost($utilizador, $post)
    {
        if ($bloq = Permissao::bloquear($utilizador, 'adicionar')) {
            return ['erro' => $bloq];
        }

        $titulo     = trim($post['titulo'] ?? '');
        $preco      = ($post['preco'] ?? '') !== '' ? (float)$post['preco'] : 0;
        $dataEdicao = $post['ano_edicao'] ?? '';
        $ano        = $dataEdicao !== '' ? (int)substr($dataEdicao, 0, 4) : null;   // o disco guarda só o ano
        $codEditora = (int)($post['editora'] ?? 0);

        if ($titulo === '') {
            return ['erro' => 'O título é obrigatório.'];
        }
        if (empty($post['generos'])) {
            return ['erro' => 'Escolha pelo menos um género.'];
        }
        if ($codEditora > 0 && $dataEdicao === '') {
            return ['erro' => 'Escolha a data de edição para associar a editora.'];
        }

        $disco  = new DiscoCompacto(0, $titulo, $preco, $ano, [], [], [], [], [], [], [], null, []);
        $codigo = $this->discoDAO->inserir($disco);

        if ($codigo <= 0) {
            return ['erro' => 'Erro ao registar o disco.'];
        }

        // género(s)
        foreach ($post['generos'] as $g) {
            $this->generoDAO->inserirRelacaoGeneroDisco($codigo, (int)$g);
        }

        // participantes
        foreach (($post['compositores'] ?? []) as $c) {
            $this->compositorDAO->inserirRelacaoDiscoCompositor($codigo, (int)$c);
        }
        foreach (($post['musicos'] ?? []) as $m) {
            $this->musicoDAO->inserirRelacaoDiscoMusico($codigo, (int)$m);
        }
        foreach (($post['cantores'] ?? []) as $c) {
            $this->cantorDAO->inserirRelacaoDiscoCantor($codigo, (int)$c);
        }

        // produção
        foreach (($post['produtores'] ?? []) as $p) {
            $this->produtorDAO->inserirRelacaoDiscoProdutor($codigo, (int)$p);
        }
        foreach (($post['gravadoras'] ?? []) as $g) {
            $this->gravadoraDAO->inserirRelacaoDiscoGravadora($codigo, (int)$g);
        }
        if ($codEditora > 0) {
            $this->edicaoDAO->inserir(new Edicao($codigo, $codEditora, $dataEdicao));
        }

        LogsController::registar('Registou o disco: ' . $titulo);

        return ['redirect' => 'registro.php?ok=' . $codigo];
    }

    /**
     * Prepara as listas dos modais e a mensagem de sucesso.
     */
    public function prepararEcra($get)
    {
        $msg = isset($get['ok']) ? 'Disco registado com sucesso' : '';
        $erro = '';

        /* Listas para os modais (código => texto) */
        $generos = [];
        foreach ($this->generoDAO->listarTodos() as $g) {
            $generos[$g->getCodigoGenero()] = $g->getNomeGenero();
        }

        $compositores = [];
        foreach ($this->compositorDAO->listarTodos() as $c) {
            $compositores[$c->getCodigoCompositor()] = $c->getNomeCompositor() . ' ' . $c->getApelidoCompositor();
        }

        $musicos = [];
        foreach ($this->musicoDAO->listarTodos() as $m) {
            $musicos[$m->getCodigoMusico()] = $m->getNomeMusico() . ' ' . $m->getApelidoMusico();
        }

        $cantores = [];
        foreach ($this->cantorDAO->listarTodos() as $c) {
            $cantores[$c->getCodigoCantor()] = $c->getNomeCantor() . ' ' . $c->getApelidoCantor();
        }

        $produtores = [];
        foreach ($this->produtorDAO->listarTodos() as $p) {
            $produtores[$p->getCodigoProdutor()] = $p->getNomeProdutor() . ' ' . $p->getApelidoProdutor();
        }

        $gravadoras = [];
        foreach ($this->gravadoraDAO->listarTodos() as $g) {
            $gravadoras[$g->getCodigoGravadora()] = $g->getNomeGravadora();
        }

        $editoras = [];
        foreach ($this->editoraDAO->listarTodos() as $e) {
            $editoras[$e->getCodigoEditora()] = $e->getNomeEditora();
        }

        return compact(
            'msg', 'erro', 'generos', 'compositores', 'musicos', 'cantores',
            'produtores', 'gravadoras', 'editoras'
        );
    }
}
