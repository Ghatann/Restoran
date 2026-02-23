<?php
include 'connection.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id   = intval($_GET['id']);
            $stmt = $connect->prepare("SELECT * FROM karyawans WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
            } else {
                echo json_encode(['status' => 'fail', 'msg' => 'Karyawan tidak ditemukan']);
            }
        } else {
            $result = $connect->query("SELECT * FROM karyawans ORDER BY nama_karyawan ASC");
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            echo json_encode(['status' => 'success', 'data' => $rows]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['nama_karyawan'])) {
            echo json_encode(['status' => 'fail', 'msg' => 'nama_karyawan diperlukan']);
            exit;
        }
        $nama    = $data['nama_karyawan'];
        $no_hp   = $data['no_hp']   ?? null;
        $alamat  = $data['alamat']  ?? null;
        $jabatan = $data['jabatan'] ?? null;
        $now     = date('Y-m-d H:i:s');
        $stmt = $connect->prepare("INSERT INTO karyawans (nama_karyawan, no_hp, alamat, jabatan, created_at, updated_at) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $nama, $no_hp, $alamat, $jabatan, $now, $now);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Karyawan ditambahkan', 'id' => $connect->insert_id]);
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
        $id      = intval($data['id']);
        $nama    = $data['nama_karyawan'];
        $no_hp   = $data['no_hp']   ?? null;
        $alamat  = $data['alamat']  ?? null;
        $jabatan = $data['jabatan'] ?? null;
        $now     = date('Y-m-d H:i:s');
        $stmt = $connect->prepare("UPDATE karyawans SET nama_karyawan=?, no_hp=?, alamat=?, jabatan=?, updated_at=? WHERE id=?");
        $stmt->bind_param("sssssi", $nama, $no_hp, $alamat, $jabatan, $now, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Karyawan diupdate']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = intval($data['id']);
        $stmt = $connect->prepare("DELETE FROM karyawans WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Karyawan dihapus']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'fail', 'msg' => 'Method not allowed']);
}
?>
