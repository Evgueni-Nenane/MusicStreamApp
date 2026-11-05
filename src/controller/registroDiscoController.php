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
        if ($bloqueio = Permissao::bloquear($utilizador, 'adicionar')) {
            return ['erro' => $bloqueio];
        }

        $titulo = trim($post['titulo'] ?? '');
        $preco = ($post['preco'] ?? '') !== '' ? (float) $post['preco'] : 0;
        $dataEdicao = $post['ano_edicao'] ?? '';
        $ano = $dataEdicao !== '' ? (int) substr($dataEdicao, 0, 4) : null;   // o disco guarda só o ano
        $editoraId = (int) ($post['editora'] ?? 0);

        if ($titulo === '') {
            return ['erro' => 'O título é obrigatório.'];
        }

        if (empty($post['generos'])) {
            return ['erro' => 'Escolha pelo menos um género.'];
        }

        if ($editoraId > 0 && $dataEdicao === '') {
            return ['erro' => 'Escolha a data de edição para associar a editora.'];
        }

        $disco = new DiscoCompacto(0, $titulo, $preco, $ano, [], [], [], [], [], [], [], null, []);
        $codigoDisco = $this->discoDAO->inserir($disco);

        if ($codigoDisco <= 0) {
            return ['erro' => 'Erro ao registar o disco.'];
        }

        foreach ($post['generos'] as $generoId) {
            $this->generoDAO->inserirRelacaoGeneroDisco($codigoDisco, (int) $generoId);
        }

        foreach (($post['compositores'] ?? []) as $compositorId) {
            $this->compositorDAO->inserirRelacaoDiscoCompositor($codigoDisco, (int) $compositorId);
        }

        foreach (($post['musicos'] ?? []) as $musicoId) {
            $this->musicoDAO->inserirRelacaoDiscoMusico($codigoDisco, (int) $musicoId);
        }

        foreach (($post['cantores'] ?? []) as $cantorId) {
            $this->cantorDAO->inserirRelacaoDiscoCantor($codigoDisco, (int) $cantorId);
        }

        foreach (($post['produtores'] ?? []) as $produtorId) {
            $this->produtorDAO->inserirRelacaoDiscoProdutor($codigoDisco, (int) $produtorId);
        }

        foreach (($post['gravadoras'] ?? []) as $gravadoraId) {
            $this->gravadoraDAO->inserirRelacaoDiscoGravadora($codigoDisco, (int) $gravadoraId);
        }

        if ($editoraId > 0) {
            $this->edicaoDAO->inserir(new Edicao($codigoDisco, $editoraId, $dataEdicao));
        }

        LogsController::registar('Registou o disco: ' . $titulo);

        return ['redirect' => 'registro.php?ok=' . $codigoDisco];
    }

    /**
     * Prepara as listas dos modais e a mensagem de sucesso.
     */
    public function prepararEcra($get)
    {
        $mensagem = isset($get['ok']) ? 'Disco registado com sucesso' : '';

        $generos = $this->mapearOpcoes(
            $this->generoDAO->listarTodos(),
            fn($genero) => $genero->getCodigoGenero(),
            fn($genero) => $genero->getNomeGenero()
        );

        $compositores = $this->mapearOpcoes(
            $this->compositorDAO->listarTodos(),
            fn($compositor) => $compositor->getCodigoCompositor(),
            fn($compositor) => $compositor->getNomeCompositor() . ' ' . $compositor->getApelidoCompositor()
        );

        $musicos = $this->mapearOpcoes(
            $this->musicoDAO->listarTodos(),
            fn($musico) => $musico->getCodigoMusico(),
            fn($musico) => $musico->getNomeMusico() . ' ' . $musico->getApelidoMusico()
        );

        $cantores = $this->mapearOpcoes(
            $this->cantorDAO->listarTodos(),
            fn($cantor) => $cantor->getCodigoCantor(),
            fn($cantor) => $cantor->getNomeCantor() . ' ' . $cantor->getApelidoCantor()
        );

        $produtores = $this->mapearOpcoes(
            $this->produtorDAO->listarTodos(),
            fn($produtor) => $produtor->getCodigoProdutor(),
            fn($produtor) => $produtor->getNomeProdutor() . ' ' . $produtor->getApelidoProdutor()
        );

        $gravadoras = $this->mapearOpcoes(
            $this->gravadoraDAO->listarTodos(),
            fn($gravadora) => $gravadora->getCodigoGravadora(),
            fn($gravadora) => $gravadora->getNomeGravadora()
        );

        $editoras = $this->mapearOpcoes(
            $this->editoraDAO->listarTodos(),
            fn($editora) => $editora->getCodigoEditora(),
            fn($editora) => $editora->getNomeEditora()
        );

        return [
            'mensagem' => $mensagem,
            'generos' => $generos,
            'compositores' => $compositores,
            'musicos' => $musicos,
            'cantores' => $cantores,
            'produtores' => $produtores,
            'gravadoras' => $gravadoras,
            'editoras' => $editoras,
        ];
    }

    /**
     * Converte uma lista de objectos numa lista associativa código => texto.
     */
    private function mapearOpcoes(array $registos, callable $obterCodigo, callable $obterTexto)
    {
        $opcoes = [];

        foreach ($registos as $registo) {
            $opcoes[$obterCodigo($registo)] = $obterTexto($registo);
        }

        return $opcoes;
    }
}
