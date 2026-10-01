<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../functions/fonnte.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => false, "message" => "Gunakan method POST"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
} else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
}

if (empty($email) || empty($password)) {
    echo json_encode(["status" => false, "message" => "Email dan password wajib diisi"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, nama, email, no_hp, alamat, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => false, "message" => "Email tidak ditemukan"]);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    echo json_encode(["status" => false, "message" => "Password salah"]);
    exit;
}

// Update last_login
$updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$updateStmt->bind_param("i", $user['id']);
$updateStmt->execute();

// Set session
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_nama']  = $user['nama'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_hp']    = $user['no_hp'];
$_SESSION['user_role']  = $user['role'];

// 🔥 KIRIM NOTIF WA: LOGIN BERHASIL
$pesanWA = pesanLogin($user['nama'], $user['role']);
$hasilWA = kirimWhatsApp($user['no_hp'], $pesanWA);
simpanLogWA($conn, $user['id'], $user['no_hp'], $pesanWA, 'login', $hasilWA);

echo json_encode([
    "status" => true,
    "message" => "Login berhasil!",
    "data" => [
        "id" => $user['id'],
        "nama" => $user['nama'],
        "email" => $user['email'],
        "no_hp" => $user['no_hp'],
        "alamat" => $user['alamat'] ?? '',
        "role" => $user['role']
    ],
    "whatsapp" => [
        "status" => $hasilWA['status'] ? "terkirim" : "gagal"
    ]
]);
?>