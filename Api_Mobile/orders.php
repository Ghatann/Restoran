<?php
include 'connection.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get orders. Optional filter by status or table
        $sql = "SELECT o.*, m.nama_menu, mj.kode_meja 
                FROM orders o 
                JOIN menu m ON o.id_menu = m.id_menu 
                JOIN meja mj ON o.id_meja = mj.id_meja 
                ORDER BY o.tanggal DESC";
                
        $result = $connect->query($sql);
        $orders = [];
        
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'data' => $orders]);
        break;

    case 'POST':
        // Create new order
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Data: id_meja, items: [{id_menu, jumlah, catatan}], tipe (DINE_IN/TAKEAWAY)
        if (!isset($data['id_meja'], $data['items']) || !is_array($data['items'])) {
            echo json_encode(['status' => 'fail', 'msg' => 'Data order tidak lengkap']);
            exit;
        }

        $id_meja = intval($data['id_meja']);
        $tipe = $data['tipe'] ?? 'DINE_IN';
        $status = 'BELUM_BAYAR';
        $tanggal = date('Y-m-d H:i:s');
        
        // Start transaction
        $connect->begin_transaction();
        
        try {
            $stmt = $connect->prepare("INSERT INTO orders (id_meja, id_menu, jumlah, catatan, tipe, tanggal, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($data['items'] as $item) {
                $id_menu = intval($item['id_menu']);
                $jumlah = intval($item['jumlah']);
                $catatan = $item['catatan'] ?? '';
                
                $stmt->bind_param("iiissss", $id_meja, $id_menu, $jumlah, $catatan, $tipe, $tanggal, $status);
                
                if (!$stmt->execute()) {
                    throw new Exception("Gagal insert order item");
                }
                
                // Update stock? (Optional logic depending on requirement)
            }
            
            // Update status meja jadi 'terisi' jika Dine In
            if ($tipe === 'DINE_IN') {
                $connect->query("UPDATE meja SET status='terisi' WHERE id_meja=$id_meja");
            }
            
            $connect->commit();
            echo json_encode(['status' => 'success', 'msg' => 'Order berhasil dibuat']);
            
        } catch (Exception $e) {
            $connect->rollback();
            echo json_encode(['status' => 'fail', 'msg' => 'Gagal membuat order: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Update order status (e.g. cancel or paid triggered externally)
        $data = json_decode(file_get_contents('php://input'), true);
        $id_order = intval($data['id_order']);
        $status = $data['status']; // BELUM_BAYAR, SUDAH_BAYAR
        
        $stmt = $connect->prepare("UPDATE orders SET status=? WHERE id_order=?");
        $stmt->bind_param("si", $status, $id_order);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Status order update']);
        } else {
             echo json_encode(['status' => 'fail', 'msg' => 'Gagal update status']);
        }
        break;
        
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $id_order = intval($data['id_order']);
        
        if($connect->query("DELETE FROM orders WHERE id_order=$id_order")) {
             echo json_encode(['status' => 'success', 'msg' => 'Order dihapus']);
        } else {
             echo json_encode(['status' => 'fail', 'msg' => 'Gagal hapus']);
        }
        break;
}
?>
