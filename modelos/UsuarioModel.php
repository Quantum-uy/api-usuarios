<?php

class UsuarioModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $sql = "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.telefono, u.estado,
                CASE
                    WHEN a.id_usuario IS NOT NULL THEN 'administrador'
                    WHEN o.id_usuario IS NOT NULL THEN 'operario'
                    WHEN c.id_usuario IS NOT NULL THEN 'conductor'
                    WHEN p.id_usuario IS NOT NULL THEN 'peon'
                    ELSE 'sin_rol'
                END AS rol
                FROM usuario u
                LEFT JOIN administrador a ON u.id_usuario = a.id_usuario
                LEFT JOIN operario o ON u.id_usuario = o.id_usuario
                LEFT JOIN conductor c ON u.id_usuario = c.id_usuario
                LEFT JOIN peon p ON u.id_usuario = p.id_usuario
                ORDER BY u.id_usuario DESC";

        $result = mysqli_query($this->conn, $sql);
        $usuarios = [];

        while ($row = mysqli_fetch_assoc($result)) {
            unset($row['contrasena']);
            $usuarios[] = $row;
        }

        return $usuarios;
    }

    public function getById($id)
    {
        $stmt = mysqli_prepare($this->conn, "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.telefono, u.estado,
                CASE
                    WHEN a.id_usuario IS NOT NULL THEN 'administrador'
                    WHEN o.id_usuario IS NOT NULL THEN 'operario'
                    WHEN c.id_usuario IS NOT NULL THEN 'conductor'
                    WHEN p.id_usuario IS NOT NULL THEN 'peon'
                    ELSE 'sin_rol'
                END AS rol
                FROM usuario u
                LEFT JOIN administrador a ON u.id_usuario = a.id_usuario
                LEFT JOIN operario o ON u.id_usuario = o.id_usuario
                LEFT JOIN conductor c ON u.id_usuario = c.id_usuario
                LEFT JOIN peon p ON u.id_usuario = p.id_usuario
                WHERE u.id_usuario = ?");

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return mysqli_fetch_assoc($result);
    }

    public function create($data)
    {
        $checkStmt = mysqli_prepare($this->conn, "SELECT id_usuario FROM usuario WHERE email = ?");
        mysqli_stmt_bind_param($checkStmt, "s", $data['email']);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            return ["error" => "El email ya está registrado"];
        }

        $hashedPass = password_hash($data['contrasena'], PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO usuario (nombre, apellido, email, contrasena, telefono, estado)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $estado = $data['estado'] ?? 'pendiente';
        mysqli_stmt_bind_param($stmt, "ssssss",
            $data['nombre'],
            $data['apellido'],
            $data['email'],
            $hashedPass,
            $data['telefono'],
            $estado
        );

        if (mysqli_stmt_execute($stmt)) {
            $userId = mysqli_insert_id($this->conn);
            $this->assignRole($userId, $data['rol'] ?? 'sin_rol', $data);
            return ["success" => "Usuario creado", "id" => $userId];
        }

        return ["error" => "No se pudo crear el usuario"];
    }

    private function assignRole($userId, $rol, $data)
    {
        switch ($rol) {
            case 'administrador':
                $stmt = mysqli_prepare($this->conn,
                    "INSERT INTO administrador (id_usuario, nivel_permiso) VALUES (?, ?)"
                );
                $nivel = $data['nivel_permiso'] ?? 1;
                mysqli_stmt_bind_param($stmt, "ii", $userId, $nivel);
                mysqli_stmt_execute($stmt);
                break;

            case 'operario':
                $stmt = mysqli_prepare($this->conn,
                    "INSERT INTO operario (id_usuario, turno) VALUES (?, ?)"
                );
                $turno = $data['turno'] ?? null;
                mysqli_stmt_bind_param($stmt, "is", $userId, $turno);
                mysqli_stmt_execute($stmt);
                break;

            case 'conductor':
                $stmt = mysqli_prepare($this->conn,
                    "INSERT INTO conductor (id_usuario, turno, zona_asignada) VALUES (?, ?, ?)"
                );
                $turno = $data['turno'] ?? null;
                $zona = $data['zona_asignada'] ?? null;
                mysqli_stmt_bind_param($stmt, "iss", $userId, $turno, $zona);
                mysqli_stmt_execute($stmt);
                break;

            case 'peon':
                $stmt = mysqli_prepare($this->conn,
                    "INSERT INTO peon (id_usuario, turno) VALUES (?, ?)"
                );
                $turno = $data['turno'] ?? null;
                mysqli_stmt_bind_param($stmt, "is", $userId, $turno);
                mysqli_stmt_execute($stmt);
                break;
        }
    }

    public function updateEstado($id, $estado)
    {
        $stmt = mysqli_prepare($this->conn,
            "UPDATE usuario SET estado = ? WHERE id_usuario = ?"
        );
        mysqli_stmt_bind_param($stmt, "si", $estado, $id);

        if (mysqli_stmt_execute($stmt)) {
            return ["success" => "Estado actualizado"];
        }

        return ["error" => "No se pudo actualizar el estado"];
    }

    public function login($data)
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.contrasena, u.estado,
                CASE
                    WHEN a.id_usuario IS NOT NULL THEN 'administrador'
                    WHEN o.id_usuario IS NOT NULL THEN 'operario'
                    WHEN c.id_usuario IS NOT NULL THEN 'conductor'
                    WHEN p.id_usuario IS NOT NULL THEN 'peon'
                    ELSE 'sin_rol'
                END AS rol
                FROM usuario u
                LEFT JOIN administrador a ON u.id_usuario = a.id_usuario
                LEFT JOIN operario o ON u.id_usuario = o.id_usuario
                LEFT JOIN conductor c ON u.id_usuario = c.id_usuario
                LEFT JOIN peon p ON u.id_usuario = p.id_usuario
                WHERE u.email = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $data['email']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 0) {
            return ["error" => "Usuario no encontrado"];
        }

        $usuario = mysqli_fetch_assoc($result);

        if ($usuario['estado'] !== 'activo') {
            return ["error" => "La cuenta no está activa"];
        }

        if (!password_verify($data['contrasena'], $usuario['contrasena'])) {
            return ["error" => "Contraseña incorrecta"];
        }

        unset($usuario['contrasena']);
        return ["success" => "Login exitoso", "usuario" => $usuario];
    }
}
