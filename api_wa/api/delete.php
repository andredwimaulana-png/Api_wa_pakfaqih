<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../functions/fonnte.php";

// Terima DELETE atau POST
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'DELETE' && $method !== 'POST') {
    echo json_encode(["status" => false, "message" => "Gunakan method DELETE"]);
    exit;
}

// Ambil ID
$id = 0;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
}

if (empty($id)) {
    echo json_encode(["status" => false, "message" => "ID tidak ditemukan"]);
    exit;
}

// Ambil data user
$getUser = $conn->prepare("SELECT id, nama, email, no_hp FROM users WHERE id = ?");
$getUser->bind_param("i", $id);
$getUser->execute();
$userResult = $getUser->get_result();

if ($userResult->num_rows === 0) {
    echo json_encode(["status" => false, "message" => "User tidak ditemukan"]);
    exit;
}

$user = $userResult->fetch_assoc();

// Kirim WA
$pesanWA = pesanHapus($user['nama']);
$hasilWA = kirimWhatsApp($user['no_hp'], $pesanWA);
simpanLogWA($conn, $id, $user['no_hp'], $pesanWA, 'delete', $hasilWA);

// Hapus data
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Akun berhasil dihapus!",
        "whatsapp" => [
            "status" => $hasilWA['status'] ? "terkirim" : "gagal"
        ],
        "deleted" => true
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Gagal menghapus akun: " . $conn->error
    ]);
}
?>