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
            $stmt = $pdo->prepare("SELECT * FROM orden_servicio WHERE id_orden = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            if ($item) {
                http_response_code(200);
                echo json_encode(["status" => "success", "data" => $item]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Orden no encontrada"]);
            }
        } else {
            $stmt = $pdo->query("SELECT * FROM orden_servicio ORDER BY id_orden ASC");
            $items = $stmt->fetchAll();
            http_response_code(200);
            echo json_encode(["status" => "success", "total" => count($items), "data" => $items]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id_equipo'], $data['problema_reportado'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Datos mínimos faltantes"]);
            exit();
        }
        $diag = $data['diagnostico_tecnico'] ?? null;
        $costo = $data['costo_mano_obra'] ?? 0.00;
        $estatus = $data['estatus'] ?? 'Recibido';

        $stmt = $pdo->prepare("INSERT INTO orden_servicio (id_equipo, problema_reportado, diagnostico_tecnico, costo_mano_obra, estatus) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$data['id_equipo'], $data['problema_reportado'], $diag, $costo, $estatus])) {
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Orden creada exitosamente", "id" => (int)$pdo->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Error al registrar orden"]);
        }
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID no especificado"]);
            exit();
        }
        $data = json_decode(file_get_contents("php://input"), true);
        $diag = $data['diagnostico_tecnico'] ?? null;
        $costo = $data['costo_mano_obra'] ?? 0.00;
        $estatus = $data['estatus'] ?? 'Recibido';
        $fecha_salida = $data['fecha_salida'] ?? null;

        $stmt = $pdo->prepare("UPDATE orden_servicio SET id_equipo = ?, problema_reportado = ?, diagnostico_tecnico = ?, costo_mano_obra = ?, estatus = ?, fecha_salida = ? WHERE id_orden = ?");
        $stmt->execute([$data['id_equipo'], $data['problema_reportado'], $diag, $costo, $estatus, $fecha_salida, $id]);
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Orden actualizada"]);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID no especificado"]);
            exit();
        }
        $stmt = $pdo->prepare("DELETE FROM orden_servicio WHERE id_orden = ?");
        $stmt->execute([$id]);
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Orden eliminada"]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Método no permitido"]);
        break;
}
