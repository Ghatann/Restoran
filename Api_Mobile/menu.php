<?php
include 'connection.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // READ - Get all menus or single menu by id
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $connect->prepare("SELECT * FROM menu WHERE id_menu = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $menu = $result->fetch_assoc();
                echo json_encode([
                    'status' => 'success',
                    'data' => $menu
                ]);
            } else {
                echo json_encode([
                    'status' => 'fail',
                    'msg' => 'Menu tidak ditemukan'
                ]);
            }
        } else {
            // Get all menus
            $result = $connect->query("SELECT * FROM menu ORDER BY id_menu DESC");
            $menus = [];
            
            while ($row = $result->fetch_assoc()) {
                $menus[] = $row;
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => $menus
            ]);
        }
        break;

    case 'POST':
        // CREATE - Add new menu
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['nama_menu'], $data['harga'], $data['stok'], $data['status'])) {
            echo json_encode([
                'status' => 'fail',
                'msg' => 'Data tidak lengkap. Butuh: nama_menu, harga, stok, status'
            ]);
            exit;
        }
        
        $nama_menu = $data['nama_menu'];
        $harga = intval($data['harga']);
        $stok = intval($data['stok']);
        $status = $data['status'];
        
        // Validate status
        if (!in_array($status, ['TERSEDIA', 'SOLD_OUT'])) {
            echo json_encode([
                'status' => 'fail',
                'msg' => 'Status harus TERSEDIA atau SOLD_OUT'
            ]);
            exit;
        }
        
        $stmt = $connect->prepare("INSERT INTO menu (nama_menu, harga, stok, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $nama_menu, $harga, $stok, $status);
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'msg' => 'Menu berhasil ditambahkan',
                'id' => $connect->insert_id
            ]);
        } else {
            echo json_encode([
                'status' => 'fail',
                'msg' => 'Gagal menambahkan menu: ' . $connect->error
            ]);
        }
        break;

    case 'PUT':
        // UPDATE - Update existing menu
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id_menu'])) {
            echo json_encode([
                'status' => 'fail',
                'msg' => 'ID menu diperlukan'
            ]);
            exit;
        }
        
        $id_menu = intval($data['id_menu']);
        $nama_menu = $data['nama_menu'] ?? null;
        $harga = isset($data['harga']) ? intval($data['harga']) : null;
        $stok = isset($data['stok']) ? intval($data['stok']) : null;
        $status = $data['status'] ?? null;
        
        // Validate status if provided
        if ($status !== null && !in_array($status, ['TERSEDIA', 'SOLD_OUT'])) {
            echo json_encode([
                'status' => 'fail',
                'msg' => 'Status harus TERSEDIA atau SOLD_OUT'
            ]);
            exit;
        }
        
        // Build dynamic query
        $updates = [];
        $types = "";
        $values = [];
        
        if ($nama_menu !== null) {
            $updates[] = "nama_menu = ?";
            $types .= "s";
            $values[] = $nama_menu;
        }
        if ($harga !== null) {
            $updates[] = "harga = ?";
            $types .= "i";
            $values[] = $harga;
        }
        if ($stok !== null) {
            $updates[] = "stok = ?";
            $types .= "i";
            $values[] = $stok;
        }
        if ($status !== null) {
            $updates[] = "status = ?";
            $types .= "s";
            $values[] = $status;
        }
        
        if (empty($updates)) {
            echo json_encode([
                'status' => 'fail',
                'msg' => 'Tidak ada data untuk diupdate'
            ]);
            exit;
        }
        
        $types .= "i";
        $values[] = $id_menu;
        
        $sql = "UPDATE menu SET " . implode(", ", $updates) . " WHERE id_menu = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'status' => 'success',
                    'msg' => 'Menu berhasil diupdate'
                ]);
            } else {
                echo json_encode([
                    'status' => 'fail',
                    'msg' => 'Menu tidak ditemukan atau tidak ada perubahan'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'fail',
                'msg' => 'Gagal mengupdate menu: ' . $connect->error
            ]);
        }
        break;

    case 'DELETE':
        // DELETE - Remove menu
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id_menu'])) {
            echo json_encode([
                'status' => 'fail',
                'msg' => 'ID menu diperlukan'
            ]);
            exit;
        }
        
        $id_menu = intval($data['id_menu']);
        
        $stmt = $connect->prepare("DELETE FROM menu WHERE id_menu = ?");
        $stmt->bind_param("i", $id_menu);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'status' => 'success',
                    'msg' => 'Menu berhasil dihapus'
                ]);
            } else {
                echo json_encode([
                    'status' => 'fail',
                    'msg' => 'Menu tidak ditemukan'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'fail',
                'msg' => 'Gagal menghapus menu: ' . $connect->error
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'status' => 'fail',
            'msg' => 'Method not allowed'
        ]);
        break;
}
?>
