<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriSegments = explode('/', trim($uri, '/'));

$id = null;
if (isset($uriSegments[3]) && is_numeric($uriSegments[3])) {
    $id = (int)$uriSegments[3];
}

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("SELECT id_cliente, nombre, apellido_paterno, apellido_materno, telefono, correo_electronico, fecha_registro FROM cliente WHERE id_cliente = ?");
            $stmt->execute([$id]);
            $cliente = $stmt->fetch();

            if ($cliente) {
                http_response_code(200);
                echo json_encode(["status" => "success", "data" => $cliente]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Cliente no encontrado"]);
            }
        } else {
            $stmt = $pdo->query("SELECT id_cliente, nombre, apellido_paterno, apellido_materno, telefono, correo_electronico, fecha_registro FROM cliente ORDER BY id_cliente ASC");
            $clientes = $stmt->fetchAll();
            http_response_code(200);
            echo json_encode(["status" => "success", "total" => count($clientes), "data" => $clientes]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['nombre']) || !isset($data['apellido_paterno']) || !isset($data['telefono']) || !isset($data['correo_electronico'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Datos incompletos. 'nombre', 'apellido_paterno', 'telefono' y 'correo_electronico' son obligatorios"]);
            exit();
        }

        $nombre = trim($data['nombre']);
        $paterno = trim($data['apellido_paterno']);
        $materno = isset($data['apellido_materno']) ? trim($data['apellido_materno']) : null;
        $telefono = trim($data['telefono']);
        $correo = trim($data['correo_electronico']);

        $stmt = $pdo->prepare("INSERT INTO cliente (nombre, apellido_paterno, apellido_materno, telefono, correo_electronico) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$nombre, $paterno, $materno, $telefono, $correo])) {
            http_response_code(201);
            echo json_encode([
                "status" => "success",
                "message" => "Registro creado exitosamente",
                "id" => (int)$pdo->lastInsertId()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error interno al guardar en el servidor"]);
        }
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID no proporcionado en la URL"]);
            exit();
        }

        $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Cliente no encontrado para actualizar"]);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['nombre']) || !isset($data['apellido_paterno']) || !isset($data['telefono']) || !isset($data['correo_electronico'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Datos inválidos. Campos obligatorios faltantes"]);
            exit();
        }

        $nombre = trim($data['nombre']);
        $paterno = trim($data['apellido_paterno']);
        $materno = isset($data['apellido_materno']) ? trim($data['apellido_materno']) : null;
        $telefono = trim($data['telefono']);
        $correo = trim($data['correo_electronico']);

        $stmtUpdate = $pdo->prepare("UPDATE cliente SET nombre = ?, apellido_paterno = ?, apellido_materno = ?, telefono = ?, correo_electronico = ? WHERE id_cliente = ?");
        $stmtUpdate->execute([$nombre, $paterno, $materno, $telefono, $correo, $id]);

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Registro actualizado correctamente"
        ]);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID no proporcionado en la URL"]);
            exit();
        }

        $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Cliente no encontrado"]);
            exit();
        }

        $stmtDelete = $pdo->prepare("DELETE FROM cliente WHERE id_cliente = ?");
        $stmtDelete->execute([$id]);

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Registro eliminado exitosamente"
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Método no permitido"]);
        break;
}
?>
