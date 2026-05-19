<?php

use \Hcode\DB\Sql;
use \Hcode\PageAdmin;
use \Hcode\Model\Funcionarios;

$app->get('/admin/dashboard/geral', function () {
    Funcionarios::checkPermission('DASHBOARD_VIEW');

    $page = new PageAdmin();
    $page->setTpl('admin/dashboard-geral');
});

$app->get('/admin/api/dashboard/geral', function () {
    header('Content-Type: application/json; charset=utf-8');

    try {
        Funcionarios::checkPermission('DASHBOARD_VIEW');

        $sql = new Sql();

        $titulares = $sql->select("SELECT COUNT(*) AS total FROM tb_titular");
        $dependentes = $sql->select("SELECT COUNT(*) AS total FROM tb_dependentes");
        $pdfs = $sql->select("SELECT COUNT(*) AS total FROM tb_relatorios_pdf");
        $pdfsStatus = $sql->select("
            SELECT status_upload, COUNT(*) AS total
            FROM tb_relatorios_pdf
            GROUP BY status_upload
        ");

        $hoje = date('Y-m-d');

        $relHoje = $sql->select("
            SELECT 
                COALESCE(Total_pessoas_atendidas,0) AS atendimentos_hoje,
                COALESCE(qtd_refeicoes_servidas,0) AS refeicoes_hoje
            FROM tb_relatorios
            WHERE data = :data
            LIMIT 1
        ", array(':data' => $hoje));

        $ultimoBackup = function_exists('getUltimoBackup') ? getUltimoBackup() : null;

        $logsBackup = array();
        if (function_exists('getBackupNotifications')) {
            $logsBackup = getBackupNotifications(10);
        }

        $remoteDbOk = false;
        $remoteDbMessage = 'Banco remoto não testado.';
        if (function_exists('getPdoRelatorioRemoto')) {
            try {
                $pdo = getPdoRelatorioRemoto();
                if ($pdo instanceof PDO) {
                    $pdo->query('SELECT 1');
                    $remoteDbOk = true;
                    $remoteDbMessage = 'Conexão remota validada com sucesso.';
                } else {
                    $remoteDbMessage = 'Banco remoto desativado na configuração.';
                }
            } catch (\Exception $e) {
                $remoteDbMessage = $e->getMessage();
            }
        }

        $uploadsOk = 0;
        $uploadsErro = 0;
        foreach ($pdfsStatus as $row) {
            if (strtoupper((string)$row['status_upload']) === 'SUCESSO') {
                $uploadsOk = (int)$row['total'];
            }
            if (strtoupper((string)$row['status_upload']) === 'ERRO') {
                $uploadsErro = (int)$row['total'];
            }
        }

        $recentesPdfs = $sql->select("
            SELECT id, nome_arquivo, status_upload, responsavel, data_geracao
            FROM tb_relatorios_pdf
            ORDER BY id DESC
            LIMIT 5
        ");

        $recentesBackups = array();
        $backupsEnvio = 0;
        $backupsErro = 0;

        foreach ($logsBackup as $item) {
            $mensagem = isset($item['mensagem']) ? (string)$item['mensagem'] : '';
            $status = stripos($mensagem, 'erro') !== false ? 'ERRO' : 'SUCESSO';

            if (stripos($mensagem, 'envio') !== false || stripos($mensagem, 'upload') !== false) {
                $backupsEnvio++;
            }
            if ($status === 'ERRO') {
                $backupsErro++;
            }

            $recentesBackups[] = array(
                'contexto' => isset($item['contexto']) ? $item['contexto'] : 'backup',
                'status' => $status,
                'data_execucao' => isset($item['data']) ? $item['data'] : null,
                'mensagem' => $mensagem
            );
        }

        echo json_encode(array(
            'success' => true,
            'total_titulares' => isset($titulares[0]['total']) ? (int)$titulares[0]['total'] : 0,
            'total_dependentes' => isset($dependentes[0]['total']) ? (int)$dependentes[0]['total'] : 0,
            'total_pdfs' => isset($pdfs[0]['total']) ? (int)$pdfs[0]['total'] : 0,
            'atendimentos_hoje' => isset($relHoje[0]['atendimentos_hoje']) ? (int)$relHoje[0]['atendimentos_hoje'] : 0,
            'refeicoes_hoje' => isset($relHoje[0]['refeicoes_hoje']) ? (int)$relHoje[0]['refeicoes_hoje'] : 0,
            'uploads_ok' => $uploadsOk,
            'uploads_erro' => $uploadsErro,
            'ultimo_backup' => $ultimoBackup,
            'recentes_pdfs' => $recentesPdfs,
            'recentes_backups' => $recentesBackups,
            'remote_db_ok' => $remoteDbOk,
            'remote_db_message' => $remoteDbMessage,
            'backup_send_enabled' => function_exists('backupExecutarComEnvio'),
            'backups_envio' => $backupsEnvio,
            'backups_erro' => $backupsErro
        ));
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => $e->getMessage()
        ));
        exit;
    }
});

$app->post('/admin/api/backup/run-and-send', function () {
    header('Content-Type: application/json; charset=utf-8');

    try {
        Funcionarios::checkPermission('BACKUP_RUN');

        if (!function_exists('backupExecutarComEnvio')) {
            throw new Exception('Função backupExecutarComEnvio() não encontrada.');
        }

        $resultado = backupExecutarComEnvio(true);

        echo json_encode(array(
            'success' => !empty($resultado['success']),
            'message' => isset($resultado['message']) ? $resultado['message'] : 'Backup executado.',
            'data' => $resultado
        ));
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => $e->getMessage(),
            'message' => $e->getMessage()
        ));
        exit;
    }
});
