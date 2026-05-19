<?php

namespace Hcode\Model;

use \Exception;
use \Hcode\DB\Sql;
use \Hcode\Model;

class SocioEconomico extends Model
{
    const ERROR = "SocioEconomicoError";
    const SUCCESS = "SocioEconomicoSuccess";

    private static function valorOpcional($valor)
    {
        $valor = trim((string)$valor);
        return $valor === '' ? null : $valor;
    }

    private static function intOpcional($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (int)$valor;
    }

    public static function emptyRecord($idTitular = 0): array
    {
        return [
            'id' => '',
            'id_titular' => (int)$idTitular,
            'escolariedade' => '',
            'renda_mensal_familia' => '',
            'programas_sociais' => '',
            'composicao_familiar' => '',
            'moradia' => '',
            'estrutura_Moradia' => '',
            'qtdPessoas_Trabalhando' => '',
            'emprego' => '',
            'profissao_Responsavel' => '',
            'AB_Agua' => '',
            'SN_basico' => '',
            'Energia_eletrica' => '',
            'Lixo_Domiciliar' => '',
            'frequenta_Centro' => '',
        ];
    }

    public static function listarTitulares(): array
    {
        $sql = new Sql();

        return $sql->select("
            SELECT id, nome_completo, cpf, status_cliente
            FROM tb_titular
            ORDER BY nome_completo
        ");
    }

    public static function listarTitularesComRegistro(): array
    {
        $sql = new Sql();

        return $sql->select("
            SELECT
                t.id AS id_titular,
                t.nome_completo,
                t.cpf,
                t.status_cliente,
                se.id AS id_socio,
                se.escolariedade,
                se.renda_mensal_familia,
                se.programas_sociais,
                DATE_FORMAT(se.registration_date, '%d/%m/%Y %H:%i') AS cadastro_em,
                DATE_FORMAT(se.registration_date_update, '%d/%m/%Y %H:%i') AS atualizado_em
            FROM tb_titular t
            LEFT JOIN tb_socio_economico se ON se.id_titular = t.id
            ORDER BY t.nome_completo
        ");
    }

    public static function getById($id): array
    {
        $sql = new Sql();
        $rows = $sql->select("
            SELECT *
            FROM tb_socio_economico
            WHERE id = :id
            LIMIT 1
        ", [
            ':id' => (int)$id
        ]);

        return $rows[0] ?? [];
    }

    public static function findByTitular($idTitular): array
    {
        $sql = new Sql();
        $rows = $sql->select("
            SELECT *
            FROM tb_socio_economico
            WHERE id_titular = :id_titular
            ORDER BY id DESC
            LIMIT 1
        ", [
            ':id_titular' => (int)$idTitular
        ]);

        return $rows[0] ?? [];
    }

    private static function titularExiste($idTitular): bool
    {
        $sql = new Sql();
        $rows = $sql->select("SELECT id FROM tb_titular WHERE id = :id LIMIT 1", [
            ':id' => (int)$idTitular
        ]);

        return count($rows) > 0;
    }

    private static function validarTitular($idTitular): void
    {
        if ((int)$idTitular <= 0) {
            throw new Exception("Selecione um titular.");
        }

        if (!self::titularExiste($idTitular)) {
            throw new Exception("Titular nao encontrado.");
        }
    }

    private static function validarDuplicidade($idTitular, $idIgnorar = 0): void
    {
        $sql = new Sql();
        $rows = $sql->select("
            SELECT id
            FROM tb_socio_economico
            WHERE id_titular = :id_titular
            AND id <> :id
            LIMIT 1
        ", [
            ':id_titular' => (int)$idTitular,
            ':id' => (int)$idIgnorar
        ]);

        if (count($rows) > 0) {
            throw new Exception("Este titular ja possui cadastro socio economico.");
        }
    }

    private static function params(array $data): array
    {
        return [
            ':id_titular' => (int)($data['id_titular'] ?? 0),
            ':escolariedade' => self::valorOpcional($data['escolariedade'] ?? null),
            ':renda_mensal_familia' => self::valorOpcional($data['renda_mensal_familia'] ?? null),
            ':programas_sociais' => self::valorOpcional($data['programas_sociais'] ?? null),
            ':composicao_familiar' => self::valorOpcional($data['composicao_familiar'] ?? null),
            ':moradia' => self::valorOpcional($data['moradia'] ?? null),
            ':estrutura_Moradia' => self::valorOpcional($data['estrutura_Moradia'] ?? null),
            ':qtdPessoas_Trabalhando' => self::intOpcional($data['qtdPessoas_Trabalhando'] ?? null),
            ':emprego' => self::valorOpcional($data['emprego'] ?? null),
            ':profissao_Responsavel' => self::valorOpcional($data['profissao_Responsavel'] ?? null),
            ':AB_Agua' => self::valorOpcional($data['AB_Agua'] ?? null),
            ':SN_basico' => self::valorOpcional($data['SN_basico'] ?? null),
            ':Energia_eletrica' => self::valorOpcional($data['Energia_eletrica'] ?? null),
            ':Lixo_Domiciliar' => self::valorOpcional($data['Lixo_Domiciliar'] ?? null),
            ':frequenta_Centro' => self::valorOpcional($data['frequenta_Centro'] ?? null),
        ];
    }

    public static function salvar(array $data): int
    {
        $idTitular = (int)($data['id_titular'] ?? 0);

        self::validarTitular($idTitular);
        self::validarDuplicidade($idTitular);

        $sql = new Sql();
        $sql->query("
            INSERT INTO tb_socio_economico (
                id_titular, escolariedade, renda_mensal_familia, programas_sociais,
                composicao_familiar, moradia, estrutura_Moradia, qtdPessoas_Trabalhando,
                emprego, profissao_Responsavel, AB_Agua, SN_basico, Energia_eletrica,
                Lixo_Domiciliar, frequenta_Centro
            ) VALUES (
                :id_titular, :escolariedade, :renda_mensal_familia, :programas_sociais,
                :composicao_familiar, :moradia, :estrutura_Moradia, :qtdPessoas_Trabalhando,
                :emprego, :profissao_Responsavel, :AB_Agua, :SN_basico, :Energia_eletrica,
                :Lixo_Domiciliar, :frequenta_Centro
            )
        ", self::params($data));

        $row = $sql->select("SELECT LAST_INSERT_ID() AS id");
        return (int)($row[0]['id'] ?? 0);
    }

    public static function atualizar($id, array $data): void
    {
        $id = (int)$id;
        $idTitular = (int)($data['id_titular'] ?? 0);

        if ($id <= 0 || empty(self::getById($id))) {
            throw new Exception("Registro socio economico nao encontrado.");
        }

        self::validarTitular($idTitular);
        self::validarDuplicidade($idTitular, $id);

        $params = self::params($data);
        $params[':id'] = $id;

        $sql = new Sql();
        $sql->query("
            UPDATE tb_socio_economico SET
                id_titular = :id_titular,
                escolariedade = :escolariedade,
                renda_mensal_familia = :renda_mensal_familia,
                programas_sociais = :programas_sociais,
                composicao_familiar = :composicao_familiar,
                moradia = :moradia,
                estrutura_Moradia = :estrutura_Moradia,
                qtdPessoas_Trabalhando = :qtdPessoas_Trabalhando,
                emprego = :emprego,
                profissao_Responsavel = :profissao_Responsavel,
                AB_Agua = :AB_Agua,
                SN_basico = :SN_basico,
                Energia_eletrica = :Energia_eletrica,
                Lixo_Domiciliar = :Lixo_Domiciliar,
                frequenta_Centro = :frequenta_Centro,
                registration_date_update = NOW()
            WHERE id = :id
        ", $params);
    }

    public static function excluir($id): void
    {
        $sql = new Sql();
        $sql->query("DELETE FROM tb_socio_economico WHERE id = :id", [
            ':id' => (int)$id
        ]);
    }

    public static function setError($msg): void
    {
        $_SESSION[self::ERROR] = $msg;
    }

    public static function getError(): string
    {
        $msg = $_SESSION[self::ERROR] ?? '';
        self::clearError();
        return $msg ?: '';
    }

    public static function clearError(): void
    {
        $_SESSION[self::ERROR] = null;
    }

    public static function setSuccess($msg): void
    {
        $_SESSION[self::SUCCESS] = $msg;
    }

    public static function getSuccess(): string
    {
        $msg = $_SESSION[self::SUCCESS] ?? '';
        self::clearSuccess();
        return $msg ?: '';
    }

    public static function clearSuccess(): void
    {
        $_SESSION[self::SUCCESS] = null;
    }
}
