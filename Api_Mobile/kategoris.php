<?php
include 'connection.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id   = intval($_GET['id']);
            $stmt = $connect->prepare("SELECT * FROM kategoris WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
            } else {
                echo json_encode(['status' => 'fail', 'msg' => 'Kategori tidak ditemukan']);
            }
        } else {
            $result = $connect->query("SELECT * FROM kategoris ORDER BY nama_kategori ASC");
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            echo json_encode(['status' => 'success', 'data' => $rows]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['nama_kategori'])) {
            echo json_encode(['status' => 'fail', 'msg' => 'nama_kategori diperlukan']);
            exit;
        }
        $nama = $data['nama_kategori'];
        $now  = date('Y-m-d H:i:s');
        $stmt = $connect->prepare("INSERT INTO kategoris (nama_kategori, created_at, updated_at) VALUES (?,?,?)");
        $stmt->bind_param("sss", $nama, $now, $now);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Kategori ditambahkan', 'id' => $connect->insert_id]);
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
        $id   = intval($data['id']);
        $nama = $data['nama_kategori'];
        $now  = date('Y-m-d H:i:s');
        $stmt = $connect->prepare("UPDATE kategoris SET nama_kategori=?, updated_at=? WHERE id=?");
        $stmt->bind_param("ssi", $nama, $now, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Kategori diupdate']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = intval($data['id']);
        $stmt = $connect->prepare("DELETE FROM kategoris WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Kategori dihapus']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'fail', 'msg' => 'Method not allowed']);
}
?>
