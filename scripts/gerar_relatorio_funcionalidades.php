<?php

require_once __DIR__ . '/../config/paths.php';
require_once ROOT_DIR . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$outputDir = ROOT_DIR . '/storage/reports';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0775, true);
}

$generatedAt = date('d/m/Y H:i:s');
$pdfPath = $outputDir . '/relatorio-funcionalidades-prato-web.pdf';
$htmlPath = $outputDir . '/relatorio-funcionalidades-prato-web.html';

$sections = [
    [
        'title' => '1. Visao Geral',
        'body' => [
            'O prato_web e um sistema administrativo para gestao operacional do programa Prato Cheio. O sistema organiza usuarios internos, titulares/clientes, familias, dependentes, liberacao/venda de senhas de refeicao, controle de limite diario, fechamento de expediente, relatorios em PDF e planilha, backup, auditoria, permissoes e monitoramento operacional.',
            'A aplicacao usa PHP com Slim 2, RainTPL, Composer, MySQL/MariaDB, Dompdf para PDF, PhpSpreadsheet para planilhas e phpseclib para SFTP. A entrada principal passa por public/index.php, carrega config/paths.php, vendor/autoload.php, helpers e rotas administrativas.'
        ]
    ],
    [
        'title' => '2. Login, Sessao e Recuperacao de Senha',
        'body' => [
            'O acesso administrativo acontece em /admin/login. O formulario solicita CPF e senha. O CPF e normalizado para somente digitos antes da autenticacao.',
            'Ha limite de tentativas de login na sessao: apos 5 tentativas invalidas o sistema bloqueia novas tentativas temporariamente na propria sessao e informa o usuario.',
            'Ao autenticar, os dados do funcionario/usuario sao gravados na sessao, as permissoes sao carregadas e o acesso e registrado em auditoria. Durante o login tambem pode ser disparado backupAutomatico(false, 86400, "login"), respeitando lock/cooldown.',
            'O logout registra a saida do usuario e limpa a sessao.',
            'A recuperacao de senha possui tela para solicitar link por CPF e tela para redefinir senha via codigo. Os codigos sao gravados em tb_usuarios_passwords_recoveries e validados antes de permitir a troca.'
        ]
    ],
    [
        'title' => '3. Seguranca, Perfis, Permissoes e Auditoria',
        'body' => [
            'O PerfilMiddleware protege as rotas administrativas. Rotas publicas ficam restritas a login e recuperacao de senha; as demais exigem sessao ativa.',
            'A autorizacao e baseada em permissoes como DASHBOARD_VIEW, FUNCIONARIOS_VIEW, CLIENTES_CREATE, DEPENDENTES_UPDATE, VENDAS_VIEW, RELATORIOS_VIEW, BACKUP_RUN, ACL_PROFILES_MANAGE, AUDITORIA_VIEW e outras.',
            'Os perfis padrao encontrados sao ADMIN, SUPERVISOR e ASSESSOR. ADMIN recebe todas as permissoes; SUPERVISOR possui permissao ampla de operacao; ASSESSOR possui acesso mais voltado a consulta, vendas e relatorios.',
            'Tentativas de acesso negado sao salvas em tb_access_denied com perfil, rota, IP, user agent e data. Existe tela para consultar acessos negados.',
            'A auditoria de acoes fica em tb_userlogs e registra eventos como LOGIN, LOGOUT, cadastro/edicao/exclusao de titulares, venda de senhas, alteracao de permissoes e configuracoes. A tela de auditoria permite filtrar por periodo, modulo e busca textual.',
            'Ha tela de seguranca de usuarios para ativar/desativar usuario e funcionario, alem da gestao de permissoes por perfil.'
        ]
    ],
    [
        'title' => '4. Dashboard e Indicadores',
        'body' => [
            'O painel principal mostra totais de titulares, dependentes e familias. Tambem ha API para graficos e contadores simples usados na tela inicial.',
            'O dashboard geral consolida indicadores operacionais: total de titulares, dependentes, PDFs gerados, atendimentos/refeicoes do dia, status de uploads, ultimo backup, logs recentes de backup, conexao com banco remoto, comparativos mensal/anual e ranking de titulares por refeicoes ou frequencia.',
            'O dashboard tambem exibe saude operacional: falhas de upload, falhas de backup e estado do banco remoto quando configurado.'
        ]
    ],
    [
        'title' => '5. Funcionarios e Usuarios Internos',
        'body' => [
            'O modulo de funcionarios permite listar, cadastrar, editar, inativar/excluir e alterar senha de usuarios internos.',
            'No cadastro e edicao ha validacao de CPF, telefone, e-mail e duplicidade de CPF/e-mail/telefone. CPF e telefone sao normalizados para evitar inconsistencias.',
            'O cadastro salva dados pessoais em tb_funcionario e dados de acesso em tb_usuario, incluindo CPF, senha hash, perfil, indicador de administrador e ativo.',
            'A troca de senha exige senha preenchida, confirmacao e minimo de 6 caracteres.',
            'A exclusao usa procedure de soft delete/inativacao quando disponivel, preservando rastreabilidade operacional.'
        ]
    ],
    [
        'title' => '6. Clientes, Titulares e Criacao de Familia',
        'body' => [
            'O modulo de clientes gerencia titulares. O cadastro completo exige nome, cor/etnia, sexo, estado civil, nascimento, CPF e CEP. Tambem valida que o titular seja maior de idade.',
            'Ao cadastrar um titular, o sistema cria/relaciona endereco, familia e titular usando a procedure sp_cadastrar_titular_familia_endereco. A familia pode receber nome informado; se nao vier, o sistema gera automaticamente "Familia" com base no nome do titular.',
            'Os dados de titular incluem nome completo, nome social, cor/etnia, nome da mae, telefone, data de nascimento, idade, genero, estado civil, RG, CPF, NIS e status do cliente.',
            'Os dados de endereco incluem CEP, bairro, rua, numero, referencia, nacionalidade, naturalidade, cidade e tempo de moradia.',
            'A edicao atualiza titular, familia e endereco pela procedure sp_atualizar_titular_familia_endereco. A exclusao chama sp_excluir_titular_dependentes_endereco, removendo titular, dependentes e endereco conforme as regras do banco.',
            'Ha consultas AJAX para verificar duplicidade de CPF, RG, NIS e telefone antes de salvar.',
            'Existe um fluxo especial para "pessoa em situacao de rua": cadastro reduzido com nome/apelido, sexo e idade, status fixo "PESSOA EM SITUACAO DE RUA" e criacao de familia simplificada.'
        ]
    ],
    [
        'title' => '7. Dependentes',
        'body' => [
            'O modulo de dependentes permite criar dependentes vinculados a um titular/familia, listar via AJAX, buscar um dependente especifico, editar e excluir.',
            'A criacao em lote recebe JSON com id_titular e lista de dependentes. Para cada dependente grava nome, idade, data de nascimento quando valida, genero, CPF, RG, familia, titular e grau/descricao de dependencia.',
            'A idade pode ser calculada automaticamente pela data de nascimento. Quando nao ha data valida, o sistema usa a idade informada como fallback.',
            'A edicao atualiza nome, idade, data de nascimento, genero, CPF, RG e dependencia_cliente. A exclusao remove o dependente e registra auditoria.',
            'Dependentes entram no fluxo de vendas: podem receber senha/refeicao individualmente e possuem regra de duplicidade propria para nao comprar duas vezes no mesmo dia.'
        ]
    ],
    [
        'title' => '8. Socioeconomico e Saude',
        'body' => [
            'O sistema possui cadastro socioeconomico vinculado a titulares. A tela lista titulares com registro e permite criar, editar e excluir informacoes socioeconomicas.',
            'Os campos encontrados na view incluem renda mensal familiar, beneficios, composicao familiar, situacao de moradia e outros dados sociais usados para acompanhamento.',
            'Ha tambem modulo socioeconomico de saude, com registros vinculados a titulares e dependentes. Ele permite criar, editar, listar e excluir informacoes de saude.',
            'Esses modulos usam permissoes especificas de visualizacao, criacao, atualizacao e exclusao, separadas das permissoes de clientes.'
        ]
    ],
    [
        'title' => '9. Venda/Liberacao de Senhas de Refeicao',
        'body' => [
            'A tela /admin/vendas e o centro operacional de liberacao de senhas. Ela pesquisa titulares, carrega dependentes, consulta contagem do dia, verifica se uma pessoa ja comprou e registra novas senhas.',
            'O sistema trabalha com tipos de senha, incluindo senha GENERICA e senhas vinculadas a titular/dependente. Para senhas nao genericas, a regra impede duplicidade por dia.',
            'A duplicidade e tratada por id_dependente para dependentes e por id_titular ou CPF para titulares. Tambem existe coluna/indice dedupe_key em tb_senhas para reforco de unicidade no banco.',
            'A venda e transacional: antes de inserir, o sistema cria ou atualiza o registro de tb_fechamento_dia, bloqueia a linha com FOR UPDATE, calcula total atual, compara com o limite e cancela se ultrapassar.',
            'Cada item vendido gera registro em tb_senhas com cliente, CPF, idade, genero, deficiente, tipoSenha, status_cliente, data_refeicao, id_titular e id_dependente quando houver.',
            'A numeracao exibida da senha e calculada pela posicao do atendimento no dia. Ao final, a venda registra auditoria com data, tipo, quantidade, numeros e IDs criados.',
            'Se o limite diario for atingido, o dia passa para fechado e o sistema tenta gerar o resumo do dia em tb_relatorios.'
        ]
    ],
    [
        'title' => '10. Expediente, Limite Diario e Fechamento do Dia',
        'body' => [
            'O limite diario de senhas/refeicoes vem das configuracoes do sistema (limite_senhas_dia) com fallback em LIMITE_SENHAS_DIA. A configuracao fica em storage/config/system-settings.php e pode ser alterada por usuarios autorizados.',
            'A tabela tb_fechamento_dia guarda data_refeicao, limite, total, fechado, fechado_em e atualizado_em. Ela representa o expediente diario: aberto enquanto ha capacidade e fechado quando atinge limite ou quando um usuario fecha manualmente.',
            'A contagem do dia usa tb_senhas por data_refeicao. A API de contagem retorna total, limite, restante e status de fechamento.',
            'O fechamento manual recebe data, quantidade de refeicoes servidas, ocorrencias e cardapio. Ele salva informacoes de fechamento, atualiza/gera tb_relatorios, marca tb_fechamento_dia como fechado e dispara backupAutomatico(true, 86400, "fechamento_dia").',
            'O fechamento automatico ocorre durante vendas quando total + quantidade solicitada atinge o limite ou quando a procedure sp_fechamento_atualizar atualiza a situacao. Ao fechar, o sistema tenta gerar o relatorio do dia.'
        ]
    ],
    [
        'title' => '11. Relatorios Operacionais de Senhas',
        'body' => [
            'O modulo /admin/relatorio/senhas fornece resumo, lista detalhada, top 10, consolidado mensal e exportacao.',
            'Os relatorios usam dados de tb_senhas e tb_relatorios para mostrar atendimentos por data, tipo de senha, status de cliente, perfil etario, genero, PCD, pessoas em situacao de rua e totais.',
            'O resumo de fechamento calcula refeicoes ofertadas, senhas vendidas, refeicoes servidas, sobras, cardapio, ocorrencias e status fechado.',
            'O top 10 ranqueia titulares por total de refeicoes ou frequencia em periodo mensal/anual.',
            'A exportacao de planilha usa PhpSpreadsheet e modelos de planilha para consolidar os dados mensais.'
        ]
    ],
    [
        'title' => '12. Relatorio PDF de Fechamento e Envio Remoto',
        'body' => [
            'A rota /admin/api/relatorio/pdf gera PDF do fechamento do dia usando Dompdf. O PDF contem resumo operacional, faixa etaria, PCD, situacao de rua, cardapio e ocorrencias.',
            'Quando chamado sem upload, o PDF e exibido inline no navegador. Quando chamado com upload=1, o arquivo e salvo temporariamente, enviado ao destino remoto e registrado no historico.',
            'O envio remoto usa configuracoes de app/config/relatorio-upload.php, alimentadas por variaveis RELATORIO_FTP_*, RELATORIO_SFTP_* e RELATORIO_PUBLIC_BASE_URL. O sistema tenta registrar historico local em tb_relatorios_pdf e tambem em banco remoto se RELATORIO_REMOTE_DB_ENABLED estiver ativo.',
            'O historico de PDFs permite filtrar por data e status de upload, e guarda nome do arquivo, URL publica, caminho remoto, status, mensagem de erro, responsavel, CPF do responsavel, data de geracao e data de upload.',
            'Em caso de erro na geracao ou upload, o sistema registra o erro no historico para rastreabilidade.'
        ]
    ],
    [
        'title' => '13. Planilhas Mensais',
        'body' => [
            'O sistema possui RelatorioPlanilhaMensalExporter e RelatorioPlanilhaDashboard para gerar planilhas mensais a partir de tb_relatorios.',
            'O exporter localiza unidades na planilha, mapeia campos, escreve dados por dia do mes e pode considerar somente dias fechados.',
            'O dashboard de planilhas mostra painel mensal, historico de geracoes e localiza arquivos gerados. A permissao PLANILHA_EXPORT aparece nos scripts SQL de upgrade para ADMIN e SUPERVISOR.',
            'A tabela tb_relatorios_pdf e reaproveitada como historico de arquivos gerados, incluindo planilhas, conforme comentario do upgrade portal_planilha_upgrade.sql.'
        ]
    ],
    [
        'title' => '14. Backup Automatico e Manual',
        'body' => [
            'O backup e executado por backupAutomatico, com lock e cooldown para evitar execucoes repetidas. O log fica em storage/backup/backup_notifications.log.',
            'O BackupService gera dump SQL do banco atual usando mysqldump/mariadb-dump, salva em BACKUP_DIR e usa as credenciais centralizadas de Hcode\\DB\\Sql.',
            'O UploadService envia o backup por HTTP quando BACKUP_UPLOAD_URL/TOKEN estao configurados ou por SFTP quando BACKUP_SFTP_* esta configurado. O arquivo enviado recebe marcador .sent.',
            'O sistema registra backup no banco remoto em tb_backups quando o banco remoto esta configurado. O registro inclui nome do banco, arquivo, status de upload, caminho remoto, hash e datas.',
            'O backup pode ser disparado no login, no fechamento do dia e manualmente em /admin/backup/run ou /admin/api/backup/run-and-send.'
        ]
    ],
    [
        'title' => '15. Notificacoes',
        'body' => [
            'O sistema possui modulo de notificacoes em sessao, com tela de listagem, totalizador, API para buscar notificacoes, acao para limpar e rota de teste.',
            'A limpeza de notificacoes registra auditoria. O dashboard usa notificacoes e logs para indicar eventos recentes e falhas operacionais.'
        ]
    ],
    [
        'title' => '16. Consulta Unificada entre Bases',
        'body' => [
            'O CadastroUnificadoService permite configurar bases remotas em tb_bases_consulta, sincronizar titulares e dependentes de outras bases e pesquisar tudo em tb_cadastros_unificados.',
            'A sincronizacao detecta existencia de tabelas/colunas remotas, normaliza CPF, datas, titular/dependente, origem do banco e unidade, e faz upsert local.',
            'O servico registra status de sincronizacao por base e logs em tb_bases_consulta_sync_log. A pesquisa possui filtros por texto, tipo de cadastro, origem, unidade e ativos.'
        ]
    ],
    [
        'title' => '17. Configuracoes do Sistema e Atualizador',
        'body' => [
            'A tela de configuracoes permite ajustar nome do programa e limite diario de senhas/refeicoes. As mudancas sao auditadas.',
            'O projeto possui atualizador proprio em updater/update.php, com checagem de version.json remoto, download de ZIP, validacao de SHA256, backup do sistema atual, extracao segura e gravacao de updater/version-local.json.',
            'O build de pacote fica em updater/build-update-package.php. Ele exclui arquivos sensiveis como .env, storage, logs, vendor e caches no pacote padrao.'
        ]
    ],
    [
        'title' => '18. Principais Tabelas e Procedures',
        'body' => [
            'Tabelas principais: tb_titular, tb_dependentes, tb_familia, tb_endereco, tb_senhas, tb_fechamento_dia, tb_relatorios, tb_relatorios_pdf, tb_funcionario, tb_usuario, tb_permissions, tb_profile_permissions, tb_access_denied, tb_userlogs, tb_usuarios_passwords_recoveries, tb_backups, tb_bases_consulta, tb_cadastros_unificados e tb_bases_consulta_sync_log.',
            'Procedures relevantes: sp_cadastrar_titular_familia_endereco, sp_atualizar_titular_familia_endereco, sp_excluir_titular_dependentes_endereco, sp_cadastrar_dependente, sp_fechamento_atualizar, sp_gerar_relatorio_dia, sp_tb_senhas_count_by_date, sp_tb_senhas_save, sp_funcionario_usuario_save, sp_funcionario_usuario_update e procedures de exclusao/inativacao de funcionario/usuario.'
        ]
    ],
    [
        'title' => '19. Observacoes Tecnicas Encontradas',
        'body' => [
            'Ha duplicidade historica de rotas entre app/routes/admin.php, app/routes/admin-vendas.php, app/routes/admin-relatorio.php e dashboard-geral-routes.php. Na pratica, a ordem de require em public/index.php define qual rota responde primeiro quando ha caminhos repetidos.',
            'Alguns trechos antigos permanecem comentados ou apos return, indicando evolucao incremental do sistema. Isso nao impede a operacao, mas aumenta custo de manutencao.',
            'O modulo de backup havia dependido de Hcode\\Backup\\BackupService e Hcode\\Backup\\UploadService. As classes existem agora em vendor/API/php-classes/src/Backup, no caminho compativel com PSR-4 em Linux.',
            'Arquivos .env, backups, logs e cache devem continuar fora dos pacotes publicos de atualizacao por conterem informacoes sensiveis ou dados operacionais.'
        ]
    ],
    [
        'title' => '20. Fontes Locais Analisadas',
        'body' => [
            'Rotas: app/routes/admin.php, admin-clientes.php, admin-dependentes.php, admin-funcionarios.php, admin-vendas.php, admin-relatorio.php, admin-socio-economico.php e dashboard-geral-routes.php.',
            'Modelos e servicos: vendor/API/php-classes/src/Model/Funcionarios.php, Clientes.php, Dependente.php, Senhas.php, Permissions.php, SocioEconomico.php, SocioEconomicoSaude.php, app/services/CadastroUnificadoService.php.',
            'Helpers: app/helpers/functions.php, backup-envio-helper.php, RelatorioPlanilhaDashboard.php, RelatorioPlanilhaMensalExporter.php.',
            'Banco e infraestrutura: database/sql/*.sql, config/paths.php, config/env.php, app/config/relatorio-upload.php, app/config/relatorio-db-remoto.php, updater/update.php e updater/build-update-package.php.'
        ]
    ],
];

$toc = '';
$content = '';
foreach ($sections as $section) {
    $id = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $section['title']));
    $toc .= '<li><a href="#' . $id . '">' . htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') . '</a></li>';
    $content .= '<section id="' . $id . '"><h2>' . htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') . '</h2>';
    foreach ($section['body'] as $paragraph) {
        $content .= '<p>' . htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $content .= '</section>';
}

$html = '<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Relatorio de Funcionalidades - prato_web</title>
<style>
    @page { margin: 28px 34px; }
    body { font-family: DejaVu Sans, sans-serif; color: #1f2933; font-size: 11.5px; line-height: 1.48; }
    h1 { color: #0f3d5e; font-size: 25px; margin: 0 0 6px; }
    h2 { color: #174a6b; font-size: 16px; margin: 22px 0 8px; border-bottom: 1px solid #d8e2ea; padding-bottom: 4px; }
    p { margin: 0 0 7px; text-align: justify; }
    .cover { border-bottom: 3px solid #0f3d5e; padding-bottom: 14px; margin-bottom: 18px; }
    .meta { color: #52616f; font-size: 10.5px; }
    .badge { display: inline-block; background: #e8f2f8; border: 1px solid #c7ddeb; color: #174a6b; padding: 3px 7px; border-radius: 4px; margin: 3px 4px 3px 0; }
    .toc { background: #f6f8fa; border: 1px solid #d8e2ea; padding: 10px 14px; margin: 12px 0 18px; }
    .toc h2 { margin-top: 0; border: 0; padding: 0; }
    .toc ol { margin: 0; padding-left: 18px; columns: 2; }
    .toc li { margin: 0 0 4px; }
    a { color: #174a6b; text-decoration: none; }
    section { page-break-inside: avoid; }
    .footer-note { margin-top: 18px; padding-top: 8px; border-top: 1px solid #d8e2ea; color: #52616f; font-size: 10px; }
</style>
</head>
<body>
    <div class="cover">
        <h1>Relatorio Completo de Funcionalidades do Sistema prato_web</h1>
        <div class="meta">Gerado em ' . htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') . ' | Projeto: /home/matheus-mota/Desenvolvimento/prato_web</div>
        <div style="margin-top:8px">
            <span class="badge">Login e seguranca</span>
            <span class="badge">Funcionarios</span>
            <span class="badge">Clientes e familias</span>
            <span class="badge">Dependentes</span>
            <span class="badge">Vendas de senhas</span>
            <span class="badge">Fechamento</span>
            <span class="badge">Relatorios</span>
            <span class="badge">Backup</span>
        </div>
    </div>
    <div class="toc">
        <h2>Indice</h2>
        <ol>' . $toc . '</ol>
    </div>
    ' . $content . '
    <div class="footer-note">Documento gerado automaticamente a partir da leitura dos arquivos locais do projeto. Nao inclui valores secretos do .env.</div>
</body>
</html>';

file_put_contents($htmlPath, $html);

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

file_put_contents($pdfPath, $dompdf->output());

echo json_encode([
    'success' => true,
    'pdf' => $pdfPath,
    'html' => $htmlPath,
    'pdf_size' => filesize($pdfPath),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
