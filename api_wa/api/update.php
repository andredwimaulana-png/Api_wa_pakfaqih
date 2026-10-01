<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../functions/fonnte.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST" && $_SERVER["REQUEST_METHOD"] !== "PUT") {
    echo json_encode(["status" => false, "message" => "Gunakan method POST atau PUT"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $id = $input['id'] ?? 0;
    $nama = trim($input['nama'] ?? '');
    $email = trim($input['email'] ?? '');
    $no_hp = trim($input['no_hp'] ?? '');
    $alamat = trim($input['alamat'] ?? '');
    $password = $input['password'] ?? '';
} else {
    $id = $_POST['id'] ?? 0;
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
}

if (empty($id) || empty($nama) || empty($email) || empty($no_hp)) {
    echo json_encode(["status" => false, "message" => "Data tidak lengkap"]);
    exit;
}

// Ambil data lama
$cekUser = $conn->prepare("SELECT id, nama, email, no_hp, alamat FROM users WHERE id = ?");
$cekUser->bind_param("i", $id);
$cekUser->execute();
$userResult = $cekUser->get_result();

if ($userResult->num_rows === 0) {
    echo json_encode(["status" => false, "message" => "User tidak ditemukan"]);
    exit;
}

$userLama = $userResult->fetch_assoc();

// Cek duplikasi
$cekEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$cekEmail->bind_param("si", $email, $id);
$cekEmail->execute();
if ($cekEmail->get_result()->num_rows > 0) {
    echo json_encode(["status" => false, "message" => "Email sudah digunakan user lain"]);
    exit;
}

$cekNomor = $conn->prepare("SELECT id FROM users WHERE no_hp = ? AND id != ?");
$cekNomor->bind_param("si", $no_hp, $id);
$cekNomor->execute();
if ($cekNomor->get_result()->num_rows > 0) {
    echo json_encode(["status" => false, "message" => "Nomor WhatsApp sudah digunakan user lain"]);
    exit;
}

$no_hp = formatNomor($no_hp);

// Buat daftar perubahan
$perubahan = "";
if ($userLama['nama'] !== $nama) $perubahan .= "👤 Nama: {$userLama['nama']} → $nama\n";
if ($userLama['email'] !== $email) $perubahan .= "📧 Email: {$userLama['email']} → $email\n";
if ($userLama['no_hp'] !== $no_hp) $perubahan .= "📱 No HP: {$userLama['no_hp']} → $no_hp\n";
if (($userLama['alamat'] ?? '') !== $alamat) $perubahan .= "📍 Alamat: {$userLama['alamat']} → $alamat\n";
if (!empty($password) && strlen($password) >= 6) $perubahan .= "🔑 Password: (diubah)\n";
if (empty($perubahan)) $perubahan = "(Tidak ada perubahan data)";

// Update database
if (!empty($password) && strlen($password) >= 6) {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ?, password = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $nama, $email, $no_hp, $alamat, $passwordHash, $id);
} else {
    $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $nama, $email, $no_hp, $alamat, $id);
}

if (!$stmt->execute()) {
    echo json_encode(["status" => false, "message" => "Gagal update: " . $conn->error]);
    exit;
}

// 🔥 KIRIM NOTIF WA: DATA DIUBAH
$pesanWA = pesanUpdate($nama, $perubahan);
$hasilWA = kirimWhatsApp($no_hp, $pesanWA);
simpanLogWA($conn, $id, $no_hp, $pesanWA, 'update', $hasilWA);

echo json_encode([
    "status" => true,
    "message" => "Data berhasil diperbarui!",
    "whatsapp" => [
        "status" => $hasilWA['status'] ? "terkirim" : "gagal",
        "target" => $no_hp
    ]
]);
?>