<?php

require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../model/discocompacto.php';
require_once __DIR__ . '/../model/faixa.php';
require_once __DIR__ . '/../repository/disco.php';
require_once __DIR__ . '/../repository/faixa.php';
require_once __DIR__ . '/../repository/generorepository.php';
require_once __DIR__ . '/../repository/compositor.php';
require_once __DIR__ . '/../repository/musico.php';
require_once __DIR__ . '/../repository/cantor.php';
require_once __DIR__ . '/logsController.php';

/**
 * Controller da página de listagem de discos.
 *
 * Trata das acções de discos e faixas, da preparação dos modais (editar,
 * detalhes, faixas) e dos dados derivados que a view apresenta.
 */
class ListagemDiscosController
{
    private $discoDAO;
    private $faixaDAO;
    private $generoDAO;
    private $compositorDAO;
    private $musicoDAO;
    private $cantorDAO;

    public function __construct()
    {
        $this->discoDAO      = new DiscoDAO();
        $this->faixaDAO      = new FaixaDAO();
        $this->generoDAO     = new GeneroDAO();
        $this->compositorDAO = new CompositorDAO();
        $this->musicoDAO     = new MusicoDAO();
        $this->cantorDAO     = new CantorDAO();
    }

    /**
     * Executa a acção submetida.
     * Devolve ['msg' => ...] e, quando aplicável, ['det' => disco].
     */
    public function processarPost($utilizador, $post)
    {
        $acao = $post['acao'] ?? '';

        if ($acao === 'remover') {
            return $this->removerDisco($utilizador, $post);
        }
        if ($acao === 'atualizar') {
            return $this->atualizarDisco($utilizador, $post);
        }
        if ($acao === 'addfaixa') {
            return $this->adicionarFaixa($utilizador, $post);
        }
        if ($acao === 'atualizarfaixa') {
            return $this->atualizarFaixa($utilizador, $post);
        }
        if ($acao === 'removerfaixa') {
            return $this->removerFaixa($utilizador, $post);
        }

        return ['msg' => ''];
    }

    /**
     * Prepara tudo o que a página precisa para ser desenhada.
     */
    public function prepararEcra($utilizador, $get, $post = [])
    {
        $msg = '';
        $det = null;    // disco aberto em "Ver Detalhes"
        $edF = null;    // faixa a editar ("Editar Faixa" dentro de Ver Detalhes)
        $edFDisco = 0;  // disco da faixa em edição
        $edFComp = $edFMus = $edFCant = [];

        // Texto da pesquisa (vem por GET na pesquisa e nos botões Editar/Detalhes; por POST nas restantes acções)
        $q = trim($get['q'] ?? $post['q'] ?? '');

        /* ---------- Botões Editar / Adicionar Faixas / Ver Detalhes / Editar Faixa (GET) ---------- */
        $ed = null;   // disco a editar
        $fx = null;   // disco a que se vai juntar uma faixa
        $acaoGet = $get['acao'] ?? '';

        if ($acaoGet !== '') {
            $d = $this->discoDAO->buscarPorCodigo((int)($get['sel'] ?? 0));

            if ($acaoGet === 'editarfaixa') {
                // Editar Faixa: a faixa vem de sel_faixa; o disco vem de id (ou deduz-se da faixa).
                $edFDisco = (int)($get['id'] ?? 0);
                $fId = (int)($get['sel_faixa'] ?? 0);

                if (!Permissao::pode($utilizador, 'editar')) {
                    $msg = 'Sem permissão para editar.';
                } elseif ($fId > 0) {
                    $edF = $this->faixaDAO->listarFaixaPorCodigo($fId);
                    foreach ($this->faixaDAO->listarCompositoresPorFaixa($fId) as $c) $edFComp[] = $c->getCodigoCompositor();
                    foreach ($this->faixaDAO->listarMusicosPorFaixa($fId) as $m)      $edFMus[]  = $m->getCodigoMusico();
                    foreach ($this->faixaDAO->listarCantoresPorFaixa($fId) as $c)     $edFCant[] = $c->getCodigoCantor();
                }
                // Reabre os Detalhes no mesmo disco para dar contexto ao editar.
                $det = $edFDisco > 0 ? $this->discoDAO->buscarPorCodigo($edFDisco) : null;
                if (!$edF) $msg = 'Seleccione uma faixa.';
            } elseif (!$d) {
                $msg = 'Seleccione um disco.';
            } elseif ($acaoGet === 'editar') {
                $ed  = $d;
            } elseif ($acaoGet === 'faixas') {
                $fx  = $d;
            } elseif ($acaoGet === 'detalhes') {
                $det = $d;
            }
        }

        $discos = $this->discoDAO->listarTodos();   // depois das acções, para vir actualizado
        $todosGeneros = $this->generoDAO->listarTodos();

        /* ---------- Pesquisa: filtra por título, género, preço ou ano ---------- */
        if ($q !== '') {
            $discos = array_filter($discos, function ($d) use ($q) {
                $alvo = $d->getTitulo() . ' ' . $d->getGeneroMusicalTxt() . ' '
                      . number_format($d->getPreco(), 2, ',', '.') . ' ' . $d->getAnoEdicao();
                return mb_stripos($alvo, $q) !== false;
            });
        }

        /* ---------- Dados derivados dos modais ---------- */
        $dGeneros = '';
        $dFaixas = [];
        $dCompositores = $dMusicos = $dCantores = [];
        if ($det) {
            $dId = $det->getCodigoDisco();
            $dGeneros = implode(', ', array_map(fn($g) => $g->getNomeGenero(), $this->generoDAO->listarPorDisco($dId)));
            $dFaixas = $this->faixaDAO->listarPorCodigo($dId);
            $dCompositores = $this->compositorDAO->listarPorCodigo($dId);
            $dMusicos = $this->musicoDAO->listarPorCodigo($dId);
            $dCantores = $this->cantorDAO->listarPorCodigo($dId);
        }

        $generosDoDisco = [];
        if ($ed) {
            foreach ($this->generoDAO->listarPorDisco($ed->getCodigoDisco()) as $g) {
                $generosDoDisco[] = $g->getCodigoGenero();
            }
        }

        $edFMin = $edFSeg = 0;   // duração "mm:ss" partida em dois campos
        if ($edF) {
            [$edFMin, $edFSeg] = array_pad(array_map('intval', explode(':', (string)$edF->getDuracao())), 2, 0);
        }

        $edFDiscoCompositores = $edFDiscoMusicos = $edFDiscoCantores = [];
        if ($edFDisco) {
            $edFDiscoCompositores = $this->compositorDAO->listarPorCodigo($edFDisco);
            $edFDiscoMusicos = $this->musicoDAO->listarPorCodigo($edFDisco);
            $edFDiscoCantores = $this->cantorDAO->listarPorCodigo($edFDisco);
        }

        $fxCompositores = $fxMusicos = $fxCantores = [];
        if ($fx) {
            $fxCompositores = $this->compositorDAO->listarPorCodigo($fx->getCodigoDisco());
            $fxMusicos = $this->musicoDAO->listarPorCodigo($fx->getCodigoDisco());
            $fxCantores = $this->cantorDAO->listarPorCodigo($fx->getCodigoDisco());
        }

        return compact(
            'msg', 'det', 'ed', 'fx', 'edF', 'edFDisco', 'edFComp', 'edFMus', 'edFCant',
            'edFMin', 'edFSeg', 'edFDiscoCompositores', 'edFDiscoMusicos', 'edFDiscoCantores',
            'fxCompositores', 'fxMusicos', 'fxCantores', 'dGeneros', 'dFaixas',
            'dCompositores', 'dMusicos', 'dCantores', 'discos', 'todosGeneros', 'generosDoDisco', 'q'
        );
    }

    private function removerDisco($utilizador, $post)
    {
        if ($bloq = Permissao::bloquear($utilizador, 'remover')) {
            return ['msg' => $bloq];
        }

        $id = (int)($post['sel'] ?? 0);
        if ($id === 0) {
            return ['msg' => 'Seleccione um disco.'];
        }

        $discoRemovido = $this->discoDAO->buscarPorCodigo($id);   // antes de remover, para guardar o título no log
        $removido = $this->discoDAO->remover($id);
        if ($removido) {
            LogsController::registar('Eliminou o disco: ' . ($discoRemovido ? $discoRemovido->getTitulo() : 'ID ' . $id));
        }

        return ['msg' => $removido ? 'Disco removido.' : 'Erro ao remover o disco.'];
    }

    private function atualizarDisco($utilizador, $post)
    {
        if ($bloq = Permissao::bloquear($utilizador, 'editar')) {
            return ['msg' => $bloq];
        }

        $id = (int)$post['id'];
        $this->discoDAO->atualizar(new DiscoCompacto(
            $id,
            trim($post['titulo']),
            (float)$post['preco'],
            (int)substr($post['ano_edicao'], 0, 4),   // o calendário envia AAAA-MM-DD
            [], [], [], [], [], [], [], null, []
        ));

        // géneros: apaga as ligações antigas e grava as marcadas
        $this->generoDAO->removerRelacoesPorDisco($id);
        foreach (($post['generos'] ?? []) as $g) {
            $this->generoDAO->inserirRelacaoGeneroDisco($id, (int)$g);
        }

        return ['msg' => 'Disco atualizado.'];
    }

    private function adicionarFaixa($utilizador, $post)
    {
        if ($bloq = Permissao::bloquear($utilizador, 'adicionar')) {
            return ['msg' => $bloq];
        }

        $id  = (int)$post['id'];
        $dur = sprintf('%02d:%02d', (int)$post['min'], (int)$post['seg']);
        $num = count($this->faixaDAO->listarPorCodigo($id)) + 1;

        $idFaixa = $this->faixaDAO->inserir(
            new Faixa(trim($post['nome']), trim($post['artista']), $dur, $num),
            $id
        );

        if ($idFaixa > 0) {
            foreach (($post['compositores'] ?? []) as $c) $this->faixaDAO->InserRelacaoCompositor($idFaixa, (int)$c);
            foreach (($post['musicos'] ?? []) as $m)      $this->faixaDAO->InserRelacaoMusico($idFaixa, (int)$m);
            foreach (($post['cantores'] ?? []) as $c)     $this->faixaDAO->InserRelacaoCantor($idFaixa, (int)$c);
            return ['msg' => 'Faixa adicionada.'];
        }

        return ['msg' => 'Erro ao adicionar a faixa.'];
    }

    private function atualizarFaixa($utilizador, $post)
    {
        if ($bloq = Permissao::bloquear($utilizador, 'editar')) {
            return ['msg' => $bloq];
        }

        // Edita nome/artista/duração e sincroniza os participantes da faixa.
        $id = (int)$post['id'];
        $f  = (int)$post['id_faixa'];
        $dur = sprintf('%02d:%02d', (int)$post['min'], (int)$post['seg']);
        $this->faixaDAO->atualizar(
            new Faixa(trim($post['nome']), trim($post['artista']), $dur, (int)$post['num']),
            $f
        );

        $novosComp = array_map('intval', $post['compositores'] ?? []);
        $novosMus  = array_map('intval', $post['musicos'] ?? []);
        $novosCant = array_map('intval', $post['cantores'] ?? []);

        $atualComp = $atualMus = $atualCant = [];
        foreach ($this->faixaDAO->listarCompositoresPorFaixa($f) as $c) $atualComp[] = $c->getCodigoCompositor();
        foreach ($this->faixaDAO->listarMusicosPorFaixa($f) as $m)      $atualMus[]  = $m->getCodigoMusico();
        foreach ($this->faixaDAO->listarCantoresPorFaixa($f) as $c)     $atualCant[] = $c->getCodigoCantor();

        foreach (array_diff($atualComp, $novosComp) as $c) $this->faixaDAO->removerCompositor($f, $c);
        foreach (array_diff($atualMus, $novosMus) as $m)   $this->faixaDAO->removerMusico($f, $m);
        foreach (array_diff($atualCant, $novosCant) as $c) $this->faixaDAO->removerCantor($f, $c);
        foreach (array_diff($novosComp, $atualComp) as $c) $this->faixaDAO->InserRelacaoCompositor($f, $c);
        foreach (array_diff($novosMus, $atualMus) as $m)   $this->faixaDAO->InserRelacaoMusico($f, $m);
        foreach (array_diff($novosCant, $atualCant) as $c) $this->faixaDAO->InserRelacaoCantor($f, $c);

        return [
            'msg' => 'Faixa atualizada.',
            'det' => $this->discoDAO->buscarPorCodigo($id),
        ];
    }

    private function removerFaixa($utilizador, $post)
    {
        if ($bloq = Permissao::bloquear($utilizador, 'remover')) {
            return ['msg' => $bloq];
        }

        $id = (int)$post['id'];
        $f  = (int)($post['sel_faixa'] ?? 0);

        if ($f === 0) {
            return [
                'msg' => 'Seleccione uma faixa.',
                'det' => $this->discoDAO->buscarPorCodigo($id),
            ];
        }

        // o DAO não deixa remover faixas com participantes: tira-os primeiro
        foreach ($this->faixaDAO->listarCompositoresPorFaixa($f) as $c) $this->faixaDAO->removerCompositor($f, $c->getCodigoCompositor());
        foreach ($this->faixaDAO->listarMusicosPorFaixa($f) as $m)      $this->faixaDAO->removerMusico($f, $m->getCodigoMusico());
        foreach ($this->faixaDAO->listarCantoresPorFaixa($f) as $c)     $this->faixaDAO->removerCantor($f, $c->getCodigoCantor());
        $removida = $this->faixaDAO->remover($f);

        return [
            'msg' => $removida ? 'Faixa removida.' : 'Não foi possível remover a faixa.',
            'det' => $this->discoDAO->buscarPorCodigo($id),
        ];
    }
}
