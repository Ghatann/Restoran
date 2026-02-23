<?php
include 'connection.php';
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'msg' => 'Only POST method allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email'], $data['password'])) {
    echo json_encode(['status' => 'fail', 'msg' => 'Email dan password harus diisi']);
    exit;
}

$email    = $data['email'];
$password = $data['password'];

$stmt = $connect->prepare(
    "SELECT u.id, u.username, u.email, u.password, u.role, u.id_karyawan, k.nama_karyawan
     FROM users u
     LEFT JOIN karyawans k ON u.id_karyawan = k.id
     WHERE u.email = ?"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'fail', 'msg' => 'Email tidak ditemukan']);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'fail', 'msg' => 'Password salah']);
    exit;
}

unset($user['password']); // jangan kirim hash ke client

echo json_encode([
    'status' => 'success',
    'msg'    => 'Login berhasil',
    'data'   => $user   // berisi: id, username, email, role, id_karyawan, nama_karyawan
]);
?>