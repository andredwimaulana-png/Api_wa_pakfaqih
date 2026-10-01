<?php
require_once __DIR__ . "/../config/fonnte.php";

/**
 * Format nomor WhatsApp ke format internasional
 * 081234567890 → 6281234567890
 */
function formatNomor($nomor) {
    $nomor = preg_replace('/[^0-9]/', '', $nomor);
    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    }
    if (substr($nomor, 0, 2) !== '62') {
        $nomor = '62' . $nomor;
    }
    return $nomor;
}

/**
 * Kirim pesan WhatsApp via Fonnte
 */
function kirimWhatsApp($nomor, $pesan) {
    global $fonnte_token, $fonnte_url;
    
    $nomor = formatNomor($nomor);
    
    $data = [
        'target' => $nomor,
        'message' => $pesan,
        'countryCode' => '62'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fonnte_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $fonnte_token
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    return [
        'status' => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'message' => $responseData['message'] ?? ($responseData['reason'] ?? 'OK'),
        'response' => $responseData,
        'error' => $error
    ];
}

// ============================================================
//  TEMPLATE PESAN WHATSAPP
// ============================================================

/**
 * Pesan Registrasi Berhasil
 */
function pesanRegistrasi($nama, $email, $no_hp) {
    return "🎉 *REGISTRASI BERHASIL!* 🎉\n\n" .
           "Halo *$nama*! 👋\n\n" .
           "Akun Anda telah berhasil dibuat.\n\n" .
           "📧 Email    : $email\n" .
           "📱 WhatsApp : $no_hp\n\n" .
           "Terima kasih telah mendaftar! 🙏\n\n" .
           "_Pesan ini dikirim otomatis oleh sistem._";
}

/**
 * Pesan Login Berhasil
 */
function pesanLogin($nama, $role) {
    return "🔐 *ANDA BERHASIL LOGIN!*\n\n" .
           "Halo *$nama*! 👋\n\n" .
           "Anda baru saja login ke sistem.\n\n" .
           "👤 Role  : " . strtoupper($role) . "\n" .
           "🕐 Waktu : " . date('d-m-Y H:i:s') . "\n\n" .
           "Jika ini bukan Anda, segera hubungi admin! 🔒";
}

/**
 * Pesan Data Berhasil Diubah
 */
function pesanUpdate($nama, $perubahan) {
    $pesan  = "✏️ *DATA ANDA BERHASIL DIUBAH!*\n\n";
    $pesan .= "Halo *$nama*! 👋\n\n";
    $pesan .= "Data profil Anda telah berhasil diperbarui.\n\n";
    $pesan .= "📋 *Detail Perubahan:*\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n";
    $pesan .= $perubahan . "\n";
    $pesan .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    $pesan .= "🕐 Waktu: " . date('d-m-Y H:i:s') . "\n\n";
    $pesan .= "Jika ini bukan Anda, segera hubungi admin! 🔒";
    return $pesan;
}

/**
 * Pesan Akun Dihapus
 */
function pesanHapus($nama) {
    return "🗑️ *AKUN TELAH DIHAPUS!*\n\n" .
           "Halo *$nama*,\n\n" .
           "Akun Anda telah dihapus dari sistem.\n\n" .
           "🕐 Waktu: " . date('d-m-Y H:i:s') . "\n\n" .
           "Terima kasih telah menggunakan layanan kami. 🙏";
}

/**
 * Pesan Notifikasi ke Admin - User Baru
 */
function pesanUserBaru($nama_user, $email_user, $no_hp_user, $total_user) {
    return "👤 *USER BARU TERDAFTAR!* 👤\n\n" .
           "Ada user baru yang mendaftar di sistem.\n\n" .
           "📋 *Detail User Baru:*\n" .
           "━━━━━━━━━━━━━━━━━━━━\n" .
           "👤 Nama     : $nama_user\n" .
           "📧 Email    : $email_user\n" .
           "📱 WhatsApp : $no_hp_user\n" .
           "━━━━━━━━━━━━━━━━━━━━\n\n" .
           "👥 Total User: $total_user orang\n" .
           "🕐 Waktu: " . date('d-m-Y H:i:s') . "\n\n" .
           "Silakan cek dashboard admin untuk detail.";
}

/**
 * Ambil semua nomor admin
 */
function getAdminPhones($conn) {
    // Ambil admin pertama saja (by ID ASC) untuk hindari notif dobel
    $result = $conn->query("SELECT no_hp FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
    $phones = [];
    while ($row = $result->fetch_assoc()) {
        $phones[] = $row['no_hp'];
    }
    return $phones;
}

/**
 * Simpan log WhatsApp ke database
 */
function simpanLogWA($conn, $user_id, $no_tujuan, $pesan, $jenis, $hasilWA) {
    $status = $hasilWA['status'] ? 'success' : 'failed';
    $response = json_encode($hasilWA);
    
    $stmt = $conn->prepare("INSERT INTO log_whatsapp (user_id, no_tujuan, pesan, jenis, status, response) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssss", $user_id, $no_tujuan, $pesan, $jenis, $status, $response);
        $stmt->execute();
    }
}
?>