<?php
include 'connection.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id_orderan'])) {
            $id = intval($_GET['id_orderan']);
            $stmt = $connect->prepare(
                "SELECT d.*, m.nama_menu, m.harga, m.foto
                 FROM detail_orderans d
                 JOIN menus m ON d.id_menu = m.id
                 WHERE d.id_orderan = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            echo json_encode(['status' => 'success', 'data' => $rows]);
        } else {
            $result = $connect->query(
                "SELECT d.*, m.nama_menu, m.foto
                 FROM detail_orderans d
                 JOIN menus m ON d.id_menu = m.id
                 ORDER BY d.id DESC"
            );
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            echo json_encode(['status' => 'success', 'data' => $rows]);
        }
        break;

    case 'PUT':
        // Update status detail: processing → done
        $data   = json_decode(file_get_contents('php://input'), true);
        $id     = intval($data['id']);
        $status = $data['status']; // processing | done
        $now    = date('Y-m-d H:i:s');
        $stmt   = $connect->prepare("UPDATE detail_orderans SET status=?, updated_at=? WHERE id=?");
        $stmt->bind_param("ssi", $status, $now, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Status detail diupdate']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'fail', 'msg' => 'Method not allowed']);
}
?>
