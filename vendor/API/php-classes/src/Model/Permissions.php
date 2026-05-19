<?php

namespace Hcode\Model;
use \Hcode\DB\Sql;

class Permissions
{
    public static function definitions(): array
    {
        return [
            'DASHBOARD_VIEW' => ['description' => 'Visualizar dashboard', 'module_name' => 'DASHBOARD'],

            'FUNCIONARIOS_VIEW' => ['description' => 'Visualizar funcionários', 'module_name' => 'FUNCIONARIOS'],
            'FUNCIONARIOS_CREATE' => ['description' => 'Cadastrar funcionários', 'module_name' => 'FUNCIONARIOS'],
            'FUNCIONARIOS_UPDATE' => ['description' => 'Editar funcionários', 'module_name' => 'FUNCIONARIOS'],
            'FUNCIONARIOS_DELETE' => ['description' => 'Excluir/inativar funcionários', 'module_name' => 'FUNCIONARIOS'],
            'FUNCIONARIOS_PASSWORD' => ['description' => 'Alterar senha de funcionários', 'module_name' => 'FUNCIONARIOS'],

            'CLIENTES_VIEW' => ['description' => 'Visualizar clientes', 'module_name' => 'CLIENTES'],
            'CLIENTES_CREATE' => ['description' => 'Cadastrar clientes', 'module_name' => 'CLIENTES'],
            'CLIENTES_UPDATE' => ['description' => 'Editar clientes', 'module_name' => 'CLIENTES'],
            'CLIENTES_DELETE' => ['description' => 'Excluir clientes', 'module_name' => 'CLIENTES'],

            'DEPENDENTES_VIEW' => ['description' => 'Visualizar dependentes', 'module_name' => 'DEPENDENTES'],
            'DEPENDENTES_CREATE' => ['description' => 'Cadastrar dependentes', 'module_name' => 'DEPENDENTES'],
            'DEPENDENTES_UPDATE' => ['description' => 'Editar dependentes', 'module_name' => 'DEPENDENTES'],

            'SOCIO_ECONOMICO_VIEW' => ['description' => 'Visualizar socio economico', 'module_name' => 'SOCIO_ECONOMICO'],
            'SOCIO_ECONOMICO_CREATE' => ['description' => 'Cadastrar socio economico', 'module_name' => 'SOCIO_ECONOMICO'],
            'SOCIO_ECONOMICO_UPDATE' => ['description' => 'Editar socio economico', 'module_name' => 'SOCIO_ECONOMICO'],
            'SOCIO_ECONOMICO_DELETE' => ['description' => 'Excluir socio economico', 'module_name' => 'SOCIO_ECONOMICO'],

            'SOCIO_ECONOMICO_SAUDE_VIEW' => ['description' => 'Visualizar socio economico saude', 'module_name' => 'SOCIO_ECONOMICO_SAUDE'],
            'SOCIO_ECONOMICO_SAUDE_CREATE' => ['description' => 'Cadastrar socio economico saude', 'module_name' => 'SOCIO_ECONOMICO_SAUDE'],
            'SOCIO_ECONOMICO_SAUDE_UPDATE' => ['description' => 'Editar socio economico saude', 'module_name' => 'SOCIO_ECONOMICO_SAUDE'],
            'SOCIO_ECONOMICO_SAUDE_DELETE' => ['description' => 'Excluir socio economico saude', 'module_name' => 'SOCIO_ECONOMICO_SAUDE'],

            'VENDAS_VIEW' => ['description' => 'Visualizar vendas', 'module_name' => 'VENDAS'],
            'RELATORIOS_VIEW' => ['description' => 'Visualizar relatórios', 'module_name' => 'RELATORIOS'],
            'BACKUP_RUN' => ['description' => 'Executar backup', 'module_name' => 'BACKUP'],

            'NOTIFICACOES_VIEW' => ['description' => 'Visualizar notificações', 'module_name' => 'NOTIFICACOES'],
            'NOTIFICACOES_CLEAR' => ['description' => 'Limpar notificações', 'module_name' => 'NOTIFICACOES'],
            'NOTIFICACAO_TESTE_CREATE' => ['description' => 'Gerar notificação de teste', 'module_name' => 'NOTIFICACOES'],

            'ACL_PROFILES_MANAGE' => ['description' => 'Gerenciar permissões por perfil', 'module_name' => 'SEGURANCA'],
            'ACL_DENIED_VIEW' => ['description' => 'Visualizar acessos negados', 'module_name' => 'SEGURANCA'],
            'USUARIOS_SECURITY_MANAGE' => ['description' => 'Gerenciar status/bloqueio de usuários', 'module_name' => 'SEGURANCA'],
            'SISTEMA_DEBUG' => ['description' => 'Acessar rotas de debug', 'module_name' => 'SEGURANCA'],
            'AUDITORIA_VIEW' => ['description' => 'Visualizar auditoria de acoes', 'module_name' => 'SEGURANCA'],
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
                'DASHBOARD_VIEW',
                'FUNCIONARIOS_VIEW',
                'FUNCIONARIOS_CREATE',
                'FUNCIONARIOS_UPDATE',
                'FUNCIONARIOS_PASSWORD',
                'CLIENTES_VIEW',
                'CLIENTES_CREATE',
                'CLIENTES_UPDATE',
                'DEPENDENTES_VIEW',
                'DEPENDENTES_CREATE',
                'DEPENDENTES_UPDATE',
                'SOCIO_ECONOMICO_VIEW',
                'SOCIO_ECONOMICO_CREATE',
                'SOCIO_ECONOMICO_UPDATE',
                'SOCIO_ECONOMICO_SAUDE_VIEW',
                'SOCIO_ECONOMICO_SAUDE_CREATE',
                'SOCIO_ECONOMICO_SAUDE_UPDATE',
                'VENDAS_VIEW',
                'RELATORIOS_VIEW',
                'BACKUP_RUN',
                'NOTIFICACOES_VIEW',
                'NOTIFICACOES_CLEAR'
            ],
            'ASSESSOR' => [
                'DASHBOARD_VIEW',
                'CLIENTES_VIEW',
                'DEPENDENTES_VIEW',
                'SOCIO_ECONOMICO_VIEW',
                'SOCIO_ECONOMICO_SAUDE_VIEW',
                'VENDAS_VIEW',
                'RELATORIOS_VIEW',
                'NOTIFICACOES_VIEW'
            ]
        ];
    }

    private static function normalizeDefinition(string $permissionKey, $definition): array
    {
        if (is_array($definition)) {
            return [
                'permission_key' => $permissionKey,
                'description' => $definition['description'] ?? $permissionKey,
                'module_name' => $definition['module_name'] ?? self::inferModuleName($permissionKey),
            ];
        }

        return [
            'permission_key' => $permissionKey,
            'description' => (string)$definition,
            'module_name' => self::inferModuleName($permissionKey),
        ];
    }

    private static function inferModuleName(string $permissionKey): string
    {
        $parts = explode('_', $permissionKey);
        return $parts[0] ?? 'SISTEMA';
    }

    public static function syncDefinitions(): void
    {
        $sql = new Sql();
        $existsPermissions = $sql->select("SHOW TABLES LIKE 'tb_permissions'");
        $existsProfilePermissions = $sql->select("SHOW TABLES LIKE 'tb_profile_permissions'");

        if (count($existsPermissions) === 0 || count($existsProfilePermissions) === 0) {
            return;
        }

        foreach (self::definitions() as $permissionKey => $definition) {
            $normalized = self::normalizeDefinition($permissionKey, $definition);

            $sql->query(
                "INSERT INTO tb_permissions (permission_key, description, module_name, created_at)
                 VALUES (:permission_key, :description, :module_name, NOW())
                 ON DUPLICATE KEY UPDATE
                    description = VALUES(description),
                    module_name = VALUES(module_name)",
                [
                    ':permission_key' => $normalized['permission_key'],
                    ':description' => $normalized['description'],
                    ':module_name' => $normalized['module_name'],
                ]
            );
        }

        foreach (self::defaultProfilePermissions() as $perfil => $permissionKeys) {
            foreach ($permissionKeys as $key) {
                $sql->query(
                    "INSERT IGNORE INTO tb_profile_permissions (perfil, id_permission)
                     SELECT :perfil, id_permission
                     FROM tb_permissions
                     WHERE permission_key = :permission_key",
                    [
                        ':perfil' => $perfil,
                        ':permission_key' => $key,
                    ]
                );
            }
        }
    }

    public static function listAll(): array
    {
        self::syncDefinitions();
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_permissions ORDER BY module_name, description");
    }

    public static function listByProfile(string $perfil): array
    {
        self::syncDefinitions();
        $sql = new Sql();
        $rows = $sql->select("SELECT p.*
            FROM tb_permissions p
            INNER JOIN tb_profile_permissions pp ON pp.id_permission = p.id_permission
            WHERE pp.perfil = :perfil
            ORDER BY p.module_name, p.description", [':perfil' => $perfil]);
        return array_map(fn($r) => $r['permission_key'], $rows);
    }

    public static function saveProfilePermissions(string $perfil, array $permissionKeys): void
    {
        self::syncDefinitions();
        $sql = new Sql();
        $sql->query("DELETE pp FROM tb_profile_permissions pp WHERE pp.perfil = :perfil", [':perfil' => $perfil]);
        foreach ($permissionKeys as $key) {
            $sql->query("INSERT IGNORE INTO tb_profile_permissions (perfil, id_permission)
                SELECT :perfil, id_permission FROM tb_permissions WHERE permission_key = :permission_key", [
                ':perfil' => $perfil,
                ':permission_key' => $key
            ]);
        }
    }

    public static function userHasPermission(array $user, string $permissionKey): bool
    {
        if (($user['perfil'] ?? '') === 'ADMIN') return true;
        if (!isset($_SESSION['acl_permissions']) || !is_array($_SESSION['acl_permissions'])) {
            $_SESSION['acl_permissions'] = self::listByProfile($user['perfil'] ?? 'ASSESSOR');
        }
        return in_array($permissionKey, $_SESSION['acl_permissions'], true);
    }

    public static function clearSessionCache(): void
    {
        unset($_SESSION['acl_permissions']);
    }
}
