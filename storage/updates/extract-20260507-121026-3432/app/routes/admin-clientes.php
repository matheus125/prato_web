<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Clientes;
use \Hcode\Model\Funcionarios;
use \Hcode\DB\Sql;

if (!function_exists('pc_cliente_valor_opcional')) {
	function pc_cliente_valor_opcional($valor)
	{
		$valor = trim((string)$valor);
		return $valor === '' ? null : $valor;
	}
}

if (!function_exists('pc_cliente_data_opcional')) {
	function pc_cliente_data_opcional($valor)
	{
		$valor = trim((string)$valor);

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
			return null;
		}

		$data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

		return ($data && $data->format('Y-m-d') === $valor) ? $valor : null;
	}
}

if (!function_exists('pc_cliente_genero_opcional')) {
	function pc_cliente_genero_opcional($valor)
	{
		$valor = trim((string)$valor);
		$upper = strtoupper($valor);

		if ($upper === 'M' || $upper === 'F') {
			return $upper;
		}

		if ($upper === 'OUTRO') {
			return 'Outro';
		}

		return null;
	}
}

if (!function_exists('pc_cliente_idade_por_data')) {
	function pc_cliente_idade_por_data($valor)
	{
		$data = pc_cliente_data_opcional($valor);

		if ($data === null) {
			return null;
		}

		$nascimento = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
		$hoje = new DateTimeImmutable('today');

		if (!$nascimento) {
			return null;
		}

		if ($nascimento > $hoje) {
			return -1;
		}

		return (int)$nascimento->diff($hoje)->y;
	}
}

if (!function_exists('pc_cliente_validar_obrigatorios')) {
	function pc_cliente_validar_obrigatorios(array $dados)
	{
		$campos = [
			'nome_completo' => 'Nome Completo',
			'cor_cliente' => 'Cor/Etnia',
			'genero_cliente' => 'Sexo',
			'estado_civil' => 'Estado Civil',
			'data_nascimento' => 'Nascimento',
			'cpf' => 'CPF',
			'cep' => 'CEP'
		];

		$faltando = [];

		foreach ($campos as $campo => $label) {
			$valor = $dados[$campo] ?? '';

			if ($campo === 'data_nascimento') {
				if (pc_cliente_data_opcional($valor) === null) {
					$faltando[] = $label;
				}
				continue;
			}

			if ($campo === 'genero_cliente') {
				if (pc_cliente_genero_opcional($valor) === null) {
					$faltando[] = $label;
				}
				continue;
			}

			if ($campo === 'cpf' || $campo === 'cep') {
				if (preg_replace('/\D+/', '', (string)$valor) === '') {
					$faltando[] = $label;
				}
				continue;
			}

			if (pc_cliente_valor_opcional($valor) === null) {
				$faltando[] = $label;
			}
		}

		if (count($faltando) > 0) {
			throw new Exception('Os campos obrigatorios devem ser preenchidos: ' . implode(', ', $faltando) . '.');
		}

		$idade = pc_cliente_idade_por_data($dados['data_nascimento'] ?? '');

		if ($idade === null || $idade < 18) {
			throw new Exception('Cliente menor de idade. Só podem ser cadastrados clientes com idade maior ou igual a 18 anos.');
		}
	}
}

if (!function_exists('pc_cliente_nome_familia')) {
	function pc_cliente_nome_familia($nomeFamilia, $nomeCompleto)
	{
		$nomeFamilia = pc_cliente_valor_opcional($nomeFamilia);

		if ($nomeFamilia !== null) {
			return $nomeFamilia;
		}

		$nomeCompleto = trim((string)$nomeCompleto);

		if ($nomeCompleto === '') {
			return 'Familia sem nome';
		}

		$partes = preg_split('/\s+/', $nomeCompleto);
		$referencia = $partes[1] ?? $partes[0];

		return 'Familia ' . $referencia;
	}
}

if (!function_exists('pc_cliente_status_vulnerabilidade')) {
	function pc_cliente_status_vulnerabilidade()
	{
		return 'PESSOA EM SITUAÇÃO DE RUA';
	}
}

if (!function_exists('pc_cliente_normalizar_status')) {
	function pc_cliente_normalizar_status($valor)
	{
		$valor = str_replace(
			['á', 'à', 'â', 'ã', 'é', 'è', 'ê', 'í', 'ì', 'î', 'ó', 'ò', 'ô', 'õ', 'ú', 'ù', 'û', 'ç'],
			['a', 'a', 'a', 'a', 'e', 'e', 'e', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'c'],
			trim((string)$valor)
		);
		$valor = strtoupper($valor);
		$valor = str_replace(
			['Á', 'À', 'Â', 'Ã', 'É', 'È', 'Ê', 'Í', 'Ì', 'Î', 'Ó', 'Ò', 'Ô', 'Õ', 'Ú', 'Ù', 'Û', 'Ç'],
			['A', 'A', 'A', 'A', 'E', 'E', 'E', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'C'],
			$valor
		);

		return $valor;
	}
}

if (!function_exists('pc_cliente_is_vulnerabilidade')) {
	function pc_cliente_is_vulnerabilidade($status)
	{
		return pc_cliente_normalizar_status($status) === 'PESSOA EM SITUACAO DE RUA';
	}
}

if (!function_exists('pc_cliente_validar_vulnerabilidade')) {
	function pc_cliente_validar_vulnerabilidade(array $dados)
	{
		$nome = pc_cliente_valor_opcional($dados['nome_completo'] ?? '');
		$genero = pc_cliente_genero_opcional($dados['genero_cliente'] ?? '');
		$idadeRaw = trim((string)($dados['idade'] ?? ''));

		$faltando = [];

		if ($nome === null) {
			$faltando[] = 'Nome/Apelido';
		}

		if ($genero === null) {
			$faltando[] = 'Sexo';
		}

		if ($idadeRaw === '') {
			$faltando[] = 'Idade';
		}

		if (count($faltando) > 0) {
			throw new Exception('Os campos obrigatorios devem ser preenchidos: ' . implode(', ', $faltando) . '.');
		}

		if (!preg_match('/^\d{1,3}$/', $idadeRaw)) {
			throw new Exception('Informe uma idade valida.');
		}

		$idade = (int)$idadeRaw;

		if ($idade < 0 || $idade > 130) {
			throw new Exception('Informe uma idade valida.');
		}

		return [
			'nome_completo' => $nome,
			'genero_cliente' => $genero,
			'idade' => $idade
		];
	}
}

if (!function_exists('pc_cliente_salvar_vulnerabilidade')) {
	function pc_cliente_salvar_vulnerabilidade(array $dados)
	{
		$dados = pc_cliente_validar_vulnerabilidade($dados);
		$sql = new Sql();

		try {
			$sql->query('START TRANSACTION');

			$nomeFamilia = pc_cliente_nome_familia('', $dados['nome_completo']);

			$sql->query("
				INSERT INTO tb_familia (nome_familia, registration_date)
				VALUES (:nome_familia, NOW())
			", [
				':nome_familia' => $nomeFamilia
			]);

			$rowFamilia = $sql->select('SELECT LAST_INSERT_ID() AS id');
			$idFamilia = (int)($rowFamilia[0]['id'] ?? 0);

			$sql->query("
				INSERT INTO tb_titular (
					id_endereco,
					id_familia,
					nome_completo,
					idade,
					genero,
					status_cliente,
					registration_date
				) VALUES (
					NULL,
					:id_familia,
					:nome_completo,
					:idade,
					:genero,
					:status_cliente,
					NOW()
				)
			", [
				':id_familia' => $idFamilia,
				':nome_completo' => $dados['nome_completo'],
				':idade' => $dados['idade'],
				':genero' => $dados['genero_cliente'],
				':status_cliente' => pc_cliente_status_vulnerabilidade()
			]);

			$rowTitular = $sql->select('SELECT LAST_INSERT_ID() AS id');

			$sql->query('COMMIT');

			return (int)($rowTitular[0]['id'] ?? 0);
		} catch (Exception $e) {
			try {
				$sql->query('ROLLBACK');
			} catch (Exception $ignored) {
			}

			throw $e;
		}
	}
}

if (!function_exists('pc_cliente_atualizar_vulnerabilidade')) {
	function pc_cliente_atualizar_vulnerabilidade($id, array $dados)
	{
		$id = (int)$id;

		if ($id <= 0) {
			throw new Exception('ID do titular invalido.');
		}

		$dados = pc_cliente_validar_vulnerabilidade($dados);
		$sql = new Sql();

		$rows = $sql->select("
			SELECT id, id_familia, status_cliente
			FROM tb_titular
			WHERE id = :id
			LIMIT 1
		", [
			':id' => $id
		]);

		if (count($rows) === 0) {
			throw new Exception('Cliente nao encontrado.');
		}

		if (!pc_cliente_is_vulnerabilidade($rows[0]['status_cliente'] ?? '')) {
			throw new Exception('Este cliente nao esta marcado como pessoa em situacao de rua.');
		}

		try {
			$sql->query('START TRANSACTION');

			$idFamilia = (int)($rows[0]['id_familia'] ?? 0);
			$nomeFamilia = pc_cliente_nome_familia('', $dados['nome_completo']);

			if ($idFamilia > 0) {
				$sql->query("
					UPDATE tb_familia
					SET nome_familia = :nome_familia
					WHERE id = :id
				", [
					':nome_familia' => $nomeFamilia,
					':id' => $idFamilia
				]);
			} else {
				$sql->query("
					INSERT INTO tb_familia (nome_familia, registration_date)
					VALUES (:nome_familia, NOW())
				", [
					':nome_familia' => $nomeFamilia
				]);

				$rowFamilia = $sql->select('SELECT LAST_INSERT_ID() AS id');
				$idFamilia = (int)($rowFamilia[0]['id'] ?? 0);
			}

			$sql->query("
				UPDATE tb_titular
				SET id_familia = :id_familia,
					nome_completo = :nome_completo,
					idade = :idade,
					genero = :genero,
					status_cliente = :status_cliente,
					registration_date_update = NOW()
				WHERE id = :id
			", [
				':id_familia' => $idFamilia,
				':nome_completo' => $dados['nome_completo'],
				':idade' => $dados['idade'],
				':genero' => $dados['genero_cliente'],
				':status_cliente' => pc_cliente_status_vulnerabilidade(),
				':id' => $id
			]);

			$sql->query('COMMIT');
		} catch (Exception $e) {
			try {
				$sql->query('ROLLBACK');
			} catch (Exception $ignored) {
			}

			throw $e;
		}
	}
}

$app->get("/admin/clientes", function () {

	Funcionarios::checkPermission('CLIENTES_VIEW');

	$lista_titulares = Clientes::lista_titulares();

	$page = new PageAdmin();

	$page->setTpl("clientes", array(
		"lista_titulares" => $lista_titulares,
		"msgError" => Clientes::getError(),
		"msgSuccess" => Clientes::getSuccess()
	));
});

$app->get("/admin/clientes/create", function () {

	Funcionarios::checkPermission('CLIENTES_CREATE');

	$page = new PageAdmin();

	$page->setTpl("clientes-create", [
		"msgError" => Clientes::getError(),
		"msgSuccess" => Clientes::getSuccess()
	]);
});

$app->post("/admin/clientes/create", function () {

	Funcionarios::checkPermission('CLIENTES_CREATE');

	try {
		pc_cliente_validar_obrigatorios($_POST);

		$clientes = new Clientes();
		$clientes->setData($_POST);
		$clientes->salvar_cliente_titular();

		Clientes::setSuccess("Cliente cadastrado com sucesso.");
		header("Location: /admin/clientes");
		exit;
	} catch (Exception $e) {
		Clientes::setError($e->getMessage());
		header("Location: /admin/clientes/create");
		exit;
	}
});

$app->get("/admin/clientes/vulnerabilidade/create", function () {

	Funcionarios::checkPermission('CLIENTES_CREATE');

	$page = new PageAdmin();

	$page->setTpl("clientes-vulnerabilidade-create", [
		"msgError" => Clientes::getError(),
		"msgSuccess" => Clientes::getSuccess(),
		"statusCliente" => pc_cliente_status_vulnerabilidade()
	]);
});

$app->post("/admin/clientes/vulnerabilidade/create", function () {

	Funcionarios::checkPermission('CLIENTES_CREATE');

	try {
		pc_cliente_salvar_vulnerabilidade($_POST);

		Clientes::setSuccess("Cliente em vulnerabilidade cadastrado com sucesso.");
		header("Location: /admin/clientes");
		exit;
	} catch (Exception $e) {
		Clientes::setError($e->getMessage());
		header("Location: /admin/clientes/vulnerabilidade/create");
		exit;
	}
});

$app->get("/admin/clientes/vulnerabilidade/:id", function ($id) {

	Funcionarios::checkPermission('CLIENTES_UPDATE');

	$clientes = new Clientes();

	if (!$clientes->getByTitular((int)$id)) {
		Clientes::setError("Cliente nao encontrado.");
		header("Location: /admin/clientes");
		exit;
	}

	$values = $clientes->getValues();

	if (!pc_cliente_is_vulnerabilidade($values["status_cliente"] ?? "")) {
		Clientes::setError("Este cliente nao esta marcado como pessoa em situacao de rua.");
		header("Location: /admin/clientes/" . (int)$id);
		exit;
	}

	$page = new PageAdmin();

	$page->setTpl("clientes-vulnerabilidade-update", [
		"clientes" => $values,
		"msgError" => Clientes::getError(),
		"msgSuccess" => Clientes::getSuccess(),
		"statusCliente" => pc_cliente_status_vulnerabilidade()
	]);
});

$app->post("/admin/clientes/vulnerabilidade/update", function () {

	Funcionarios::checkPermission('CLIENTES_UPDATE');

	$id = (int)($_POST["id"] ?? 0);

	try {
		pc_cliente_atualizar_vulnerabilidade($id, $_POST);

		Clientes::setSuccess("Cliente em vulnerabilidade atualizado com sucesso.");
		header("Location: /admin/clientes");
		exit;
	} catch (Exception $e) {
		Clientes::setError($e->getMessage());
		header("Location: /admin/clientes/vulnerabilidade/" . $id);
		exit;
	}
});

$app->get("/admin/index", function () {

	$lista_titulares = Clientes::lista_titulares();

	$page = new PageAdmin();

	$page->setTpl("index", [
		"lista_titulares" => $lista_titulares,
		"msgError" => Clientes::getError(),
		"msgSuccess" => Clientes::getSuccess()
	]);
});

/*
|--------------------------------------------------------------------------
| ROTAS AJAX DE VERIFICAÇÃO
| IMPORTANTE: DEVEM VIR ANTES DE /admin/clientes/:id
|--------------------------------------------------------------------------
*/

$app->get('/admin/clientes/verificar-cpf', function () {
	header('Content-Type: application/json; charset=utf-8');

	try {
		$valor = preg_replace('/\D+/', '', $_GET['valor'] ?? '');
		$idsB64 = $_GET['ids_b64'] ?? '';

		$idTitular = 0;

		if (!empty($idsB64)) {
			$ids = json_decode(base64_decode($idsB64), true);
			$idTitular = (int)($ids['id_titular'] ?? 0);
		}

		if ($valor === '') {
			echo json_encode(['exists' => false], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$sql = new Sql();
		$rows = $sql->select("
			SELECT id
			FROM tb_titular
			WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = :cpf
			AND id != :id
			LIMIT 1
		", [
			':cpf' => $valor,
			':id' => $idTitular
		]);

		echo json_encode([
			'exists' => count($rows) > 0
		], JSON_UNESCAPED_UNICODE);
	} catch (Exception $e) {
		http_response_code(500);
		echo json_encode([
			'exists' => false,
			'error' => $e->getMessage()
		], JSON_UNESCAPED_UNICODE);
	}

	exit;
});

$app->get('/admin/clientes/verificar-rg', function () {
	header('Content-Type: application/json; charset=utf-8');

	try {
		$valor = trim($_GET['valor'] ?? '');
		$idsB64 = $_GET['ids_b64'] ?? '';

		$idTitular = 0;

		if (!empty($idsB64)) {
			$ids = json_decode(base64_decode($idsB64), true);
			$idTitular = (int)($ids['id_titular'] ?? 0);
		}

		if ($valor === '') {
			echo json_encode(['exists' => false], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$sql = new Sql();
		$rows = $sql->select("
			SELECT id
			FROM tb_titular
			WHERE rg = :rg
			AND id != :id
			LIMIT 1
		", [
			':rg' => $valor,
			':id' => $idTitular
		]);

		echo json_encode([
			'exists' => count($rows) > 0
		], JSON_UNESCAPED_UNICODE);
	} catch (Exception $e) {
		http_response_code(500);
		echo json_encode([
			'exists' => false,
			'error' => $e->getMessage()
		], JSON_UNESCAPED_UNICODE);
	}

	exit;
});

$app->get('/admin/clientes/verificar-nis', function () {
	header('Content-Type: application/json; charset=utf-8');

	try {
		$valor = trim($_GET['valor'] ?? '');
		$idsB64 = $_GET['ids_b64'] ?? '';

		$idTitular = 0;

		if (!empty($idsB64)) {
			$ids = json_decode(base64_decode($idsB64), true);
			$idTitular = (int)($ids['id_titular'] ?? 0);
		}

		if ($valor === '') {
			echo json_encode(['exists' => false], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$sql = new Sql();
		$rows = $sql->select("
			SELECT id
			FROM tb_titular
			WHERE nis = :nis
			AND id != :id
			LIMIT 1
		", [
			':nis' => $valor,
			':id' => $idTitular
		]);

		echo json_encode([
			'exists' => count($rows) > 0
		], JSON_UNESCAPED_UNICODE);
	} catch (Exception $e) {
		http_response_code(500);
		echo json_encode([
			'exists' => false,
			'error' => $e->getMessage()
		], JSON_UNESCAPED_UNICODE);
	}

	exit;
});

$app->get('/admin/clientes/verificar-telefone', function () {
	header('Content-Type: application/json; charset=utf-8');

	try {
		$valor = preg_replace('/\D/', '', $_GET['valor'] ?? '');
		$idsB64 = $_GET['ids_b64'] ?? '';

		$idTitular = 0;

		if (!empty($idsB64)) {
			$ids = json_decode(base64_decode($idsB64), true);
			$idTitular = (int)($ids['id_titular'] ?? 0);
		}

		if ($valor === '') {
			echo json_encode(['exists' => false], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$sql = new Sql();
		$rows = $sql->select("
			SELECT id
			FROM tb_titular
			WHERE REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = :telefone
			AND id != :id
			LIMIT 1
		", [
			':telefone' => $valor,
			':id' => $idTitular
		]);

		echo json_encode([
			'exists' => count($rows) > 0
		], JSON_UNESCAPED_UNICODE);
	} catch (Exception $e) {
		http_response_code(500);
		echo json_encode([
			'exists' => false,
			'error' => $e->getMessage()
		], JSON_UNESCAPED_UNICODE);
	}

	exit;
});

/*
|--------------------------------------------------------------------------
| ROTAS DINÂMICAS
|--------------------------------------------------------------------------
*/

$app->get("/admin/clientes/:id", function ($id) {

	Funcionarios::checkPermission('CLIENTES_UPDATE');

	$clientes = new Clientes();

	$clientes->getByTitular((int)$id);

	$values = $clientes->getValues();

	if (empty($values)) {
		Clientes::setError("Cliente nao encontrado.");
		header("Location: /admin/clientes");
		exit;
	}

	if (pc_cliente_is_vulnerabilidade($values["status_cliente"] ?? "")) {
		$page = new PageAdmin();

		$page->setTpl("clientes-vulnerabilidade-update", array(
			"clientes" => $values,
			"msgError" => Clientes::getError(),
			"msgSuccess" => Clientes::getSuccess(),
			"statusCliente" => pc_cliente_status_vulnerabilidade()
		));

		return;
	}

	$ids = [
		"id_titular"  => (int)($values["id"] ?? 0),
		"id_familia"  => (int)($values["id_familia"] ?? 0),
		"id_endereco" => (int)($values["id_endereco"] ?? 0)
	];

	$ids_b64 = base64_encode(json_encode($ids, JSON_UNESCAPED_UNICODE));

	$page = new PageAdmin();

	$page->setTpl("clientes-update", array(
		"clientes" => $values,
		"ids_b64"  => $ids_b64,
		"msgError" => Clientes::getError(),
		"msgSuccess" => Clientes::getSuccess()
	));
});

$app->post("/admin/clientes/update", function () {

	Funcionarios::checkPermission('CLIENTES_UPDATE');

	$raw = base64_decode($_POST["ids_b64"] ?? "", true);
	$ids = json_decode($raw ?: "{}", true);

	$id_titular  = (int)($ids["id_titular"] ?? 0);
	$id_familia  = (int)($ids["id_familia"] ?? 0);
	$id_endereco = (int)($ids["id_endereco"] ?? 0);

	if ($id_titular <= 0 || $id_familia <= 0 || $id_endereco <= 0) {
		Clientes::setError("IDs inválidos para atualização (titular/família/endereço).");
		header("Location: /admin/clientes");
		exit;
	}

	try {
		pc_cliente_validar_obrigatorios($_POST);

		$sql = new Sql();

		$genero = pc_cliente_genero_opcional($_POST["genero_cliente"] ?? "");
		$nomeFamilia = pc_cliente_nome_familia($_POST["nome_familia"] ?? "", $_POST["nome_completo"] ?? "");
		$statusCliente = pc_cliente_valor_opcional($_POST["status_cliente"] ?? "") ?? "Ativo";

		$sql->select("
			CALL sp_atualizar_titular_familia_endereco(
				:id_titular, :id_familia, :id_endereco,
				:cep, :bairro, :rua, :numero, :referencia, :nacionalidade, :naturalidade, :cidade, :tempo_moradia_anos,
				:nome_familia,
				:nome_completo, :nome_social, :cor_cliente, :nome_mae, :telefone, :data_nascimento, :genero, :estado_civil, :rg, :cpf, :nis, :status_cliente
			)
		", [
			":id_titular" => $id_titular,
			":id_familia" => $id_familia,
			":id_endereco" => $id_endereco,

			":cep" => pc_cliente_valor_opcional($_POST["cep"] ?? ""),
			":bairro" => pc_cliente_valor_opcional($_POST["bairro"] ?? ""),
			":rua" => pc_cliente_valor_opcional($_POST["rua"] ?? ""),
			":numero" => pc_cliente_valor_opcional($_POST["numero"] ?? ""),
			":referencia" => pc_cliente_valor_opcional($_POST["referencia"] ?? ""),
			":nacionalidade" => pc_cliente_valor_opcional($_POST["nacionalidade"] ?? ""),
			":naturalidade" => pc_cliente_valor_opcional($_POST["naturalidade"] ?? ""),
			":cidade" => pc_cliente_valor_opcional($_POST["cidade"] ?? ""),
			":tempo_moradia_anos" => pc_cliente_valor_opcional($_POST["tempo_moradia_cliente"] ?? ""),

			":nome_familia" => $nomeFamilia,

			":nome_completo" => pc_cliente_valor_opcional($_POST["nome_completo"] ?? ""),
			":nome_social" => pc_cliente_valor_opcional($_POST["nome_social"] ?? ""),
			":cor_cliente" => pc_cliente_valor_opcional($_POST["cor_cliente"] ?? ""),
			":nome_mae" => pc_cliente_valor_opcional($_POST["nome_mae"] ?? ""),
			":telefone" => pc_cliente_valor_opcional($_POST["telefone"] ?? ""),
			":data_nascimento" => pc_cliente_data_opcional($_POST["data_nascimento"] ?? ""),
			":genero" => $genero,
			":estado_civil" => pc_cliente_valor_opcional($_POST["estado_civil"] ?? ""),
			":rg" => pc_cliente_valor_opcional($_POST["rg"] ?? ""),
			":cpf" => pc_cliente_valor_opcional($_POST["cpf"] ?? ""),
			":nis" => pc_cliente_valor_opcional($_POST["nis"] ?? ""),
			":status_cliente" => $statusCliente
		]);

		Clientes::setSuccess("Cliente atualizado com sucesso.");
		header("Location: /admin/clientes");
		exit;
	} catch (Exception $e) {
		Clientes::setError($e->getMessage());
		header("Location: /admin/clientes/" . $id_titular);
		exit;
	}
});

$app->get("/admin/clientes/:id/delete", function ($id) {

	Funcionarios::checkPermission('CLIENTES_DELETE');

	$id = (int)$id;

	if ($id <= 0) {
		Clientes::setError("ID do titular inválido.");
		header("Location: /admin/clientes");
		exit;
	}

	$sql = new Sql();

	try {

		$result = $sql->select("
			CALL sp_excluir_titular_dependentes_endereco(:id_titular)
		", [
			":id_titular" => $id
		]);

		Clientes::setSuccess($result[0]["status"] ?? "Cliente excluído com sucesso.");
		header("Location: /admin/clientes");
		exit;
	} catch (Exception $e) {

		Clientes::setError($e->getMessage());
		header("Location: /admin/clientes");
		exit;
	}
});
