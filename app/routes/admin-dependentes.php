<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Dependente;
use \Hcode\Model\Funcionarios;
use \Hcode\DB\Sql;

if (!function_exists('pc_dependente_data_opcional')) {
    function pc_dependente_data_opcional($valor)
    {
        $valor = trim((string)$valor);
        if ($valor === '' || $valor === '0000-00-00') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            $dt = DateTime::createFromFormat('!Y-m-d', $valor);
            return ($dt instanceof DateTime && $dt->format('Y-m-d') === $valor) ? $valor : null;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $valor)) {
            $dt = DateTime::createFromFormat('!d/m/Y', $valor);
            return ($dt instanceof DateTime && $dt->format('d/m/Y') === $valor) ? $dt->format('Y-m-d') : null;
        }

        return null;
    }
}

if (!function_exists('pc_dependente_idade_atual')) {
    function pc_dependente_idade_atual($dataNascimento, $fallback = null)
    {
        if (function_exists('pc_calcular_idade_atual')) {
            return pc_calcular_idade_atual($dataNascimento, $fallback);
        }

        return $fallback !== null && $fallback !== '' ? (int)$fallback : null;
    }
}

$app->get("/admin/dependente/create", function () {

    Funcionarios::checkPermission('DEPENDENTES_CREATE');

    $page = new PageAdmin();

    $page->setTpl("dependente-create");
});

$app->get("/admin/titulares/json", function () {

    Funcionarios::checkPermission('DEPENDENTES_VIEW');

    $sql = new Sql();

    $results = $sql->select("
        SELECT 
            id,
            nome_completo,
            cpf
        FROM tb_titular
        ORDER BY nome_completo
    ");

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results, JSON_UNESCAPED_UNICODE);
    exit;
});

$app->post('/admin/dependentes/create-json', function () {

    Funcionarios::checkPermission('DEPENDENTES_CREATE');

    header('Content-Type: application/json; charset=utf-8');

    try {

        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body) {
            throw new Exception('JSON inválido');
        }

        if (empty($body['id_titular'])) {
            throw new Exception('Titular não informado');
        }

        if (empty($body['dependentes']) || !is_array($body['dependentes'])) {
            throw new Exception('Nenhum dependente informado');
        }

        $idTitular = (int)$body['id_titular'];
        $dependentes = $body['dependentes'];
        $dependentesCriados = [];
        $nomesCriados = [];

        $sql = new Sql();
        $sql->query("START TRANSACTION");

        foreach ($dependentes as $dep) {

            if (empty($dep['nome'])) {
                throw new Exception('Nome do dependente é obrigatório');
            }

            $generoRaw = trim($dep['genero'] ?? '');
            $genero = 'Outro';

            if (in_array($generoRaw, ['M', 'Masculino', 'masculino'])) {
                $genero = 'M';
            } elseif (in_array($generoRaw, ['F', 'Feminino', 'feminino'])) {
                $genero = 'F';
            }

            $dataNascimento = pc_dependente_data_opcional($dep['data_nascimento'] ?? null);
            $idade = pc_dependente_idade_atual($dataNascimento, $dep['idade'] ?? null);
            $nomeDependente = trim($dep['nome']);

            $sql->query("
                INSERT INTO tb_dependentes (
                    id_titular,
                    nome,
                    rg,
                    cpf,
                    data_nascimento,
                    idade,
                    genero,
                    dependencia_cliente
                ) VALUES (
                    :id_titular,
                    :nome,
                    :rg,
                    :cpf,
                    :data_nascimento,
                    :idade,
                    :genero,
                    :dependencia_cliente
                )
            ", [
                ':id_titular' => $idTitular,
                ':nome' => $nomeDependente,
                ':rg' => $dep['rg'] ?? null,
                ':cpf' => $dep['cpf'] ?? null,
                ':data_nascimento' => $dataNascimento,
                ':idade' => $idade,
                ':genero' => $genero,
                ':dependencia_cliente' => $dep['dependencia_cliente'] ?? null
            ]);

            $rowId = $sql->select("SELECT LAST_INSERT_ID() AS id");
            $dependentesCriados[] = isset($rowId[0]['id']) ? (int)$rowId[0]['id'] : 0;
            $nomesCriados[] = $nomeDependente;
        }

        $sql->query("COMMIT");
        Funcionarios::audit(
            'DEPENDENTE_CREATE',
            'DEPENDENTES',
            $idTitular,
            'Dependentes cadastrados para o titular #' . $idTitular . ': ' . implode(', ', $nomesCriados) . '. IDs: ' . implode(',', $dependentesCriados) . '.'
        );

        echo json_encode([
            'success' => true,
            'message' => 'Dependente(s) cadastrado(s) com sucesso.'
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Exception $e) {

        if (isset($sql)) {
            $sql->query("ROLLBACK");
        }

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

$app->get("/admin/dependentes/ajax/:id", function ($id) {

    header('Content-Type: application/json; charset=utf-8');

    try {
        Funcionarios::checkPermission('CLIENTES_VIEW');

        $id = (int)$id;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID do titular inválido.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = new Sql();

        $dependentes = $sql->select("
            SELECT 
                id,
                nome,
                dependencia_cliente,
                COALESCE(TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()), idade) AS idade,
                data_nascimento,
                genero
            FROM tb_dependentes
            WHERE id_titular = :id
            ORDER BY nome
        ", [
            ":id" => $id
        ]);

        echo json_encode([
            'success' => true,
            'message' => count($dependentes) ? 'Dependentes encontrados.' : 'Nenhum dependente encontrado.',
            'data' => $dependentes
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao carregar dependentes.',
            'error' => $e->getMessage(),
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

$app->get("/admin/dependentes/get/:id", function ($id) {

    header('Content-Type: application/json; charset=utf-8');

    try {
        Funcionarios::checkPermission('CLIENTES_VIEW');

        $id = (int)$id;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID do dependente inválido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = new Sql();
        $rows = $sql->select("
            SELECT
                id,
                nome,
                dependencia_cliente,
                COALESCE(TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()), idade) AS idade,
                data_nascimento,
                genero
            FROM tb_dependentes
            WHERE id = :id
            LIMIT 1
        ", [
            ':id' => $id
        ]);

        if (count($rows) === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Dependente não encontrado.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => $rows[0]
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao buscar dependente.',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

$app->post("/admin/dependentes/editar/:id", function ($id) {

    header('Content-Type: application/json; charset=utf-8');

    try {
        Funcionarios::checkPermission('DEPENDENTES_UPDATE');

        $id = (int)$id;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID do dependente inválido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Dados inválidos.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $nome = trim($body['nome'] ?? '');
        $dependencia = trim($body['dependencia_cliente'] ?? '');

        if ($nome === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Informe o nome do dependente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $alterarDataNascimento = array_key_exists('data_nascimento', $body) ? 1 : 0;
        $dataNascimento = $alterarDataNascimento ? pc_dependente_data_opcional($body['data_nascimento'] ?? null) : null;
        $idade = $alterarDataNascimento
            ? pc_dependente_idade_atual($dataNascimento, $body['idade'] ?? null)
            : (is_numeric($body['idade'] ?? null) ? (int)$body['idade'] : null);

        $generoRaw = trim($body['genero'] ?? '');
        $genero = 'Outro';

        if (in_array($generoRaw, ['M', 'Masculino', 'masculino'])) {
            $genero = 'M';
        } elseif (in_array($generoRaw, ['F', 'Feminino', 'feminino'])) {
            $genero = 'F';
        } elseif (in_array($generoRaw, ['Outro', 'outro'])) {
            $genero = 'Outro';
        }

        $sql = new Sql();
        $dependenteAntes = $sql->select("
            SELECT id, id_titular, nome
            FROM tb_dependentes
            WHERE id = :id
            LIMIT 1
        ", [
            ':id' => $id
        ]);

        if (count($dependenteAntes) === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Dependente nao encontrado.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql->query("
            UPDATE tb_dependentes
            SET
                nome = :nome,
                dependencia_cliente = :dependencia_cliente,
                data_nascimento = CASE WHEN :alterar_data_nascimento = 1 THEN :data_nascimento ELSE data_nascimento END,
                idade = :idade,
                genero = :genero
            WHERE id = :id
        ", [
            ':nome' => $nome,
            ':dependencia_cliente' => $dependencia,
            ':alterar_data_nascimento' => $alterarDataNascimento,
            ':data_nascimento' => $dataNascimento,
            ':idade' => $idade,
            ':genero' => $genero,
            ':id' => $id
        ]);

        Funcionarios::audit(
            'DEPENDENTE_UPDATE',
            'DEPENDENTES',
            $id,
            'Dependente atualizado: ' . $dependenteAntes[0]['nome'] . ' -> ' . $nome . '. Titular #' . (int)$dependenteAntes[0]['id_titular'] . '.'
        );

        echo json_encode([
            'success' => true,
            'message' => 'Dependente atualizado com sucesso.'
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao atualizar dependente.',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

$app->delete("/admin/dependentes/excluir/:id", function ($id) {

    header('Content-Type: application/json; charset=utf-8');

    try {
        Funcionarios::checkPermission('CLIENTES_DELETE');

        $id = (int)$id;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID do dependente inválido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = new Sql();
        $dependenteAntes = $sql->select("
            SELECT id, id_titular, nome
            FROM tb_dependentes
            WHERE id = :id
            LIMIT 1
        ", [
            ':id' => $id
        ]);

        if (count($dependenteAntes) === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Dependente nao encontrado.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql->query("
            DELETE FROM tb_dependentes
            WHERE id = :id
        ", [
            ':id' => $id
        ]);

        Funcionarios::audit(
            'DEPENDENTE_DELETE',
            'DEPENDENTES',
            $id,
            'Dependente excluido: ' . $dependenteAntes[0]['nome'] . '. Titular #' . (int)$dependenteAntes[0]['id_titular'] . '.'
        );

        echo json_encode([
            'success' => true,
            'message' => 'Dependente excluído com sucesso.'
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao excluir dependente.',
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});
