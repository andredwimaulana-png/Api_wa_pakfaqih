<?php
session_start();  // ← UNTUK AUTO LOGIN

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

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
    $nama = trim($input['nama'] ?? '');
    $email = trim($input['email'] ?? '');
    $no_hp = trim($input['no_hp'] ?? '');
    $alamat = trim($input['alamat'] ?? '');
    $password = $input['password'] ?? '';
} else {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
}

// Validasi
$errors = [];
if (empty($nama)) $errors[] = "Nama wajib diisi";
if (empty($email)) $errors[] = "Email wajib diisi";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid";
if (empty($no_hp)) $errors[] = "Nomor WhatsApp wajib diisi";
if (empty($password)) $errors[] = "Password wajib diisi";
if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter";

if (!empty($errors)) {
    echo json_encode(["status" => false, "message" => "Validasi gagal", "errors" => $errors]);
    exit;
}

$no_hp = formatNomor($no_hp);

// Cek duplikasi
$cekEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
$cekEmail->bind_param("s", $email);
$cekEmail->execute();
if ($cekEmail->get_result()->num_rows > 0) {
    echo json_encode(["status" => false, "message" => "Email sudah terdaftar"]);
    exit;
}

$cekNomor = $conn->prepare("SELECT id FROM users WHERE no_hp = ?");
$cekNomor->bind_param("s", $no_hp);
$cekNomor->execute();
if ($cekNomor->get_result()->num_rows > 0) {
    echo json_encode(["status" => false, "message" => "Nomor WhatsApp sudah terdaftar"]);
    exit;
}

// Simpan user
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (nama, email, no_hp, alamat, password, role) VALUES (?, ?, ?, ?, ?, 'user')");
$stmt->bind_param("sssss", $nama, $email, $no_hp, $alamat, $passwordHash);

if (!$stmt->execute()) {
    echo json_encode(["status" => false, "message" => "Registrasi gagal: " . $conn->error]);
    exit;
}

$user_id = $conn->insert_id;

// 🔥 1. KIRIM WA KE USER BARU
$pesanUser = pesanRegistrasi($nama, $email, $no_hp);
$hasilWA1 = kirimWhatsApp($no_hp, $pesanUser);
simpanLogWA($conn, $user_id, $no_hp, $pesanUser, 'register', $hasilWA1);

// 🔥 2. KIRIM WA KE SEMUA ADMIN
$totalUserQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$totalUser = $totalUserQuery->fetch_assoc()['total'];

$pesanAdmin = pesanUserBaru($nama, $email, $no_hp, $totalUser);
$adminPhones = getAdminPhones($conn);

foreach ($adminPhones as $adminPhone) {
    $hasilWA2 = kirimWhatsApp($adminPhone, $pesanAdmin);
    simpanLogWA($conn, $user_id, $adminPhone, $pesanAdmin, 'admin_notif', $hasilWA2);
}

// 🔥 3. AUTO LOGIN - SET SESSION
$_SESSION['user_id']    = $user_id;
$_SESSION['user_nama']  = $nama;
$_SESSION['user_email'] = $email;
$_SESSION['user_hp']    = $no_hp;
$_SESSION['user_role']  = 'user';

echo json_encode([
    "status" => true,
    "message" => "Registrasi berhasil! Mengarahkan ke dashboard...",
    "data" => [
        "id" => $user_id,
        "nama" => $nama,
        "email" => $email,
        "no_hp" => $no_hp,
        "alamat" => $alamat,
        "role" => "user"
    ],
    "whatsapp" => [
        "status" => $hasilWA1['status'] ? "terkirim" : "gagal"
    ],
    "redirect" => "dashboard.php"
]);
?>