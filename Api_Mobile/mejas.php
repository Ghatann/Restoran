<?php
include 'connection.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $connect->prepare("SELECT * FROM mejas WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
            } else {
                echo json_encode(['status' => 'fail', 'msg' => 'Meja tidak ditemukan']);
            }
        } else {
            $result = $connect->query("SELECT * FROM mejas ORDER BY nomor_meja ASC");
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            echo json_encode(['status' => 'success', 'data' => $rows]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['nomor_meja'], $data['kapasitas'])) {
            echo json_encode(['status' => 'fail', 'msg' => 'Data tidak lengkap (nomor_meja, kapasitas)']);
            exit;
        }
        $nomor  = $data['nomor_meja'];
        $kap    = intval($data['kapasitas']);
        $status = $data['status'] ?? 'available';
        $now    = date('Y-m-d H:i:s');
        $stmt = $connect->prepare("INSERT INTO mejas (nomor_meja, kapasitas, status, created_at, updated_at) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sisss", $nomor, $kap, $status, $now, $now);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Meja berhasil ditambahkan', 'id' => $connect->insert_id]);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['id'])) {
            echo json_encode(['status' => 'fail', 'msg' => 'ID diperlukan']);
            exit;
        }
        $id     = intval($data['id']);
        $nomor  = $data['nomor_meja'];
        $kap    = intval($data['kapasitas']);
        $status = $data['status'];
        $now    = date('Y-m-d H:i:s');
        $stmt = $connect->prepare("UPDATE mejas SET nomor_meja=?, kapasitas=?, status=?, updated_at=? WHERE id=?");
        $stmt->bind_param("sissi", $nomor, $kap, $status, $now, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Meja berhasil diupdate']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = intval($data['id']);
        $stmt = $connect->prepare("DELETE FROM mejas WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Meja berhasil dihapus']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'fail', 'msg' => 'Method not allowed']);
}
?>
