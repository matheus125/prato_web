<?php

namespace Hcode\Model;

use \Exception;
use \Hcode\DB\Sql;
use \Hcode\Model;

class SocioEconomicoSaude extends Model
{
    const ERROR = "SocioEconomicoSaudeError";
    const SUCCESS = "SocioEconomicoSaudeSuccess";

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

    public static function emptyRecord(): array
    {
        return [
            'id' => '',
            'id_titular' => '',
            'id_dependente' => '',
            'tipo_pessoa' => 'titular',
            'doenca' => '',
            'outras_Doencas' => '',
            'deficiencia' => '',
            'justificativa_Deficiencia' => '',
            'laudo' => '',
            'observacao' => '',
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

    public static function listarDependentes(): array
    {
        $sql = new Sql();

        return $sql->select("
            SELECT
                d.id,
                d.id_titular,
                d.nome,
                d.cpf,
                d.dependencia_cliente,
                t.nome_completo AS titular_nome
            FROM tb_dependentes d
            INNER JOIN tb_titular t ON t.id = d.id_titular
            ORDER BY t.nome_completo, d.nome
        ");
    }

    public static function listar(): array
    {
        $sql = new Sql();

        return $sql->select("
            SELECT
                s.*,
                t.nome_completo AS titular_nome,
                t.cpf AS titular_cpf,
                d.nome AS dependente_nome,
                d.cpf AS dependente_cpf,
                d.dependencia_cliente,
                CASE
                    WHEN s.id_dependente IS NULL THEN 'Titular'
                    ELSE 'Dependente'
                END AS tipo_pessoa_label,
                COALESCE(d.nome, t.nome_completo) AS pessoa_nome,
                DATE_FORMAT(s.registration_date, '%d/%m/%Y %H:%i') AS cadastro_em,
                DATE_FORMAT(s.registration_date_update, '%d/%m/%Y %H:%i') AS atualizado_em
            FROM tb_socio_economico_saude s
            LEFT JOIN tb_titular t ON t.id = s.id_titular
            LEFT JOIN tb_dependentes d ON d.id = s.id_dependente
            ORDER BY s.id DESC
        ");
    }

    public static function getById($id): array
    {
        $sql = new Sql();
        $rows = $sql->select("
            SELECT *
            FROM tb_socio_economico_saude
            WHERE id = :id
            LIMIT 1
        ", [
            ':id' => (int)$id
        ]);

        if (!isset($rows[0])) {
            return [];
        }

        $rows[0]['tipo_pessoa'] = ((int)($rows[0]['id_dependente'] ?? 0) > 0) ? 'dependente' : 'titular';
        return $rows[0];
    }

    private static function titularExiste($idTitular): bool
    {
        $sql = new Sql();
        $rows = $sql->select("SELECT id FROM tb_titular WHERE id = :id LIMIT 1", [
            ':id' => (int)$idTitular
        ]);

        return count($rows) > 0;
    }

    private static function dependentePertenceAoTitular($idDependente, $idTitular): bool
    {
        $sql = new Sql();
        $rows = $sql->select("
            SELECT id
            FROM tb_dependentes
            WHERE id = :id_dependente
            AND id_titular = :id_titular
            LIMIT 1
        ", [
            ':id_dependente' => (int)$idDependente,
            ':id_titular' => (int)$idTitular
        ]);

        return count($rows) > 0;
    }

    private static function normalizarPessoa(array $data): array
    {
        $idTitular = (int)($data['id_titular'] ?? 0);
        $tipoPessoa = trim((string)($data['tipo_pessoa'] ?? 'titular'));
        $idDependente = null;

        if ($idTitular <= 0) {
            throw new Exception("Selecione um titular.");
        }

        if (!self::titularExiste($idTitular)) {
            throw new Exception("Titular nao encontrado.");
        }

        if ($tipoPessoa === 'dependente') {
            $idDependente = self::intOpcional($data['id_dependente'] ?? null);

            if (!$idDependente) {
                throw new Exception("Selecione um dependente.");
            }

            if (!self::dependentePertenceAoTitular($idDependente, $idTitular)) {
                throw new Exception("O dependente selecionado nao pertence ao titular informado.");
            }
        }

        return [
            'id_titular' => $idTitular,
            'id_dependente' => $idDependente,
        ];
    }

    private static function params(array $data): array
    {
        $pessoa = self::normalizarPessoa($data);

        return [
            ':id_titular' => $pessoa['id_titular'],
            ':id_dependente' => $pessoa['id_dependente'],
            ':doenca' => self::valorOpcional($data['doenca'] ?? null),
            ':outras_Doencas' => self::valorOpcional($data['outras_Doencas'] ?? null),
            ':deficiencia' => self::valorOpcional($data['deficiencia'] ?? null),
            ':justificativa_Deficiencia' => self::valorOpcional($data['justificativa_Deficiencia'] ?? null),
            ':laudo' => self::valorOpcional($data['laudo'] ?? null),
            ':observacao' => self::valorOpcional($data['observacao'] ?? null),
        ];
    }

    public static function salvar(array $data): int
    {
        $sql = new Sql();
        $sql->query("
            INSERT INTO tb_socio_economico_saude (
                id_titular, id_dependente, doenca, outras_Doencas, deficiencia,
                justificativa_Deficiencia, laudo, observacao
            ) VALUES (
                :id_titular, :id_dependente, :doenca, :outras_Doencas, :deficiencia,
                :justificativa_Deficiencia, :laudo, :observacao
            )
        ", self::params($data));

        $row = $sql->select("SELECT LAST_INSERT_ID() AS id");
        return (int)($row[0]['id'] ?? 0);
    }

    public static function atualizar($id, array $data): void
    {
        $id = (int)$id;

        if ($id <= 0 || empty(self::getById($id))) {
            throw new Exception("Registro de saude nao encontrado.");
        }

        $params = self::params($data);
        $params[':id'] = $id;

        $sql = new Sql();
        $sql->query("
            UPDATE tb_socio_economico_saude SET
                id_titular = :id_titular,
                id_dependente = :id_dependente,
                doenca = :doenca,
                outras_Doencas = :outras_Doencas,
                deficiencia = :deficiencia,
                justificativa_Deficiencia = :justificativa_Deficiencia,
                laudo = :laudo,
                observacao = :observacao,
                registration_date_update = NOW()
            WHERE id = :id
        ", $params);
    }

    public static function excluir($id): void
    {
        $sql = new Sql();
        $sql->query("DELETE FROM tb_socio_economico_saude WHERE id = :id", [
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
