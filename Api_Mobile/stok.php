<?php
include 'connection.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id_menu'])) {
            $id = intval($_GET['id_menu']);
            $stmt = $connect->prepare(
                "SELECT s.*, m.nama_menu 
                 FROM update_stokharians s
                 JOIN menus m ON s.id_menu = m.id
                 WHERE s.id_menu = ?
                 ORDER BY s.tanggal_update DESC"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            echo json_encode(['status' => 'success', 'data' => $rows]);
        } else {
            $result = $connect->query(
                "SELECT s.*, m.nama_menu 
                 FROM update_stokharians s
                 JOIN menus m ON s.id_menu = m.id
                 ORDER BY s.tanggal_update DESC, s.id DESC"
            );
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            echo json_encode(['status' => 'success', 'data' => $rows]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['id_menu'], $data['jumlah_porsi'])) {
            echo json_encode(['status' => 'fail', 'msg' => 'Data tidak lengkap (id_menu, jumlah_porsi)']);
            exit;
        }
        $id_menu      = intval($data['id_menu']);
        $jumlah       = intval($data['jumlah_porsi']);
        $tanggal      = $data['tanggal_update'] ?? date('Y-m-d');
        $now          = date('Y-m-d H:i:s');

        $connect->begin_transaction();
        try {
            // Insert log
            $stmt = $connect->prepare(
                "INSERT INTO update_stokharians (id_menu, jumlah_porsi, tanggal_update, created_at, updated_at) VALUES (?,?,?,?,?)"
            );
            $stmt->bind_param("iisss", $id_menu, $jumlah, $tanggal, $now, $now);
            if (!$stmt->execute()) throw new Exception($connect->error);

            // Update stok_porsi in menus
            $connect->query("UPDATE menus SET stok_porsi=$jumlah, updated_at='$now' WHERE id=$id_menu");

            $connect->commit();
            echo json_encode(['status' => 'success', 'msg' => 'Stok berhasil diupdate']);
        } catch (Exception $e) {
            $connect->rollback();
            echo json_encode(['status' => 'fail', 'msg' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'fail', 'msg' => 'Method not allowed']);
}
?>
