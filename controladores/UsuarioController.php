<?php

require_once __DIR__ . '/../modelos/UsuarioModel.php';

class UsuarioController
{
    private $modelo;

    public function __construct($conn)
    {
        $this->modelo = new UsuarioModel($conn);
    }

    public function getAll()
    {
        $usuarios = $this->modelo->getAll();
        echo json_encode($usuarios);
    }

    public function getById($id)
    {
        $usuario = $this->modelo->getById($id);

        if ($usuario) {
            echo json_encode($usuario);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Usuario no encontrado"]);
        }
    }

    public function create($data)
    {
        if (empty($data['nombre']) || empty($data['apellido']) || empty($data['email']) || empty($data['contrasena'])) {
            http_response_code(400);
            echo json_encode(["error" => "Faltan campos obligatorios: nombre, apellido, email, contrasena"]);
            return;
        }

        $result = $this->modelo->create($data);

        if (isset($result['error'])) {
            http_response_code(400);
        } else {
            http_response_code(201);
        }

        echo json_encode($result);
    }

    public function updateEstado($id, $data)
    {
        if (empty($data['estado'])) {
            http_response_code(400);
            echo json_encode(["error" => "Falta el campo: estado"]);
            return;
        }

        $result = $this->modelo->updateEstado($id, $data['estado']);

        if (isset($result['error'])) {
            http_response_code(400);
        }

        echo json_encode($result);
    }

    public function login($data)
    {
        if (empty($data['email']) || empty($data['contrasena'])) {
            http_response_code(400);
            echo json_encode(["error" => "Faltan campos: email y contrasena"]);
            return;
        }

        $result = $this->modelo->login($data);

        if (isset($result['error'])) {
            http_response_code(401);
        }

        echo json_encode($result);
    }
}
