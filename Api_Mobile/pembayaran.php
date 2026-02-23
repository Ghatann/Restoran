<?php
include 'connection.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT p.*, o.id_order, mj.kode_meja 
                FROM pembayaran p
                JOIN orders o ON p.id_order = o.id_order
                JOIN meja mj ON o.id_meja = mj.id_meja
                ORDER BY p.tanggal_bayar DESC";
                
        $result = $connect->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $data]);
        break;

    case 'POST':
        // Process Payment (Checkout)
        // Usually grouping orders by table to pay all at once, or pay per order ID.
        // Simplified: Pay per order ID or Table ID.
        // Assuming Logic: Pay all unpaid orders for a specific table.

        $data = json_decode(file_get_contents('php://input'), true);
        $id_meja = intval($data['id_meja']);
        $metode = $data['metode'] ?? 'CASH'; // CASH or QRIS
        $tanggal = date('Y-m-d H:i:s');

        // 1. Calculate total
        $sqlTotal = "SELECT SUM(m.harga * o.jumlah) as total_bayar 
                     FROM orders o 
                     JOIN menu m ON o.id_menu = m.id_menu 
                     WHERE o.id_meja = $id_meja AND o.status = 'BELUM_BAYAR'";
        
        $resTotal = $connect->query($sqlTotal);
        $rowTotal = $resTotal->fetch_assoc();
        $total = $rowTotal['total_bayar'] ?? 0;

        if ($total <= 0) {
            echo json_encode(['status' => 'fail', 'msg' => 'Tidak ada tagihan untuk meja ini']);
            exit;
        }

        $connect->begin_transaction();

        try {
            // 2. Insert Pembayaran (One record for the transaction - simplified linked to one representative order or generic)
            // Limitations of schema: pembayaran table links to id_order (single). 
            // If one table has multiple menu items ordered, they are multiple rows in 'orders'.
            // Strategy: We create one payment record per 'order row' OR we treat 'id_order' as a Transaction Group ID?
            // Checking schema picture: orders(id_order PK), pembayaran(id_pembayaran PK, id_order FK).
            // It seems 1 order row = 1 payment row? Or 1 Order Header?
            // The table `orders` seems to be distinct items (id_menu, jumlah). This is not header-detail.
            // So multiple rows for one table.
            
            // To make it simple and robust: We pay ALL items for that table.
            // We'll insert multiple payment records or just mark them paid.
            // Let's assume we insert one payment record for the first order found, or modify schema. 
            // BUT I cannot modify schema easily without losing data or risking error.
            // WORKAROUND: Insert payment record for EACH order item row found.
            
            $sqlGetOrders = "SELECT id_order, (m.harga * o.jumlah) as subtotal 
                             FROM orders o 
                             JOIN menu m ON o.id_menu = m.id_menu 
                             WHERE o.id_meja = $id_meja AND o.status = 'BELUM_BAYAR'";
            
            $resOrders = $connect->query($sqlGetOrders);
            
            while ($row = $resOrders->fetch_assoc()) {
                 $oid = $row['id_order'];
                 $sub = $row['subtotal'];
                 
                 $stmtP = $connect->prepare("INSERT INTO pembayaran (id_order, total, metode, tanggal_bayar) VALUES (?, ?, ?, ?)");
                 $stmtP->bind_param("iiss", $oid, $sub, $metode, $tanggal);
                 $stmtP->execute();
                 
                 // Update Order Status
                 $connect->query("UPDATE orders SET status='SUDAH_BAYAR' WHERE id_order=$oid");
            }
            
            // Release Table
             $connect->query("UPDATE meja SET status='kosong' WHERE id_meja=$id_meja");
             
             $connect->commit();
             echo json_encode(['status' => 'success', 'msg' => 'Pembayaran berhasil', 'total_bayar' => $total]);

        } catch (Exception $e) {
            $connect->rollback();
            echo json_encode(['status' => 'fail', 'msg' => 'Error: ' . $e->getMessage()]);
        }
        break;
}
?>
