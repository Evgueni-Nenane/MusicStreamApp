<?php

require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../model/musico.php';
require_once __DIR__ . '/../model/cantor.php';
require_once __DIR__ . '/../model/compositor.php';
require_once __DIR__ . '/musicoController.php';
require_once __DIR__ . '/cantorController.php';
require_once __DIR__ . '/compositorController.php';
require_once __DIR__ . '/instrumentoController.php';
require_once __DIR__ . '/logsController.php';

/**
 * Controller da página de artistas.
 *
 * Agrega os três tipos (músico, compositor, cantor) e concentra validação,
 * permissões, gravação, logs e preparação do estado da página.
 */
class ArtistasController
{
    private $musicoController;
    private $cantorController;
    private $compositorController;
    private $instrumentoController;

    private const ABAS = [
        'musico'     => 'musicos',
        'compositor' => 'compositores',
        'cantor'     => 'cantores',
    ];

    public function __construct()
    {
        $this->musicoController      = new MusicoController();
        $this->cantorController      = new CantorController();
        $this->compositorController  = new CompositorController();
        $this->instrumentoController = new InstrumentoController();
    }

    /**
     * Executa a acção submetida e devolve ['aba' => ..., 'msg' => ...].
     */
    public function processarPost($utilizador, $post)
    {
        $tipo = $post['tipo'] ?? '';

        $mensagem = match ($post['acao'] ?? '') {
            'cadastrar' => $this->cadastrar($utilizador, $tipo, $post),
            'atualizar' => $this->atualizar($utilizador, $tipo, $post),
            'remover' => $this->remover($utilizador, $tipo, $post),
            default => '',
        };

        return ['aba' => self::ABAS[$tipo] ?? 'musicos', 'msg' => $mensagem];
    }

    /**
     * Prepara tudo o que a página precisa para ser desenhada.
     */
    public function prepararEcra($get)
    {
        $msg = $get['msg'] ?? '';

        /* Aba visível */
        $tab = $get['tab'] ?? 'musicos';
        if (!in_array($tab, ['musicos', 'compositores', 'cantores'], true)) {
            $tab = 'musicos';
        }

        /* Botão Editar (GET): carrega o artista seleccionado */
        $artistaEdicao = null;
        $tipoArtistaEdicao = '';
        $instrumentosSelecionados = [];

        if (($get['acao'] ?? '') === 'editar') {
            $tipoArtistaEdicao = $get['tipo'] ?? '';
            // fica na aba onde o utilizador estava, mesmo que não tenha seleccionado ninguém
            $tab = self::ABAS[$tipoArtistaEdicao] ?? $tab;
            $artistaEdicaoId = (int)($get['sel_' . $tipoArtistaEdicao] ?? 0);

            if ($artistaEdicaoId === 0) { // compatibilidade com o formato antigo "tipo:id"
                [$tipoAntigo, $idAntigo] = array_pad(explode(':', $get['sel'] ?? ''), 2, '');
                if ($tipoArtistaEdicao === '' && $tipoAntigo !== '') $tipoArtistaEdicao = $tipoAntigo;
                $artistaEdicaoId = (int)$idAntigo;
            }

            if ($artistaEdicaoId > 0 && $tipoArtistaEdicao === 'musico') {
                $artistaEdicao = $this->musicoController->buscarPorCodigo($artistaEdicaoId);
                if ($artistaEdicao->getCodigoMusico()) {
                    foreach ($this->musicoController->buscarInstrumentosDoMusico($artistaEdicaoId) as $i) {
                        $instrumentosSelecionados[] = $i->getCodigo();
                    }
                    $tab = 'musicos';
                } else {
                    $artistaEdicao = null;
                }
            } elseif ($artistaEdicaoId > 0 && $tipoArtistaEdicao === 'compositor') {
                $artistaEdicao = $this->compositorController->buscarPorCodigo($artistaEdicaoId);
                if ($artistaEdicao->getCodigoCompositor()) $tab = 'compositores';
                else $artistaEdicao = null;
            } elseif ($artistaEdicaoId > 0 && $tipoArtistaEdicao === 'cantor') {
                $artistaEdicao = $this->cantorController->buscarPorCodigo($artistaEdicaoId);
                if ($artistaEdicao->getCodigoCantor()) $tab = 'cantores';
                else $artistaEdicao = null;
            } else {
                $tipoArtistaEdicao = '';
            }

            if (!$artistaEdicao && $msg === '') $msg = 'Seleccione um artista.';
        }

        $musicos      = $this->musicoController->listarMusicos();
        $cantores     = $this->cantorController->listarCantor();
        $compositores = $this->compositorController->listarCompositor();
        $instrumentos = $this->instrumentoController->listarInstrumentos();

        /* Pesquisa simples (filtra em memória as 3 listas) */
        $termoPesquisa = trim($get['q'] ?? '');
        if ($termoPesquisa !== '') {
            $termoMinusculo = mb_strtolower($termoPesquisa);

            $musicos = array_values(array_filter($musicos, function ($m) use ($termoMinusculo) {
                $nomesInstr = array_map(
                    fn($i) => $i->getNome(),
                    $this->musicoController->buscarInstrumentosDoMusico($m->getCodigoMusico())
                );
                $alvo = mb_strtolower(
                    $m->getCodigoMusico() . ' ' . $m->getNomeMusico() . ' ' . $m->getApelidoMusico() . ' ' .
                    implode(' ', $nomesInstr) . ' ' . $m->getEmailMusico() . ' ' . $m->getContactoMusico()
                );
                return str_contains($alvo, $termoMinusculo);
            }));

            $compositores = array_values(array_filter($compositores, function ($c) use ($termoMinusculo) {
                $alvo = mb_strtolower(
                    $c->getCodigoCompositor() . ' ' . $c->getNomeCompositor() . ' ' . $c->getApelidoCompositor() . ' ' .
                    $c->getEmailCompositor() . ' ' . $c->getContactoCompositor()
                );
                return str_contains($alvo, $termoMinusculo);
            }));

            $cantores = array_values(array_filter($cantores, function ($c) use ($termoMinusculo) {
                $alvo = mb_strtolower(
                    $c->getCodigoCantor() . ' ' . $c->getNomeCantor() . ' ' . $c->getApelidoCantor() . ' ' .
                    $c->getEmailCantor() . ' ' . $c->getContactoCantor()
                );
                return str_contains($alvo, $termoMinusculo);
            }));

            // Se a aba actual ficou sem resultados, abre a primeira que tenha
            if (($get['acao'] ?? '') !== 'editar') {
                $listasPorAba = ['musicos' => $musicos, 'compositores' => $compositores, 'cantores' => $cantores];
                if (empty($listasPorAba[$tab])) {
                    foreach ($listasPorAba as $nomeAba => $lista) {
                        if ($lista) { $tab = $nomeAba; break; }
                    }
                }
            }
        }

        /* Campos do modal de edição (normaliza os 3 tipos de artista) */
        $artistaEdicaoId = $artistaEdicaoNome = $artistaEdicaoApelido = $artistaEdicaoContacto = $artistaEdicaoEmail = '';
        if ($artistaEdicao) {
            if ($tipoArtistaEdicao === 'musico') {
                $artistaEdicaoId = $artistaEdicao->getCodigoMusico();
                $artistaEdicaoNome = $artistaEdicao->getNomeMusico();
                $artistaEdicaoApelido = $artistaEdicao->getApelidoMusico();
                $artistaEdicaoContacto = $artistaEdicao->getContactoMusico();
                $artistaEdicaoEmail = $artistaEdicao->getEmailMusico();
            } elseif ($tipoArtistaEdicao === 'compositor') {
                $artistaEdicaoId = $artistaEdicao->getCodigoCompositor();
                $artistaEdicaoNome = $artistaEdicao->getNomeCompositor();
                $artistaEdicaoApelido = $artistaEdicao->getApelidoCompositor();
                $artistaEdicaoContacto = $artistaEdicao->getContactoCompositor();
                $artistaEdicaoEmail = $artistaEdicao->getEmailCompositor();
            } else {
                $artistaEdicaoId = $artistaEdicao->getCodigoCantor();
                $artistaEdicaoNome = $artistaEdicao->getNomeCantor();
                $artistaEdicaoApelido = $artistaEdicao->getApelidoCantor();
                $artistaEdicaoContacto = $artistaEdicao->getContactoCantor();
                $artistaEdicaoEmail = $artistaEdicao->getEmailCantor();
            }
        }
        $artistaEdicaoTitulo = ['musico' => 'Músico', 'compositor' => 'Compositor', 'cantor' => 'Cantor'][$tipoArtistaEdicao] ?? 'Artista';

        return [
            'msg' => $msg,
            'tab' => $tab,
            'artistaEdicao' => $artistaEdicao,
            'tipoArtistaEdicao' => $tipoArtistaEdicao,
            'instrumentosSelecionados' => $instrumentosSelecionados,
            'artistaEdicaoId' => $artistaEdicaoId,
            'artistaEdicaoNome' => $artistaEdicaoNome,
            'artistaEdicaoApelido' => $artistaEdicaoApelido,
            'artistaEdicaoContacto' => $artistaEdicaoContacto,
            'artistaEdicaoEmail' => $artistaEdicaoEmail,
            'artistaEdicaoTitulo' => $artistaEdicaoTitulo,
            'musicos' => $musicos,
            'cantores' => $cantores,
            'compositores' => $compositores,
            'instrumentos' => $instrumentos,
            'termoPesquisa' => $termoPesquisa,
        ];
    }

    private function cadastrar($utilizador, $tipo, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'adicionar')) {
            return $bloqueio;
        }

        $nome     = trim($post['nome'] ?? '');
        $apelido  = trim($post['apelido'] ?? '');
        $contacto = trim($post['contacto'] ?? '');
        $email    = trim($post['email'] ?? '');

        if ($nome === '' || $apelido === '') {
            return 'Preencha pelo menos o nome e o apelido.';
        }

        $id = -1;
        if ($tipo === 'musico') {
            $id = $this->musicoController->cadastrarMusico(
                new Musico(0, $nome, $apelido, [], $contacto, $email)
            );
            if ($id > 0) {
                foreach (($post['instrumentos'] ?? []) as $cod) {
                    $this->instrumentoController->inserirRelacaoMusicoInstrumento($id, (int)$cod);
                }
            }
        } elseif ($tipo === 'cantor') {
            $id = $this->cantorController->cadastrarCantor(new Cantor(0, $nome, $apelido, $contacto, $email));
        } elseif ($tipo === 'compositor') {
            $id = $this->compositorController->cadastrarCompositor(new Compositor(0, $nome, $apelido, $contacto, $email));
        } else {
            return 'Escolha o tipo de artista.';
        }

        if ($id > 0) {
            LogsController::registar('Registou o artista (' . $tipo . '): ' . $nome . ' ' . $apelido);
            return 'Artista cadastrado.';
        }
        return 'Erro ao cadastrar.';
    }

    private function atualizar($utilizador, $tipo, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'editar')) {
            return $bloqueio;
        }

        $id       = (int)($post['id'] ?? 0);
        $nome     = trim($post['nome'] ?? '');
        $apelido  = trim($post['apelido'] ?? '');
        $contacto = trim($post['contacto'] ?? '');
        $email    = trim($post['email'] ?? '');

        if ($id === 0 || $nome === '' || $apelido === '') {
            return 'Dados inválidos para atualizar.';
        }

        if ($tipo === 'musico') {
            $ok = $this->musicoController->atualizarMusico(
                new Musico($id, $nome, $apelido, [], $contacto, $email)
            );
            $this->instrumentoController->removerRelacoesPorMusico($id);
            foreach (($post['instrumentos'] ?? []) as $cod) {
                $this->instrumentoController->inserirRelacaoMusicoInstrumento($id, (int)$cod);
            }
            return $ok ? 'Músico atualizado.' : 'Sem alterações.';
        }

        if ($tipo === 'cantor') {
            $ok = $this->cantorController->atualizarCantor(new Cantor($id, $nome, $apelido, $contacto, $email));
            return $ok ? 'Cantor atualizado.' : 'Sem alterações.';
        }

        if ($tipo === 'compositor') {
            $ok = $this->compositorController->atualizarCompositor(new Compositor($id, $nome, $apelido, $contacto, $email));
            return $ok ? 'Compositor atualizado.' : 'Sem alterações.';
        }

        return 'Dados inválidos para atualizar.';
    }

    private function remover($utilizador, $tipo, $post)
    {
        if ($bloqueio = Permissao::bloquear($utilizador, 'remover')) {
            return $bloqueio;
        }

        // Cada aba selecciona pelo seu próprio name; mantém-se o formato antigo "tipo:id".
        $id = (int)($post['sel_' . $tipo] ?? 0);
        if ($id === 0) {
            [$tipoAntigo, $idAntigo] = array_pad(explode(':', $post['sel'] ?? ''), 2, '');
            if ($tipo === '' && $tipoAntigo !== '') $tipo = $tipoAntigo;
            $id = (int)$idAntigo;
        }

        if ($tipo === 'musico')          $ok = $id > 0 && $this->musicoController->removerMusico($id);
        elseif ($tipo === 'cantor')      $ok = $id > 0 && $this->cantorController->removerCantor($id);
        elseif ($tipo === 'compositor')  $ok = $id > 0 && $this->compositorController->removerCompositor($id);
        else                             $ok = false;

        if ($ok) {
            LogsController::registar('Removeu o artista (' . $tipo . ') ID ' . $id);
        }

        return $ok ? 'Artista removido.' : 'Não foi possível remover (seleccione um artista que não esteja em discos).';
    }
}
