<?php

require_once 'config.php';
require_once __DIR__ . '/../auth.php';
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
        } elseif ($endpoint === '/conductores') {
            $controller->getByRol('conductor');
        } elseif ($endpoint === '/peones') {
            $controller->getByRol('peon');
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
            requiereRol(['administrador']);
            $controller->create($data);
        } elseif ($endpoint === '/login') {
            $controller->login($data);
        } elseif ($endpoint === '/logout') {
            session_destroy();
            echo json_encode(["success" => "Sesión cerrada"]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (preg_match('/^\/usuarios\/(\d+)\/estado$/', $endpoint, $matches)) {
            requiereRol(['administrador']);
            $controller->updateEstado($matches[1], $data);
        } elseif (preg_match('/^\/usuarios\/(\d+)$/', $endpoint, $matches)) {
            requiereRol(['administrador']);
            $controller->update($matches[1], $data);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
        }
        break;

    case 'DELETE':
        if (preg_match('/^\/usuarios\/(\d+)$/', $endpoint, $matches)) {
            requiereRol(['administrador']);
            $controller->delete($matches[1]);
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
