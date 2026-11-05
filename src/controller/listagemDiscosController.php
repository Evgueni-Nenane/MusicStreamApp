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
     * Devolve ['msg' => ...] ou ['erro' => ...] e, quando aplicável,
     * ['discoDetalhe' => disco].
     */
    public function processarPost($utilizador, $post)
    {
        return match ($post['acao'] ?? '') {
            'remover' => $this->removerDisco($utilizador, $post),
            'atualizar' => $this->atualizarDisco($utilizador, $post),
            'addfaixa' => $this->adicionarFaixa($utilizador, $post),
            'atualizarfaixa' => $this->atualizarFaixa($utilizador, $post),
            'removerfaixa' => $this->removerFaixa($utilizador, $post),
            default => [],
        };
    }

    /**
     * Prepara tudo o que a página precisa para ser desenhada.
     */
    public function prepararEcra($utilizador, $get, $post = [])
    {
        $erro = '';
        $discoDetalhe = null;   // disco aberto em "Ver Detalhes"
        $faixaEdicao = null;    // faixa a editar dentro de "Ver Detalhes"
        $faixaDiscoId = 0;      // disco da faixa em edição
        $faixaCompositoresSelecionados = [];
        $faixaMusicosSelecionados = [];
        $faixaCantoresSelecionados = [];

        // A pesquisa chega por GET nos botões Editar/Detalhes e por POST nas restantes acções.
        $termoPesquisa = trim($get['q'] ?? $post['q'] ?? '');

        $discoEdicao = null;      // disco a editar
        $discoNovaFaixa = null;   // disco a que se vai juntar uma faixa
        $acaoGet = $get['acao'] ?? '';

        if ($acaoGet !== '') {
            $disco = $this->discoDAO->buscarPorCodigo((int) ($get['sel'] ?? 0));

            if ($acaoGet === 'editarfaixa') {
                // Editar Faixa: a faixa vem de sel_faixa; o disco vem de id (ou deduz-se da faixa).
                $faixaDiscoId = (int) ($get['id'] ?? 0);
                $faixaId = (int) ($get['sel_faixa'] ?? 0);

                if (!Permissao::pode($utilizador, 'editar')) {
                    $erro = 'Sem permissão para editar.';
                } elseif ($faixaId > 0) {
                    $faixaEdicao = $this->faixaDAO->listarFaixaPorCodigo($faixaId);

                    foreach ($this->faixaDAO->listarCompositoresPorFaixa($faixaId) as $compositor) {
                        $faixaCompositoresSelecionados[] = $compositor->getCodigoCompositor();
                    }

                    foreach ($this->faixaDAO->listarMusicosPorFaixa($faixaId) as $musico) {
                        $faixaMusicosSelecionados[] = $musico->getCodigoMusico();
                    }

                    foreach ($this->faixaDAO->listarCantoresPorFaixa($faixaId) as $cantor) {
                        $faixaCantoresSelecionados[] = $cantor->getCodigoCantor();
                    }
                }

                // Reabre os Detalhes no mesmo disco para dar contexto à edição.
                $discoDetalhe = $faixaDiscoId > 0
                    ? $this->discoDAO->buscarPorCodigo($faixaDiscoId)
                    : null;

                if (!$faixaEdicao && $erro === '') {
                    $erro = 'Seleccione uma faixa.';
                }
            } elseif (!$disco) {
                $erro = 'Seleccione um disco.';
            } elseif ($acaoGet === 'editar') {
                $discoEdicao = $disco;
            } elseif ($acaoGet === 'faixas') {
                $discoNovaFaixa = $disco;
            } elseif ($acaoGet === 'detalhes') {
                $discoDetalhe = $disco;
            }
        }

        $discos = $this->discoDAO->listarTodos();   // depois das acções, para vir actualizado
        $todosGeneros = $this->generoDAO->listarTodos();

        /* ---------- Pesquisa: filtra por título, género, preço ou ano ---------- */
        if ($termoPesquisa !== '') {
            $discos = array_values(array_filter($discos, function ($disco) use ($termoPesquisa) {
                $alvo = $disco->getTitulo() . ' ' . $disco->getGeneroMusicalTxt() . ' '
                    . number_format($disco->getPreco(), 2, ',', '.') . ' ' . $disco->getAnoEdicao();

                return mb_stripos($alvo, $termoPesquisa) !== false;
            }));
        }

        /* ---------- Dados derivados dos modais ---------- */
        $detalheDiscoId = 0;
        $detalheGeneros = '';
        $detalheFaixas = [];
        $detalheCompositores = [];
        $detalheMusicos = [];
        $detalheCantores = [];

        if ($discoDetalhe) {
            $detalheDiscoId = $discoDetalhe->getCodigoDisco();
            $detalheGeneros = implode(', ', array_map(
                fn($genero) => $genero->getNomeGenero(),
                $this->generoDAO->listarPorDisco($detalheDiscoId)
            ));
            $detalheFaixas = $this->faixaDAO->listarPorCodigo($detalheDiscoId);
            $detalheCompositores = $this->compositorDAO->listarPorCodigo($detalheDiscoId);
            $detalheMusicos = $this->musicoDAO->listarPorCodigo($detalheDiscoId);
            $detalheCantores = $this->cantorDAO->listarPorCodigo($detalheDiscoId);
        }

        $generosDoDisco = [];
        if ($discoEdicao) {
            foreach ($this->generoDAO->listarPorDisco($discoEdicao->getCodigoDisco()) as $genero) {
                $generosDoDisco[] = $genero->getCodigoGenero();
            }
        }

        // Duração "mm:ss" partida em dois campos.
        $faixaMinutos = 0;
        $faixaSegundos = 0;
        if ($faixaEdicao) {
            [$faixaMinutos, $faixaSegundos] = array_pad(
                array_map('intval', explode(':', (string) $faixaEdicao->getDuracao())),
                2,
                0
            );
        }

        $discoCompositoresDisponiveis = [];
        $discoMusicosDisponiveis = [];
        $discoCantoresDisponiveis = [];
        if ($faixaDiscoId) {
            $discoCompositoresDisponiveis = $this->compositorDAO->listarPorCodigo($faixaDiscoId);
            $discoMusicosDisponiveis = $this->musicoDAO->listarPorCodigo($faixaDiscoId);
            $discoCantoresDisponiveis = $this->cantorDAO->listarPorCodigo($faixaDiscoId);
        }

        $novaFaixaCompositores = [];
        $novaFaixaMusicos = [];
        $novaFaixaCantores = [];
        if ($discoNovaFaixa) {
            $novaFaixaCompositores = $this->compositorDAO->listarPorCodigo($discoNovaFaixa->getCodigoDisco());
            $novaFaixaMusicos = $this->musicoDAO->listarPorCodigo($discoNovaFaixa->getCodigoDisco());
            $novaFaixaCantores = $this->cantorDAO->listarPorCodigo($discoNovaFaixa->getCodigoDisco());
        }

        return [
            'msg' => '',
            'erro' => $erro,
            'discoDetalhe' => $discoDetalhe,
            'discoEdicao' => $discoEdicao,
            'discoNovaFaixa' => $discoNovaFaixa,
            'faixaEdicao' => $faixaEdicao,
            'faixaDiscoId' => $faixaDiscoId,
            'faixaCompositoresSelecionados' => $faixaCompositoresSelecionados,
            'faixaMusicosSelecionados' => $faixaMusicosSelecionados,
            'faixaCantoresSelecionados' => $faixaCantoresSelecionados,
            'faixaMinutos' => $faixaMinutos,
            'faixaSegundos' => $faixaSegundos,
            'discoCompositoresDisponiveis' => $discoCompositoresDisponiveis,
            'discoMusicosDisponiveis' => $discoMusicosDisponiveis,
            'discoCantoresDisponiveis' => $discoCantoresDisponiveis,
            'novaFaixaCompositores' => $novaFaixaCompositores,
            'novaFaixaMusicos' => $novaFaixaMusicos,
            'novaFaixaCantores' => $novaFaixaCantores,
            'detalheDiscoId' => $detalheDiscoId,
            'detalheGeneros' => $detalheGeneros,
            'detalheFaixas' => $detalheFaixas,
            'detalheCompositores' => $detalheCompositores,
            'detalheMusicos' => $detalheMusicos,
            'detalheCantores' => $detalheCantores,
            'discos' => $discos,
            'todosGeneros' => $todosGeneros,
            'generosDoDisco' => $generosDoDisco,
            'termoPesquisa' => $termoPesquisa,
        ];
    }

    private function removerDisco($utilizador, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'remover')) {
            return ['erro' => $bloqueio];
        }

        $id = (int) ($post['sel'] ?? 0);
        if ($id === 0) {
            return ['erro' => 'Seleccione um disco.'];
        }

        // Lê o disco antes de remover para guardar o título no log.
        $discoRemovido = $this->discoDAO->buscarPorCodigo($id);
        $removido = $this->discoDAO->remover($id);

        if ($removido) {
            $descricao = $discoRemovido ? $discoRemovido->getTitulo() : 'ID ' . $id;
            LogsController::registar('Eliminou o disco: ' . $descricao);
            return ['msg' => 'Disco removido.'];
        }

        return ['erro' => 'Erro ao remover o disco.'];
    }

    private function atualizarDisco($utilizador, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'editar')) {
            return ['erro' => $bloqueio];
        }

        $id = (int) $post['id'];
        $this->discoDAO->atualizar(new DiscoCompacto(
            $id,
            trim($post['titulo']),
            (float) $post['preco'],
            (int) substr($post['ano_edicao'], 0, 4),   // o calendário envia AAAA-MM-DD
            [], [], [], [], [], [], [], null, []
        ));

        // Géneros: apaga as ligações antigas e grava as marcadas.
        $this->generoDAO->removerRelacoesPorDisco($id);
        foreach (($post['generos'] ?? []) as $generoId) {
            $this->generoDAO->inserirRelacaoGeneroDisco($id, (int) $generoId);
        }

        return ['msg' => 'Disco atualizado.'];
    }

    private function adicionarFaixa($utilizador, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'adicionar')) {
            return ['erro' => $bloqueio];
        }

        $discoId = (int) $post['id'];
        $duracao = sprintf('%02d:%02d', (int) $post['min'], (int) $post['seg']);
        $numeroFaixa = count($this->faixaDAO->listarPorCodigo($discoId)) + 1;

        $faixaId = $this->faixaDAO->inserir(
            new Faixa(trim($post['nome']), trim($post['artista']), $duracao, $numeroFaixa),
            $discoId
        );

        if ($faixaId <= 0) {
            return ['erro' => 'Erro ao adicionar a faixa.'];
        }

        foreach (($post['compositores'] ?? []) as $compositorId) {
            $this->faixaDAO->inserirRelacaoCompositor($faixaId, (int) $compositorId);
        }

        foreach (($post['musicos'] ?? []) as $musicoId) {
            $this->faixaDAO->inserirRelacaoMusico($faixaId, (int) $musicoId);
        }

        foreach (($post['cantores'] ?? []) as $cantorId) {
            $this->faixaDAO->inserirRelacaoCantor($faixaId, (int) $cantorId);
        }

        return ['msg' => 'Faixa adicionada.'];
    }

    private function atualizarFaixa($utilizador, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'editar')) {
            return ['erro' => $bloqueio];
        }

        // Edita nome/artista/duração e sincroniza os participantes da faixa.
        $discoId = (int) $post['id'];
        $faixaId = (int) $post['id_faixa'];
        $duracao = sprintf('%02d:%02d', (int) $post['min'], (int) $post['seg']);

        $this->faixaDAO->atualizar(
            new Faixa(trim($post['nome']), trim($post['artista']), $duracao, (int) $post['num']),
            $faixaId
        );

        $this->sincronizarParticipantesDaFaixa($faixaId, $post);

        return [
            'msg' => 'Faixa atualizada.',
            'discoDetalhe' => $this->discoDAO->buscarPorCodigo($discoId),
        ];
    }

    private function removerFaixa($utilizador, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'remover')) {
            return ['erro' => $bloqueio];
        }

        $discoId = (int) $post['id'];
        $faixaId = (int) ($post['sel_faixa'] ?? 0);

        if ($faixaId === 0) {
            return [
                'erro' => 'Seleccione uma faixa.',
                'discoDetalhe' => $this->discoDAO->buscarPorCodigo($discoId),
            ];
        }

        // O DAO não deixa remover faixas com participantes: tira-os primeiro.
        $this->removerParticipantesDaFaixa($faixaId);
        $removida = $this->faixaDAO->remover($faixaId);

        if (!$removida) {
            return [
                'erro' => 'Não foi possível remover a faixa.',
                'discoDetalhe' => $this->discoDAO->buscarPorCodigo($discoId),
            ];
        }

        return [
            'msg' => 'Faixa removida.',
            'discoDetalhe' => $this->discoDAO->buscarPorCodigo($discoId),
        ];
    }

    /**
     * Mantém apenas os participantes marcados no formulário de edição da faixa.
     */
    private function sincronizarParticipantesDaFaixa($faixaId, $post)
    {
        $novosCompositores = array_map('intval', $post['compositores'] ?? []);
        $novosMusicos = array_map('intval', $post['musicos'] ?? []);
        $novosCantores = array_map('intval', $post['cantores'] ?? []);

        $compositoresAtuais = array_map(
            fn($compositor) => $compositor->getCodigoCompositor(),
            $this->faixaDAO->listarCompositoresPorFaixa($faixaId)
        );
        $musicosAtuais = array_map(
            fn($musico) => $musico->getCodigoMusico(),
            $this->faixaDAO->listarMusicosPorFaixa($faixaId)
        );
        $cantoresAtuais = array_map(
            fn($cantor) => $cantor->getCodigoCantor(),
            $this->faixaDAO->listarCantoresPorFaixa($faixaId)
        );

        foreach (array_diff($compositoresAtuais, $novosCompositores) as $compositorId) {
            $this->faixaDAO->removerCompositor($faixaId, $compositorId);
        }
        foreach (array_diff($musicosAtuais, $novosMusicos) as $musicoId) {
            $this->faixaDAO->removerMusico($faixaId, $musicoId);
        }
        foreach (array_diff($cantoresAtuais, $novosCantores) as $cantorId) {
            $this->faixaDAO->removerCantor($faixaId, $cantorId);
        }

        foreach (array_diff($novosCompositores, $compositoresAtuais) as $compositorId) {
            $this->faixaDAO->inserirRelacaoCompositor($faixaId, $compositorId);
        }
        foreach (array_diff($novosMusicos, $musicosAtuais) as $musicoId) {
            $this->faixaDAO->inserirRelacaoMusico($faixaId, $musicoId);
        }
        foreach (array_diff($novosCantores, $cantoresAtuais) as $cantorId) {
            $this->faixaDAO->inserirRelacaoCantor($faixaId, $cantorId);
        }
    }

    private function removerParticipantesDaFaixa($faixaId)
    {
        foreach ($this->faixaDAO->listarCompositoresPorFaixa($faixaId) as $compositor) {
            $this->faixaDAO->removerCompositor($faixaId, $compositor->getCodigoCompositor());
        }

        foreach ($this->faixaDAO->listarMusicosPorFaixa($faixaId) as $musico) {
            $this->faixaDAO->removerMusico($faixaId, $musico->getCodigoMusico());
        }

        foreach ($this->faixaDAO->listarCantoresPorFaixa($faixaId) as $cantor) {
            $this->faixaDAO->removerCantor($faixaId, $cantor->getCodigoCantor());
        }
    }
}
