<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Funcionarios;
use \Hcode\Model\SocioEconomico;
use \Hcode\Model\SocioEconomicoSaude;

$app->get("/admin/socio-economico", function () {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_VIEW');

    $page = new PageAdmin();
    $page->setTpl("socio-economico", [
        "titulares" => SocioEconomico::listarTitularesComRegistro(),
        "msgError" => SocioEconomico::getError(),
        "msgSuccess" => SocioEconomico::getSuccess()
    ]);
});

$app->get("/admin/socio-economico/create", function () {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_CREATE');

    $idTitular = (int)($_GET['id_titular'] ?? 0);

    if ($idTitular > 0) {
        $registroExistente = SocioEconomico::findByTitular($idTitular);
        if (!empty($registroExistente)) {
            header("Location: /admin/socio-economico/" . (int)$registroExistente['id']);
            exit;
        }
    }

    $page = new PageAdmin();
    $page->setTpl("socio-economico-form", [
        "titulares" => SocioEconomico::listarTitulares(),
        "registro" => SocioEconomico::emptyRecord($idTitular),
        "formAction" => "/admin/socio-economico/create",
        "formTitle" => "Cadastro Socio Economico",
        "buttonText" => "Salvar",
        "msgError" => SocioEconomico::getError(),
        "msgSuccess" => SocioEconomico::getSuccess()
    ]);
});

$app->post("/admin/socio-economico/create", function () {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_CREATE');

    try {
        SocioEconomico::salvar($_POST);
        SocioEconomico::setSuccess("Socio economico cadastrado com sucesso.");
        header("Location: /admin/socio-economico");
        exit;
    } catch (Exception $e) {
        SocioEconomico::setError($e->getMessage());
        header("Location: /admin/socio-economico/create");
        exit;
    }
});

$app->get("/admin/socio-economico/:id", function ($id) {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_UPDATE');

    $registro = SocioEconomico::getById((int)$id);

    if (empty($registro)) {
        SocioEconomico::setError("Registro socio economico nao encontrado.");
        header("Location: /admin/socio-economico");
        exit;
    }

    $page = new PageAdmin();
    $page->setTpl("socio-economico-form", [
        "titulares" => SocioEconomico::listarTitulares(),
        "registro" => array_merge(SocioEconomico::emptyRecord(), $registro),
        "formAction" => "/admin/socio-economico/update",
        "formTitle" => "Editar Socio Economico",
        "buttonText" => "Atualizar",
        "msgError" => SocioEconomico::getError(),
        "msgSuccess" => SocioEconomico::getSuccess()
    ]);
});

$app->post("/admin/socio-economico/update", function () {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_UPDATE');

    $id = (int)($_POST['id'] ?? 0);

    try {
        SocioEconomico::atualizar($id, $_POST);
        SocioEconomico::setSuccess("Socio economico atualizado com sucesso.");
        header("Location: /admin/socio-economico");
        exit;
    } catch (Exception $e) {
        SocioEconomico::setError($e->getMessage());
        header("Location: /admin/socio-economico/" . $id);
        exit;
    }
});

$app->get("/admin/socio-economico/:id/delete", function ($id) {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_DELETE');

    try {
        SocioEconomico::excluir((int)$id);
        SocioEconomico::setSuccess("Socio economico excluido com sucesso.");
    } catch (Exception $e) {
        SocioEconomico::setError($e->getMessage());
    }

    header("Location: /admin/socio-economico");
    exit;
});

$app->get("/admin/socio-economico-saude", function () {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_SAUDE_VIEW');

    $page = new PageAdmin();
    $page->setTpl("socio-economico-saude", [
        "registros" => SocioEconomicoSaude::listar(),
        "msgError" => SocioEconomicoSaude::getError(),
        "msgSuccess" => SocioEconomicoSaude::getSuccess()
    ]);
});

$app->get("/admin/socio-economico-saude/create", function () {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_SAUDE_CREATE');

    $page = new PageAdmin();
    $page->setTpl("socio-economico-saude-form", [
        "titulares" => SocioEconomicoSaude::listarTitulares(),
        "dependentes" => SocioEconomicoSaude::listarDependentes(),
        "registro" => SocioEconomicoSaude::emptyRecord(),
        "formAction" => "/admin/socio-economico-saude/create",
        "formTitle" => "Cadastro Socio Economico Saude",
        "buttonText" => "Salvar",
        "msgError" => SocioEconomicoSaude::getError(),
        "msgSuccess" => SocioEconomicoSaude::getSuccess()
    ]);
});

$app->post("/admin/socio-economico-saude/create", function () {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_SAUDE_CREATE');

    try {
        SocioEconomicoSaude::salvar($_POST);
        SocioEconomicoSaude::setSuccess("Registro de saude cadastrado com sucesso.");
        header("Location: /admin/socio-economico-saude");
        exit;
    } catch (Exception $e) {
        SocioEconomicoSaude::setError($e->getMessage());
        header("Location: /admin/socio-economico-saude/create");
        exit;
    }
});

$app->get("/admin/socio-economico-saude/:id", function ($id) {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_SAUDE_UPDATE');

    $registro = SocioEconomicoSaude::getById((int)$id);

    if (empty($registro)) {
        SocioEconomicoSaude::setError("Registro de saude nao encontrado.");
        header("Location: /admin/socio-economico-saude");
        exit;
    }

    $page = new PageAdmin();
    $page->setTpl("socio-economico-saude-form", [
        "titulares" => SocioEconomicoSaude::listarTitulares(),
        "dependentes" => SocioEconomicoSaude::listarDependentes(),
        "registro" => array_merge(SocioEconomicoSaude::emptyRecord(), $registro),
        "formAction" => "/admin/socio-economico-saude/update",
        "formTitle" => "Editar Socio Economico Saude",
        "buttonText" => "Atualizar",
        "msgError" => SocioEconomicoSaude::getError(),
        "msgSuccess" => SocioEconomicoSaude::getSuccess()
    ]);
});

$app->post("/admin/socio-economico-saude/update", function () {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_SAUDE_UPDATE');

    $id = (int)($_POST['id'] ?? 0);

    try {
        SocioEconomicoSaude::atualizar($id, $_POST);
        SocioEconomicoSaude::setSuccess("Registro de saude atualizado com sucesso.");
        header("Location: /admin/socio-economico-saude");
        exit;
    } catch (Exception $e) {
        SocioEconomicoSaude::setError($e->getMessage());
        header("Location: /admin/socio-economico-saude/" . $id);
        exit;
    }
});

$app->get("/admin/socio-economico-saude/:id/delete", function ($id) {
    Funcionarios::checkPermission('SOCIO_ECONOMICO_SAUDE_DELETE');

    try {
        SocioEconomicoSaude::excluir((int)$id);
        SocioEconomicoSaude::setSuccess("Registro de saude excluido com sucesso.");
    } catch (Exception $e) {
        SocioEconomicoSaude::setError($e->getMessage());
    }

    header("Location: /admin/socio-economico-saude");
    exit;
});
