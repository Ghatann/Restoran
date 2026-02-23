<?php
include 'connection.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ─── GET ─────────────────────────────────────────────────────────────────
    case 'GET':
        if (isset($_GET['id'])) {
            // Single order with items
            $id   = intval($_GET['id']);
            $stmt = $connect->prepare(
                "SELECT o.*, u.username, mj.nomor_meja
                 FROM orderans o
                 LEFT JOIN users u  ON o.id_user = u.id
                 LEFT JOIN mejas mj ON o.id_meja  = mj.id
                 WHERE o.id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'fail', 'msg' => 'Order tidak ditemukan']);
                exit;
            }

            $order = $result->fetch_assoc();
            $dstmt = $connect->prepare(
                "SELECT d.*, m.nama_menu, m.harga, m.foto
                 FROM detail_orderans d
                 JOIN menus m ON d.id_menu = m.id
                 WHERE d.id_orderan = ?"
            );
            $dstmt->bind_param("i", $order['id']);
            $dstmt->execute();
            $dresult = $dstmt->get_result();
            $items = [];
            while ($r = $dresult->fetch_assoc()) $items[] = $r;
            $order['items'] = $items;
            echo json_encode(['status' => 'success', 'data' => $order]);

        } elseif (isset($_GET['id_meja'])) {
            // Orders for a specific table that are pending
            $id_meja = intval($_GET['id_meja']);
            $stmt = $connect->prepare(
                "SELECT o.*, mj.nomor_meja
                 FROM orderans o
                 JOIN mejas mj ON o.id_meja = mj.id
                 WHERE o.id_meja = ? AND o.status = 'pending'
                 ORDER BY o.tanggal_orderan DESC"
            );
            $stmt->bind_param("i", $id_meja);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                // Get items for each order
                $dstmt = $connect->prepare(
                    "SELECT d.*, m.nama_menu, m.harga, m.foto
                     FROM detail_orderans d
                     JOIN menus m ON d.id_menu = m.id
                     WHERE d.id_orderan = ?"
                );
                $dstmt->bind_param("i", $row['id']);
                $dstmt->execute();
                $dresult = $dstmt->get_result();
                $items = [];
                while ($r = $dresult->fetch_assoc()) $items[] = $r;
                $row['items'] = $items;
                $rows[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $rows]);

        } else {
            // All orders
            $result = $connect->query(
                "SELECT o.*, u.username, mj.nomor_meja
                 FROM orderans o
                 LEFT JOIN users u  ON o.id_user = u.id
                 LEFT JOIN mejas mj ON o.id_meja  = mj.id
                 ORDER BY o.tanggal_orderan DESC"
            );
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            echo json_encode(['status' => 'success', 'data' => $rows]);
        }
        break;

    // ─── POST ─────────────────────────────────────────────────────────────────
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id_meja'], $data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
            echo json_encode(['status' => 'fail', 'msg' => 'Data tidak lengkap (id_meja, items[])']);
            exit;
        }

        $id_meja       = intval($data['id_meja']);
        $id_user       = isset($data['id_user']) ? intval($data['id_user']) : null;
        $nama_konsumen = $data['nama_konsumen']    ?? null;
        $metode        = $data['metode_pembayaran'] ?? 'cash';
        $mode_global   = $data['mode_pesanan']      ?? 'dinein';
        $now           = date('Y-m-d H:i:s');

        $connect->begin_transaction();
        try {
            // Fetch price for each item and compute total
            $total     = 0;
            $itemsData = [];
            foreach ($data['items'] as $item) {
                $id_menu = intval($item['id_menu']);
                $jumlah  = max(1, intval($item['jumlah']));

                $stmtH = $connect->prepare("SELECT harga FROM menus WHERE id = ?");
                $stmtH->bind_param("i", $id_menu);
                $stmtH->execute();
                $rH    = $stmtH->get_result()->fetch_assoc();
                if (!$rH) throw new Exception("Menu id=$id_menu tidak ditemukan");

                $harga    = intval($rH['harga']);
                $subtotal = $harga * $jumlah;
                $total   += $subtotal;

                $itemsData[] = [
                    'id_menu'  => $id_menu,
                    'jumlah'   => $jumlah,
                    'catatan'  => $item['catatan'] ?? null,
                    'mode'     => $item['mode_pesanan'] ?? $mode_global,
                    'subtotal' => $subtotal,
                ];
            }

            // Insert orderan header
            // Columns: nama_konsumen(s), total_bayar(i), tanggal_orderan(s), status(literal),
            //          id_user(i), id_meja(i), metode_pembayaran(s), created_at(s), updated_at(s)
            $stmtO = $connect->prepare(
                "INSERT INTO orderans
                 (nama_konsumen, total_bayar, tanggal_orderan, status, id_user, id_meja, metode_pembayaran, created_at, updated_at)
                 VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?)"
            );
            $stmtO->bind_param("sisiisss",
                $nama_konsumen,   // s
                $total,           // i
                $now,             // s  (tanggal_orderan)
                $id_user,         // i
                $id_meja,         // i
                $metode,          // s
                $now,             // s  (created_at)
                $now              // s  (updated_at)
            );
            if (!$stmtO->execute()) throw new Exception($connect->error);
            $id_orderan = $connect->insert_id;

            // Insert detail items & Update Stock
            foreach ($itemsData as $it) {
                // 1. Reduce Stock
                $stmtU = $connect->prepare("UPDATE menus SET stok_porsi = stok_porsi - ? WHERE id = ? AND stok_porsi >= ?");
                $stmtU->bind_param("iii", $it['jumlah'], $it['id_menu'], $it['jumlah']);
                if (!$stmtU->execute() || $stmtU->affected_rows === 0) {
                    throw new Exception("Stok tidak mencukupi untuk menu ID: " . $it['id_menu']);
                }

                // 2. Insert Detail
                $stmtD = $connect->prepare(
                    "INSERT INTO detail_orderans
                     (id_orderan, id_menu, jumlah, mode_pesanan, catatan, status, subtotal, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 'processing', ?, ?, ?)"
                );
                $stmtD->bind_param("iiississ",
                    $id_orderan,      // i
                    $it['id_menu'],   // i
                    $it['jumlah'],    // i
                    $it['mode'],      // s
                    $it['catatan'],   // s
                    $it['subtotal'],  // i
                    $now,             // s  (created_at)
                    $now              // s  (updated_at)
                );
                if (!$stmtD->execute()) throw new Exception($connect->error);
            }

            // Mark table as booking
            $stmtM = $connect->prepare("UPDATE mejas SET status='booking', updated_at=? WHERE id=?");
            $stmtM->bind_param("si", $now, $id_meja);
            $stmtM->execute();

            $connect->commit();
            echo json_encode([
                'status'     => 'success',
                'msg'        => 'Order berhasil dibuat',
                'id_orderan' => $id_orderan,
                'total'      => $total,
            ]);

        } catch (Exception $e) {
            $connect->rollback();
            echo json_encode(['status' => 'fail', 'msg' => 'Gagal: ' . $e->getMessage()]);
        }
        break;

    // ─── PUT ─────────────────────────────────────────────────────────────────
    case 'PUT':
        $data   = json_decode(file_get_contents('php://input'), true);
        $id     = intval($data['id'] ?? 0);
        $status = $data['status'] ?? '';
        $now    = date('Y-m-d H:i:s');

        if (!$id || !$status) {
            echo json_encode(['status' => 'fail', 'msg' => 'id dan status diperlukan']);
            exit;
        }

        $stmt = $connect->prepare("UPDATE orderans SET status=?, updated_at=? WHERE id=?");
        $stmt->bind_param("ssi", $status, $now, $id);

        if ($stmt->execute()) {
            // Free the table when order is paid/cancelled
            if (in_array($status, ['dibayar', 'batal'])) {
                $res    = $connect->query("SELECT id_meja FROM orderans WHERE id=$id");
                $row    = $res->fetch_assoc();
                $idMeja = intval($row['id_meja'] ?? 0);
                if ($idMeja) {
                    $stmtM = $connect->prepare("UPDATE mejas SET status='available', updated_at=? WHERE id=?");
                    $stmtM->bind_param("si", $now, $idMeja);
                    $stmtM->execute();
                }
            }
            echo json_encode(['status' => 'success', 'msg' => 'Status order diupdate']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    // ─── DELETE ───────────────────────────────────────────────────────────────
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = intval($data['id'] ?? 0);
        $stmt = $connect->prepare("DELETE FROM orderans WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Order dihapus']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'fail', 'msg' => 'Method not allowed']);
}
?>
