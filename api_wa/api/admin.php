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

// Cek login & role admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(["status" => false, "message" => "Akses ditolak! Khusus admin."]);
    exit;
}

// 🔥 FIX: Baca action dari JSON atau POST
$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['action'])) {
    $action = $input['action'];
} else {
    $action = $_POST['action'] ?? '';
}

// ============================================================
// GET ALL USERS (hanya role = 'user')
// ============================================================
if ($action === 'get_all') {
    $result = $conn->query("SELECT id, nama, email, no_hp, alamat, role, created_at, last_login 
                            FROM users 
                            WHERE role = 'user' 
                            ORDER BY id DESC");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode(["status" => true, "data" => $users]);
    exit;
}

// ============================================================
// CREATE - TAMBAH USER
// ============================================================
if ($action === 'create') {
    $nama = trim($input['nama'] ?? '');
    $email = trim($input['email'] ?? '');
    $no_hp = trim($input['no_hp'] ?? '');
    $alamat = trim($input['alamat'] ?? '');
    $role = $input['role'] ?? 'user';
    $password = $input['password'] ?? '';

    if (empty($nama) || empty($email) || empty($no_hp) || empty($password)) {
        echo json_encode(["status" => false, "message" => "Data tidak lengkap"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => false, "message" => "Format email tidak valid"]);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(["status" => false, "message" => "Password minimal 6 karakter"]);
        exit;
    }

    if (!in_array($role, ['admin', 'user'])) {
        $role = 'user';
    }

    $no_hp = formatNomor($no_hp);

    // Cek duplikasi email
    $cekEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $cekEmail->bind_param("s", $email);
    $cekEmail->execute();
    if ($cekEmail->get_result()->num_rows > 0) {
        echo json_encode(["status" => false, "message" => "Email sudah terdaftar"]);
        exit;
    }

    // Cek duplikasi nomor
    $cekNomor = $conn->prepare("SELECT id FROM users WHERE no_hp = ?");
    $cekNomor->bind_param("s", $no_hp);
    $cekNomor->execute();
    if ($cekNomor->get_result()->num_rows > 0) {
        echo json_encode(["status" => false, "message" => "Nomor WhatsApp sudah terdaftar"]);
        exit;
    }

    // Insert
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (nama, email, no_hp, alamat, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nama, $email, $no_hp, $alamat, $passwordHash, $role);

    if (!$stmt->execute()) {
        echo json_encode(["status" => false, "message" => "Gagal: " . $conn->error]);
        exit;
    }

    $new_id = $conn->insert_id;

    // Kirim WA
    $pesanWA = "🎉 *AKUN ANDA DIBUAT ADMIN* 🎉\n\n" .
               "Halo *$nama*! 👋\n\n" .
               "Akun Anda telah dibuat oleh admin.\n\n" .
               "📧 Email    : $email\n" .
               "📱 WhatsApp : $no_hp\n" .
               "👤 Role     : " . strtoupper($role) . "\n\n" .
               "Silakan login di sistem.\n\n" .
               "🕐 " . date('d-m-Y H:i:s');

    $hasilWA = kirimWhatsApp($no_hp, $pesanWA);
    simpanLogWA($conn, $new_id, $no_hp, $pesanWA, 'register', $hasilWA);

    echo json_encode([
        "status" => true,
        "message" => "User berhasil ditambahkan!",
        "whatsapp" => ["status" => $hasilWA['status'] ? "terkirim" : "gagal"]
    ]);
    exit;
}

// ============================================================
// UPDATE - EDIT USER
// ============================================================
if ($action === 'update') {
    $id = intval($input['id'] ?? 0);
    $nama = trim($input['nama'] ?? '');
    $email = trim($input['email'] ?? '');
    $no_hp = trim($input['no_hp'] ?? '');
    $alamat = trim($input['alamat'] ?? '');
    $role = $input['role'] ?? 'user';
    $password = $input['password'] ?? '';

    if (empty($id) || empty($nama) || empty($email) || empty($no_hp)) {
        echo json_encode(["status" => false, "message" => "Data tidak lengkap"]);
        exit;
    }

    // Ambil data lama
    $stmt = $conn->prepare("SELECT nama, email, no_hp, alamat, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();

    if (!$old) {
        echo json_encode(["status" => false, "message" => "User tidak ditemukan"]);
        exit;
    }

    // Cek duplikasi email
    $cekEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $cekEmail->bind_param("si", $email, $id);
    $cekEmail->execute();
    if ($cekEmail->get_result()->num_rows > 0) {
        echo json_encode(["status" => false, "message" => "Email sudah digunakan user lain"]);
        exit;
    }

    // Cek duplikasi nomor
    $cekNomor = $conn->prepare("SELECT id FROM users WHERE no_hp = ? AND id != ?");
    $cekNomor->bind_param("si", $no_hp, $id);
    $cekNomor->execute();
    if ($cekNomor->get_result()->num_rows > 0) {
        echo json_encode(["status" => false, "message" => "Nomor WhatsApp sudah digunakan user lain"]);
        exit;
    }

    $no_hp = formatNomor($no_hp);

    // Daftar perubahan
    $perubahan = "";
    if ($old['nama'] !== $nama) $perubahan .= "👤 Nama: {$old['nama']} → $nama\n";
    if ($old['email'] !== $email) $perubahan .= "📧 Email: {$old['email']} → $email\n";
    if ($old['no_hp'] !== $no_hp) $perubahan .= "📱 No HP: {$old['no_hp']} → $no_hp\n";
    if (($old['alamat'] ?? '') !== $alamat) $perubahan .= "📍 Alamat: {$old['alamat']} → $alamat\n";
    if ($old['role'] !== $role) $perubahan .= "👤 Role: {$old['role']} → $role\n";
    if (!empty($password) && strlen($password) >= 6) $perubahan .= "🔑 Password: (diubah)\n";
    if (empty($perubahan)) $perubahan = "(Tidak ada perubahan data)";

    // Update
    if (!empty($password) && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ?, role = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $nama, $email, $no_hp, $alamat, $role, $hash, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $nama, $email, $no_hp, $alamat, $role, $id);
    }

    if (!$stmt->execute()) {
        echo json_encode(["status" => false, "message" => "Gagal update: " . $conn->error]);
        exit;
    }

    // Kirim WA
    $pesanWA  = "✏️ *DATA ANDA DIUBAH ADMIN*\n\n";
    $pesanWA .= "Halo *$nama*,\n\n";
    $pesanWA .= "Data akun Anda telah diubah oleh admin.\n\n";
    $pesanWA .= "📋 *Detail Perubahan:*\n";
    $pesanWA .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesanWA .= $perubahan . "\n";
    $pesanWA .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    $pesanWA .= "🕐 " . date('d-m-Y H:i:s');

    $hasilWA = kirimWhatsApp($no_hp, $pesanWA);
    simpanLogWA($conn, $id, $no_hp, $pesanWA, 'update', $hasilWA);

    echo json_encode([
        "status" => true,
        "message" => "User berhasil diupdate!",
        "whatsapp" => ["status" => $hasilWA['status'] ? "terkirim" : "gagal"]
    ]);
    exit;
}

// ============================================================
// DELETE - HAPUS USER
// ============================================================
if ($action === 'delete') {
    $id = intval($input['id'] ?? 0);

    if (empty($id)) {
        echo json_encode(["status" => false, "message" => "ID tidak valid"]);
        exit;
    }

    // Cegah hapus diri sendiri
    if ($id == $_SESSION['user_id']) {
        echo json_encode(["status" => false, "message" => "Tidak bisa menghapus akun sendiri"]);
        exit;
    }

    // Ambil data user
    $stmt = $conn->prepare("SELECT nama, no_hp FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        echo json_encode(["status" => false, "message" => "User tidak ditemukan"]);
        exit;
    }

    // Kirim WA
    $pesanWA = "🗑️ *AKUN ANDA DIHAPUS ADMIN*\n\n" .
               "Halo *{$user['nama']}*,\n\n" .
               "Akun Anda telah dihapus dari sistem oleh admin.\n\n" .
               "🕐 " . date('d-m-Y H:i:s') . "\n\n" .
               "Terima kasih telah menggunakan layanan kami. 🙏";

    $hasilWA = kirimWhatsApp($user['no_hp'], $pesanWA);
    simpanLogWA($conn, $id, $user['no_hp'], $pesanWA, 'delete', $hasilWA);

    // Hapus
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => true,
            "message" => "User berhasil dihapus!",
            "whatsapp" => ["status" => $hasilWA['status'] ? "terkirim" : "gagal"]
        ]);
    } else {
        echo json_encode(["status" => false, "message" => "Gagal menghapus"]);
    }
    exit;
}

echo json_encode(["status" => false, "message" => "Action tidak valid"]);
?>