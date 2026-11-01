<?php

require_once __DIR__ . '/../repository/logs.php';
require_once __DIR__ . '/../model/logs.php';
require_once __DIR__ . '/../model/sessao.php';

class LogsController {
    private $logsDAO;

    public function __construct()
    {
        $this->logsDAO = new LogsDAO();
    }

    public function inserirLog($log) {
        return $this->logsDAO->inserir($log);
    }

    public function listarLogs() {
        return $this->logsDAO->listarLogs();
    }

    // Lista já filtrada pela pesquisa em memória.
    public function listarComPesquisa($q)
    {
        $logs = $this->listarLogs();
        $q = trim($q);
        if ($q === '') return $logs;

        $qMin = strtolower($q);
        return array_filter($logs, function ($l) use ($qMin) {
            $alvo = strtolower(
                $l->getCodigo() . ' ' . $l->getNome() . ' ' . $l->getApelido() . ' ' .
                $l->getEmail() . ' ' . $l->getPerfil() . ' ' . $l->getAccao() . ' ' . $l->getDataHora()
            );
            return str_contains($alvo, $qMin);
        });
    }

    // Regista uma acção do utilizador que está logado.
    // Uso: LogsController::registar('Registou o disco ID 5');
    public static function registar($accao) {
        $u = Sessao::getUtilizadorLogado();
        if (!$u) return false;

        $log = new Logs(
            null,
            $u->getNome(),
            $u->getApelido(),
            $u->getPerfil()->getNome(),
            $u->getEmail(),
            $accao,
            date('Y-m-d H:i:s')
        );

        return (new LogsController())->inserirLog($log);
    }
}