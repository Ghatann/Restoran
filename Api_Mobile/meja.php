<?php
include 'connection.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // READ - Get all tables or single table
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $connect->prepare("SELECT * FROM meja WHERE id_meja = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
            } else {
                echo json_encode(['status' => 'fail', 'msg' => 'Meja tidak ditemukan']);
            }
        } else {
            $result = $connect->query("SELECT * FROM meja ORDER BY kode_meja ASC");
            $meja = [];
            while ($row = $result->fetch_assoc()) {
                $meja[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $meja]);
        }
        break;

    case 'POST':
        // CREATE - Add new table
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['kode_meja'], $data['kapasitas'])) {
            echo json_encode(['status' => 'fail', 'msg' => 'Data tidak lengkap']);
            exit;
        }
        
        $kode = $data['kode_meja'];
        $kapasitas = intval($data['kapasitas']);
        $status = $data['status'] ?? 'kosong'; // Default kosong
        
        $stmt = $connect->prepare("INSERT INTO meja (kode_meja, kapasitas, status) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $kode, $kapasitas, $status);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Meja berhasil ditambahkan']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => 'Gagal tambah meja: ' . $connect->error]);
        }
        break;

    case 'PUT':
        // UPDATE - Update table
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id_meja'])) {
            echo json_encode(['status' => 'fail', 'msg' => 'ID Meja diperlukan']);
            exit;
        }
        
        $id = intval($data['id_meja']);
        $kode = $data['kode_meja'];
        $kapasitas = intval($data['kapasitas']);
        $status = $data['status'];
        
        $stmt = $connect->prepare("UPDATE meja SET kode_meja=?, kapasitas=?, status=? WHERE id_meja=?");
        $stmt->bind_param("sisi", $kode, $kapasitas, $status, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Meja berhasil diupdate']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => 'Gagal update meja']);
        }
        break;

    case 'DELETE':
        // DELETE - Delete table
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id_meja']);
        
        $stmt = $connect->prepare("DELETE FROM meja WHERE id_meja = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Meja berhasil dihapus']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => 'Gagal hapus meja']);
        }
        break;
}
?>
