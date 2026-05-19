<?php

namespace Hcode\Security;

class Permissions
{
    public static function definitions(): array
    {
        return [
            'DASHBOARD_VIEW' => 'Visualizar dashboard',
            'FUNCIONARIOS_VIEW' => 'Visualizar funcionários',
            'FUNCIONARIOS_CREATE' => 'Cadastrar funcionários',
            'FUNCIONARIOS_UPDATE' => 'Editar funcionários',
            'FUNCIONARIOS_DELETE' => 'Excluir/inativar funcionários',
            'FUNCIONARIOS_PASSWORD' => 'Alterar senha de funcionários',
            'CLIENTES_VIEW' => 'Visualizar clientes',
            'CLIENTES_CREATE' => 'Cadastrar clientes',
            'CLIENTES_UPDATE' => 'Editar clientes',
            'CLIENTES_DELETE' => 'Excluir clientes',
            'DEPENDENTES_VIEW' => 'Visualizar dependentes',
            'DEPENDENTES_CREATE' => 'Cadastrar dependentes',
            'DEPENDENTES_UPDATE' => 'Editar dependentes',
            'SOCIO_ECONOMICO_VIEW' => 'Visualizar socio economico',
            'SOCIO_ECONOMICO_CREATE' => 'Cadastrar socio economico',
            'SOCIO_ECONOMICO_UPDATE' => 'Editar socio economico',
            'SOCIO_ECONOMICO_DELETE' => 'Excluir socio economico',
            'SOCIO_ECONOMICO_SAUDE_VIEW' => 'Visualizar socio economico saude',
            'SOCIO_ECONOMICO_SAUDE_CREATE' => 'Cadastrar socio economico saude',
            'SOCIO_ECONOMICO_SAUDE_UPDATE' => 'Editar socio economico saude',
            'SOCIO_ECONOMICO_SAUDE_DELETE' => 'Excluir socio economico saude',
            'VENDAS_VIEW' => 'Visualizar vendas',
            'RELATORIOS_VIEW' => 'Visualizar relatórios',
            'BACKUP_RUN' => 'Executar backup',
            'NOTIFICACOES_VIEW' => 'Visualizar notificações',
            'NOTIFICACOES_CLEAR' => 'Limpar notificações',
            'NOTIFICACAO_TESTE_CREATE' => 'Gerar notificação de teste',
            'ACL_PROFILES_MANAGE' => 'Gerenciar permissões por perfil',
            'ACL_DENIED_VIEW' => 'Visualizar acessos negados',
            'USUARIOS_SECURITY_MANAGE' => 'Gerenciar status/bloqueio de usuários',
            'SISTEMA_DEBUG' => 'Acessar rotas de debug',
            'AUDITORIA_VIEW' => 'Visualizar auditoria de acoes'
        ];
    }

    public static function routeMap(): array
    {
        return [
            '/admin' => 'DASHBOARD_VIEW',
            '/admin/index' => 'DASHBOARD_VIEW',
            '/admin/funcionarios' => 'FUNCIONARIOS_VIEW',
            '/admin/funcionarios/create' => 'FUNCIONARIOS_CREATE',
            '/admin/funcionarios/:id_usuario' => 'FUNCIONARIOS_UPDATE',
            '/admin/funcionarios/:id_usuario/password' => 'FUNCIONARIOS_PASSWORD',
            '/admin/funcionarios/:id_usuario/delete' => 'FUNCIONARIOS_DELETE',
            '/admin/funcionarios/verificar-cpf' => 'FUNCIONARIOS_UPDATE',
            '/admin/clientes' => 'CLIENTES_VIEW',
            '/admin/clientes/create' => 'CLIENTES_CREATE',
            '/admin/clientes/vulnerabilidade/create' => 'CLIENTES_CREATE',
            '/admin/clientes/vulnerabilidade/update' => 'CLIENTES_UPDATE',
            '/admin/clientes/vulnerabilidade/:id' => 'CLIENTES_UPDATE',
            '/admin/clientes/update' => 'CLIENTES_UPDATE',
            '/admin/clientes/:id' => 'CLIENTES_UPDATE',
            '/admin/clientes/:id/delete' => 'CLIENTES_DELETE',
            '/admin/dependente/create' => 'DEPENDENTES_CREATE',
            '/admin/dependentes/create-json' => 'DEPENDENTES_CREATE',
            '/admin/dependentes/ajax/:id' => 'DEPENDENTES_VIEW',
            '/admin/dependentes/editar/:id' => 'DEPENDENTES_UPDATE',
            '/admin/dependentes/get/:id' => 'DEPENDENTES_VIEW',
            '/admin/titulares/json' => 'DEPENDENTES_VIEW',
            '/admin/socio-economico' => 'SOCIO_ECONOMICO_VIEW',
            '/admin/socio-economico/create' => 'SOCIO_ECONOMICO_CREATE',
            '/admin/socio-economico/update' => 'SOCIO_ECONOMICO_UPDATE',
            '/admin/socio-economico/:id' => 'SOCIO_ECONOMICO_UPDATE',
            '/admin/socio-economico/:id/delete' => 'SOCIO_ECONOMICO_DELETE',
            '/admin/socio-economico-saude' => 'SOCIO_ECONOMICO_SAUDE_VIEW',
            '/admin/socio-economico-saude/create' => 'SOCIO_ECONOMICO_SAUDE_CREATE',
            '/admin/socio-economico-saude/update' => 'SOCIO_ECONOMICO_SAUDE_UPDATE',
            '/admin/socio-economico-saude/:id' => 'SOCIO_ECONOMICO_SAUDE_UPDATE',
            '/admin/socio-economico-saude/:id/delete' => 'SOCIO_ECONOMICO_SAUDE_DELETE',
            '/admin/vendas' => 'VENDAS_VIEW',
            '/admin/api/senhas' => 'VENDAS_VIEW',
            '/admin/api/titulares' => 'VENDAS_VIEW',
            '/admin/titulares/:id/dependentes' => 'VENDAS_VIEW',
            '/admin/api/senhas/contagem' => 'VENDAS_VIEW',
            '/admin/api/senhas/ja-comprou' => 'VENDAS_VIEW',
            '/admin/relatorio/senhas' => 'RELATORIOS_VIEW',
            '/admin/api/relatorio/senhas/resumo' => 'RELATORIOS_VIEW',
            '/admin/api/relatorio/senhas/lista' => 'RELATORIOS_VIEW',
            '/admin/api/relatorio/senhas/top10' => 'RELATORIOS_VIEW',
            '/admin/api/relatorio/senhas/mensal' => 'RELATORIOS_VIEW',
            '/admin/api/relatorio/senhas/export' => 'RELATORIOS_VIEW',
            '/admin/api/relatorio/fechamento-info' => 'RELATORIOS_VIEW',
            '/admin/api/relatorio/fechar' => 'RELATORIOS_VIEW',
            '/admin/api/relatorio/pdf' => 'RELATORIOS_VIEW',
            '/admin/relatorio/pdf/historico' => 'RELATORIOS_VIEW',
            '/admin/api/relatorio/pdf/historico' => 'RELATORIOS_VIEW',
            '/admin/api/fechamento/manual' => 'RELATORIOS_VIEW',
            '/admin/api/fechamento/sync-remoto' => 'RELATORIOS_VIEW',
            '/admin/api/relatorios/gerar' => 'RELATORIOS_VIEW',
            '/admin/api/relatorios/logtest' => 'SISTEMA_DEBUG',
            '/admin/dashboard/geral' => 'DASHBOARD_VIEW',
            '/admin/api/dashboard/geral' => 'DASHBOARD_VIEW',
            '/admin/teste-backup' => 'BACKUP_RUN',
            '/admin/backup/run' => 'BACKUP_RUN',
            '/admin/api/backup/run-and-send' => 'BACKUP_RUN',
            '/admin/api/update/check' => 'DASHBOARD_VIEW',
            '/admin/api/update/apply' => 'BACKUP_RUN',
            '/admin/debug-logpath' => 'SISTEMA_DEBUG',
            '/admin/teste-notifs' => 'SISTEMA_DEBUG',
            '/admin/test-tb-usuario' => 'SISTEMA_DEBUG',
            '/admin/test-email' => 'SISTEMA_DEBUG',
            '/admin/ping' => 'SISTEMA_DEBUG',
            '/admin/notificacoes' => 'NOTIFICACOES_VIEW',
            '/admin/notificacoes/limpar' => 'NOTIFICACOES_CLEAR',
            '/admin/notificacoes/add-teste' => 'NOTIFICACAO_TESTE_CREATE',
            '/admin/seguranca/permissoes' => 'ACL_PROFILES_MANAGE',
            '/admin/seguranca/acessos-negados' => 'ACL_DENIED_VIEW',
            '/admin/seguranca/auditoria' => 'AUDITORIA_VIEW',
            '/admin/usuarios/seguranca' => 'USUARIOS_SECURITY_MANAGE',
            '/admin/:id_usuario/status' => 'USUARIOS_SECURITY_MANAGE',
            '/admin/funcionarios/:id_pessoa/status-funcionario' => 'USUARIOS_SECURITY_MANAGE',
        ];
    }

    public static function defaultProfilePermissions(): array
    {
        return [
            'ADMIN' => array_keys(self::definitions()),
            'SUPERVISOR' => [
                'DASHBOARD_VIEW','FUNCIONARIOS_VIEW','FUNCIONARIOS_CREATE','FUNCIONARIOS_UPDATE','FUNCIONARIOS_PASSWORD',
                'CLIENTES_VIEW','CLIENTES_CREATE','CLIENTES_UPDATE',
                'DEPENDENTES_VIEW','DEPENDENTES_CREATE','DEPENDENTES_UPDATE',
                'SOCIO_ECONOMICO_VIEW','SOCIO_ECONOMICO_CREATE','SOCIO_ECONOMICO_UPDATE',
                'SOCIO_ECONOMICO_SAUDE_VIEW','SOCIO_ECONOMICO_SAUDE_CREATE','SOCIO_ECONOMICO_SAUDE_UPDATE',
                'VENDAS_VIEW','RELATORIOS_VIEW','BACKUP_RUN','NOTIFICACOES_VIEW','NOTIFICACOES_CLEAR'
            ],
            'ASSESSOR' => [
                'DASHBOARD_VIEW','CLIENTES_VIEW','DEPENDENTES_VIEW',
                'SOCIO_ECONOMICO_VIEW','SOCIO_ECONOMICO_SAUDE_VIEW',
                'VENDAS_VIEW','RELATORIOS_VIEW','NOTIFICACOES_VIEW'
            ]
        ];
    }
}
