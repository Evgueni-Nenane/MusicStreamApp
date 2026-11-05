<?php

require_once __DIR__ . '/../model/permissao.php';
require_once __DIR__ . '/../model/produtor.php';
require_once __DIR__ . '/../model/gravadora.php';
require_once __DIR__ . '/../model/editora.php';
require_once __DIR__ . '/produtorController.php';
require_once __DIR__ . '/gravadoraController.php';
require_once __DIR__ . '/editoraController.php';
require_once __DIR__ . '/logsController.php';

/**
 * Controller da página de produção.
 *
 * A página tem três abas (produtores, gravadoras, editoras), cada uma com o
 * mesmo conjunto de acções. Toda a validação, permissões e acesso aos dados
 * vive aqui; a view só recebe o estado pronto a renderizar.
 */
class ProducaoController
{
    private $produtorController;
    private $gravadoraController;
    private $editoraController;

    public function __construct()
    {
        $this->produtorController  = new ProdutorController();
        $this->gravadoraController = new GravadoraController();
        $this->editoraController   = new EditoraController();
    }

    /**
     * Executa a acção submetida e devolve a mensagem a apresentar.
     */
    public function processarPost($utilizador, $post)
    {
        $tipo = $post['tipo'] ?? '';
        $acao = $post['acao'] ?? '';

        $permissaoPedido = match ($acao) {
            'adicionar' => 'adicionar',
            'atualizar' => 'editar',
            'remover' => 'remover',
            default => '',
        };

        if ($permissaoPedido === '') {
            return 'Acção inválida.';
        }

        if ($bloqueio = Permissao::bloquear($utilizador, $permissaoPedido)) {
            return $bloqueio;
        }

        return match ($tipo) {
            'produtor' => $this->processarProdutor($acao, $post),
            'gravadora' => $this->processarGravadora($acao, $post),
            'editora' => $this->processarEditora($acao, $post),
            default => 'Tipo inválido.',
        };
    }

    /**
     * Prepara tudo o que a página precisa para ser desenhada.
     */
    public function prepararEcra($get)
    {
        $msg = $get['msg'] ?? '';
        $tab = 'prod';
        $produtorEdicao = $gravadoraEdicao = $editoraEdicao = null;

        if (($get['acao'] ?? '') === 'editar') {
            $sel = (int) ($get['sel'] ?? 0);
            $tipo = $get['tipo'] ?? '';

            if ($tipo === 'produtor') {
                $produtorEdicao = $this->produtorController->buscarPorCodigo($sel);
                $tab = 'prod';
            } elseif ($tipo === 'gravadora') {
                $gravadoraEdicao = $this->gravadoraController->buscarPorCodigo($sel);
                $tab = 'grav';
            } elseif ($tipo === 'editora') {
                $editoraEdicao = $this->editoraController->buscarPorCodigo($sel);
                $tab = 'edit';
            }
            if (!$produtorEdicao && !$gravadoraEdicao && !$editoraEdicao) {
                $msg = 'Seleccione um registo para editar.';
            }
        }

        $produtores = $this->produtorController->listarProdutor();
        $gravadoras = $this->gravadoraController->listarGravadoras();
        $editoras = $this->editoraController->listarEditoras();

        // Pesquisa simples (filtra em memória as 3 listas pelo texto introduzido)
        $termoPesquisa = trim($get['q'] ?? '');
        if ($termoPesquisa !== '') {
            $termoMinusculo = mb_strtolower($termoPesquisa);

            $produtores = array_values(array_filter($produtores, function ($produtor) use ($termoMinusculo) {
                $textoPesquisa = mb_strtolower(
                    $produtor->getCodigoProdutor() . ' ' . $produtor->getNomeCompleto() . ' ' .
                    $produtor->getContactoProdutor() . ' ' . $produtor->getEmailProdutor()
                );

                return str_contains($textoPesquisa, $termoMinusculo);
            }));

            $gravadoras = array_values(array_filter($gravadoras, function ($gravadora) use ($termoMinusculo) {
                $textoPesquisa = mb_strtolower(
                    $gravadora->getCodigoGravadora() . ' ' . $gravadora->getNomeGravadora() . ' ' .
                    $gravadora->getContactoGravadora() . ' ' . $gravadora->getEmailGravadora() . ' ' .
                    $gravadora->getEnderecoGravadora()
                );

                return str_contains($textoPesquisa, $termoMinusculo);
            }));

            $editoras = array_values(array_filter($editoras, function ($editora) use ($termoMinusculo) {
                $textoPesquisa = mb_strtolower(
                    $editora->getCodigoEditora() . ' ' . $editora->getNomeEditora() . ' ' .
                    $editora->getContactoEditora() . ' ' . $editora->getEmailEditora() . ' ' .
                    $editora->getEndereco()
                );

                return str_contains($textoPesquisa, $termoMinusculo);
            }));

            // Abre automaticamente a primeira aba que tenha resultados
            if (($get['acao'] ?? '') !== 'editar') {
                if ($produtores) {
                    $tab = 'prod';
                } elseif ($gravadoras) {
                    $tab = 'grav';
                } elseif ($editoras) {
                    $tab = 'edit';
                }
            }
        }

        return [
            'msg' => $msg,
            'tab' => $tab,
            'produtorEdicao' => $produtorEdicao,
            'gravadoraEdicao' => $gravadoraEdicao,
            'editoraEdicao' => $editoraEdicao,
            'termoPesquisa' => $termoPesquisa,
            'produtores' => $produtores,
            'gravadoras' => $gravadoras,
            'editoras' => $editoras,
        ];
    }

    private function processarProdutor($acao, $post)
    {
        if ($acao === 'adicionar') {
            $nome = trim($post['nome'] ?? '');
            $apelido = trim($post['apelido'] ?? '');
            $novo = $this->produtorController->cadastrarProdutor(new Produtor(
                null, $nome, $apelido,
                trim($post['contacto'] ?? ''), trim($post['email'] ?? '')
            ));
            if ($novo > 0) {
                LogsController::registar('Registou o produtor: ' . $nome . ' ' . $apelido);
            }
            return $novo > 0 ? 'Produtor adicionado.' : 'Erro ao adicionar produtor.';
        }

        if ($acao === 'atualizar') {
            $p = new Produtor(
                (int) $post['id'], trim($post['nome'] ?? ''), trim($post['apelido'] ?? ''),
                trim($post['contacto'] ?? ''), trim($post['email'] ?? '')
            );
            return $this->produtorController->atualizarProdutor($p) ? 'Produtor atualizado.' : 'Sem alterações.';
        }

        if ($acao === 'remover') {
            $id = (int) ($post['sel'] ?? 0);
            $removido = $id > 0 && $this->produtorController->removerProdutor($id);
            if ($removido) {
                LogsController::registar('Removeu o produtor ID ' . $id);
            }
            return $removido
                ? 'Produtor removido.'
                : 'Seleccione um produtor que não esteja em discos.';
        }

        return 'Acção inválida.';
    }

    private function processarGravadora($acao, $post)
    {
        if ($acao === 'adicionar') {
            $nome = trim($post['nome'] ?? '');
            $novo = $this->gravadoraController->cadastrarGravadora(new Gravadora(
                null, $nome, trim($post['contacto'] ?? ''),
                trim($post['endereco'] ?? ''), trim($post['email'] ?? '')
            ));
            if ($novo > 0) {
                LogsController::registar('Registou a gravadora: ' . $nome);
            }
            return $novo > 0 ? 'Gravadora adicionada.' : 'Erro ao adicionar gravadora.';
        }

        if ($acao === 'atualizar') {
            $g = new Gravadora(
                (int) $post['id'], trim($post['nome'] ?? ''), trim($post['contacto'] ?? ''),
                trim($post['endereco'] ?? ''), trim($post['email'] ?? '')
            );
            return $this->gravadoraController->atualizarGravadora($g) ? 'Gravadora atualizada.' : 'Sem alterações.';
        }

        if ($acao === 'remover') {
            $id = (int) ($post['sel'] ?? 0);
            $removido = $id > 0 && $this->gravadoraController->removerGravadora($id);
            if ($removido) {
                LogsController::registar('Removeu a gravadora ID ' . $id);
            }
            return $removido
                ? 'Gravadora removida.'
                : 'Seleccione uma gravadora que não esteja em discos.';
        }

        return 'Acção inválida.';
    }

    private function processarEditora($acao, $post)
    {
        if ($acao === 'adicionar') {
            $nome = trim($post['nome'] ?? '');
            $novo = $this->editoraController->cadastrarEditora(new Editora(
                null, $nome, trim($post['contacto'] ?? ''),
                trim($post['email'] ?? ''), trim($post['endereco'] ?? '')
            ));
            if ($novo > 0) {
                LogsController::registar('Registou a editora: ' . $nome);
            }
            return $novo > 0 ? 'Editora adicionada.' : 'Erro ao adicionar editora.';
        }

        if ($acao === 'atualizar') {
            $e = new Editora(
                (int) $post['id'], trim($post['nome'] ?? ''), trim($post['contacto'] ?? ''),
                trim($post['email'] ?? ''), trim($post['endereco'] ?? '')
            );
            return $this->editoraController->atualizarEditora($e) ? 'Editora atualizada.' : 'Sem alterações.';
        }

        if ($acao === 'remover') {
            $id = (int) ($post['sel'] ?? 0);
            $removido = $id > 0 && $this->editoraController->removerEditora($id);
            if ($removido) {
                LogsController::registar('Removeu a editora ID ' . $id);
            }
            return $removido
                ? 'Editora removida.'
                : 'Seleccione uma editora que não esteja em discos.';
        }

        return 'Acção inválida.';
    }
}
