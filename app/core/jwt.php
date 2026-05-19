<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function authenticateUser()
{
    if (isset($_SESSION[\Hcode\Model\Funcionarios::SESSION]) && !empty($_SESSION[\Hcode\Model\Funcionarios::SESSION])) {
        return (object) ["funcionarios" => $_SESSION[\Hcode\Model\Funcionarios::SESSION]];
    }

    $headers = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : [];

    if (!isset($headers['authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "Token ou sessao nao enviados"]);
        exit;
    }

    $token = str_replace("Bearer ", "", $headers['authorization']);
    $secretKey = function_exists('pc_env') ? pc_env('JWT_SECRET', '') : (getenv('JWT_SECRET') ?: '');

    if ($secretKey === '') {
        http_response_code(401);
        echo json_encode(["error" => "JWT nao configurado"]);
        exit;
    }

    try {
        return JWT::decode($token, new Key($secretKey, 'HS256'));
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["error" => "Token invalido"]);
        exit;
    }
}
