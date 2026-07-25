<?php

require_once 'config.php';
require_once 'controladores/UsuarioController.php';

$controller = new UsuarioController($conn);

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = '/sigeru/api-usuarios';
$endpoint = str_replace($basePath, '', $uri);

switch ($method) {
    case 'GET':
        if ($endpoint === '/usuarios') {
            $controller->getAll();
        } elseif (preg_match('/^\/usuarios\/(\d+)$/', $endpoint, $matches)) {
            $controller->getById($matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if ($endpoint === '/usuarios') {
            $controller->create($data);
        } elseif ($endpoint === '/login') {
            $controller->login($data);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (preg_match('/^\/usuarios\/(\d+)$/', $endpoint, $matches)) {
            $controller->updateEstado($matches[1], $data);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
