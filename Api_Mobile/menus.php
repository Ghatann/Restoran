<?php
include 'connection.php';
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

// Helper function to handle image upload
function handleUpload($existingFoto = null) {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileExtension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
        $targetFile = $targetDir . $newFileName;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
            // Optional: delete old file if it exists and is different
            // if ($existingFoto && file_exists($targetDir . $existingFoto)) unlink($targetDir . $existingFoto);
            return $newFileName;
        }
    }
    return $existingFoto;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id   = intval($_GET['id']);
            $stmt = $connect->prepare(
                "SELECT m.*, k.nama_kategori 
                 FROM menus m 
                 LEFT JOIN kategoris k ON m.id_kategori = k.id 
                 WHERE m.id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
            } else {
                echo json_encode(['status' => 'fail', 'msg' => 'Menu tidak ditemukan']);
            }
        } else {
            if (isset($_GET['id_kategori'])) {
                $kid  = intval($_GET['id_kategori']);
                $stmt = $connect->prepare(
                    "SELECT m.*, k.nama_kategori 
                     FROM menus m 
                     LEFT JOIN kategoris k ON m.id_kategori = k.id 
                     WHERE m.id_kategori = ? 
                     ORDER BY m.nama_menu ASC"
                );
                $stmt->bind_param("i", $kid);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $connect->query(
                    "SELECT m.*, k.nama_kategori 
                     FROM menus m 
                     LEFT JOIN kategoris k ON m.id_kategori = k.id 
                     ORDER BY m.nama_menu ASC"
                );
            }
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            echo json_encode(['status' => 'success', 'data' => $rows]);
        }
        break;

    case 'POST':
        // Handle multipart/form-data for image uploads
        $id        = isset($_POST['id']) ? intval($_POST['id']) : null;
        $nama      = $_POST['nama_menu'] ?? null;
        $harga     = isset($_POST['harga']) ? intval($_POST['harga']) : null;
        $id_kat    = isset($_POST['id_kategori']) ? intval($_POST['id_kategori']) : null;
        $stok      = isset($_POST['stok_porsi']) ? intval($_POST['stok_porsi']) : 0;
        $deskripsi = $_POST['deskripsi'] ?? null;
        $status    = $_POST['status'] ?? 'available';
        $now       = date('Y-m-d H:i:s');

        if (!$nama || !$harga) {
            echo json_encode(['status' => 'fail', 'msg' => 'Data tidak lengkap (nama_menu, harga)']);
            exit;
        }

        if ($id) {
            // ACTION: UPDATE
            // Fetch existing foto
            $res = $connect->query("SELECT foto FROM menus WHERE id=$id");
            $old = $res->fetch_assoc();
            $foto = handleUpload($old['foto']);

            $stmt = $connect->prepare(
                "UPDATE menus SET nama_menu=?, harga=?, id_kategori=?, stok_porsi=?, foto=?, deskripsi=?, status=?, updated_at=? WHERE id=?"
            );
            $stmt->bind_param("siiissssi", $nama, $harga, $id_kat, $stok, $foto, $deskripsi, $status, $now, $id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'msg' => 'Menu diupdate']);
            } else {
                echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
            }
        } else {
            // ACTION: INSERT
            $foto = handleUpload();

            $stmt = $connect->prepare(
                "INSERT INTO menus (nama_menu, harga, id_kategori, stok_porsi, foto, deskripsi, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("siiisssss", $nama, $harga, $id_kat, $stok, $foto, $deskripsi, $status, $now, $now);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'msg' => 'Menu ditambahkan', 'id' => $connect->insert_id]);
            } else {
                echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
            }
        }
        break;

    case 'PUT':
        // Note: PHP natively doesn't handle multipart/form-data with PUT.
        // Usually, we use POST with a _method=PUT field or just use POST for updates with files.
        // But for simplicity in hybrid systems, some people use a specific header or just POST.
        // Since we are using axios/fetch, a better approach for files is using POST for updates.
        // Let's adapt this to check if it's a POST with an ID instead.
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $data = $_POST; // Use $_POST if it's a multipart update
        }

        if (!isset($data['id'])) {
            echo json_encode(['status' => 'fail', 'msg' => 'ID diperlukan']);
            exit;
        }

        $id        = intval($data['id']);
        $nama      = $data['nama_menu']  ?? null;
        $harga     = isset($data['harga']) ? intval($data['harga']) : null;
        $id_kat    = isset($data['id_kategori']) ? intval($data['id_kategori']) : null;
        $stok      = isset($data['stok_porsi']) ? intval($data['stok_porsi']) : null;
        $deskripsi = $data['deskripsi']  ?? null;
        $status    = $data['status']     ?? null;
        $now       = date('Y-m-d H:i:s');

        // Fetch existing foto
        $res = $connect->query("SELECT foto FROM menus WHERE id=$id");
        $old = $res->fetch_assoc();
        $foto = handleUpload($old['foto']);

        $stmt = $connect->prepare(
            "UPDATE menus SET nama_menu=?, harga=?, id_kategori=?, stok_porsi=?, foto=?, deskripsi=?, status=?, updated_at=? WHERE id=?"
        );
        $stmt->bind_param("siiissssi", $nama, $harga, $id_kat, $stok, $foto, $deskripsi, $status, $now, $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Menu diupdate']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = intval($data['id']);
        $stmt = $connect->prepare("DELETE FROM menus WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'msg' => 'Menu dihapus']);
        } else {
            echo json_encode(['status' => 'fail', 'msg' => $connect->error]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 'fail', 'msg' => 'Method not allowed']);
}
?>
