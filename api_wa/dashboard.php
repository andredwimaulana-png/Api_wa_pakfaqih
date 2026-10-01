<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once __DIR__ . "/config/database.php";

$user_id = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];
$user_email = $_SESSION['user_email'];
$user_hp = $_SESSION['user_hp'];
$user_role = $_SESSION['user_role'];

// Ambil data user terbaru dari database
$stmt = $conn->prepare("SELECT id, nama, email, no_hp, alamat, role, created_at, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: login.html");
    exit();
}

// Format tanggal
$tglDaftar = $user['created_at'] ? date('d F Y', strtotime($user['created_at'])) : '-';
$tglLogin = $user['last_login'] ? date('d F Y, H:i', strtotime($user['last_login'])) : 'Belum pernah';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - API WhatsApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #25D366;
            --primary-dark: #128C7E;
            --blue: #2563EB;
            --purple: #7C3AED;
            --red: #DC2626;
            --border: rgba(255, 255, 255, 0.08);
            --text: #F1F5F9;
            --muted: #94A3B8;
        }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(145deg, #0F172A 0%, #1E293B 100%);
            color: var(--text);
            padding: 20px;
        }

        .navbar {
            max-width: 1200px;
            margin: 0 auto 24px;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 16px 28px;
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .brand {
            display: flex; align-items: center; gap: 12px;
            font-weight: 800; font-size: 20px;
            color: #FFF;
        }
        .brand i { color: var(--primary); font-size: 26px; }
        .brand .role-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .role-admin {
            background: rgba(124, 58, 237, 0.15);
            color: #A78BFA;
            border: 1px solid rgba(124, 58, 237, 0.3);
        }
        .role-user {
            background: rgba(37, 211, 102, 0.15);
            color: #34D399;
            border: 1px solid rgba(37, 211, 102, 0.3);
        }
        .navbar-right {
            display: flex; align-items: center; gap: 12px;
            flex-wrap: wrap;
        }
        .user-chip {
            padding: 8px 16px;
            background: rgba(37, 99, 235, 0.10);
            border: 1px solid rgba(37, 99, 235, 0.15);
            border-radius: 40px;
            font-size: 13px;
            display: flex; align-items: center; gap: 8px;
        }
        .user-chip i { color: #60A5FA; }
        .user-chip strong { color: #60A5FA; }

        .btn-logout {
            padding: 8px 18px;
            border-radius: 40px;
            background: rgba(220, 38, 38, 0.15);
            color: #F87171;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }
        .btn-logout:hover {
            background: rgba(220, 38, 38, 0.25);
            transform: translateY(-2px);
        }

        .container { max-width: 1200px; margin: 0 auto; }

        /* WELCOME CARD */
        .welcome {
            background: linear-gradient(135deg, rgba(37, 211, 102, 0.15), rgba(18, 140, 126, 0.05));
            border: 1px solid rgba(37, 211, 102, 0.2);
            border-radius: 24px;
            padding: 40px 36px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }
        .welcome::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(37, 211, 102, 0.15), transparent 70%);
            border-radius: 50%;
        }
        .welcome-content { position: relative; z-index: 1; }
        .welcome h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            color: #FFF;
        }
        .welcome h1 span {
            background: linear-gradient(135deg, #25D366, #34D399);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .welcome p {
            color: #94A3B8;
            font-size: 15px;
            margin-bottom: 20px;
        }
        .welcome-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 13px;
            color: #CBD5E1;
        }
        .welcome-info div {
            display: flex; align-items: center; gap: 8px;
        }
        .welcome-info i { color: #34D399; }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            display: flex; align-items: center; gap: 16px;
        }
        .stat-icon {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .stat-green { background: rgba(37, 211, 102, 0.15); color: #34D399; }
        .stat-blue { background: rgba(37, 99, 235, 0.15); color: #60A5FA; }
        .stat-purple { background: rgba(124, 58, 237, 0.15); color: #A78BFA; }
        .stat-orange { background: rgba(245, 158, 11, 0.15); color: #FBBF24; }
        .stat-info h3 {
            font-size: 22px; font-weight: 800; color: #FFF;
        }
        .stat-info p {
            font-size: 12px; color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* PROFIL CARD */
        .profil-section {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 32px 28px;
            margin-bottom: 24px;
        }
        .profil-header {
            display: flex; justify-content: space-between;
            align-items: center; flex-wrap: wrap; gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        .profil-header h2 {
            font-size: 20px; font-weight: 800; color: #FFF;
            display: flex; align-items: center; gap: 10px;
        }
        .profil-header h2 i { color: #34D399; }

        .profil-avatar {
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 24px;
        }
        .avatar-circle {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #25D366, #128C7E);
            display: flex; align-items: center; justify-content: center;
            font-size: 36px; font-weight: 800; color: white;
            flex-shrink: 0;
        }
        .avatar-info h3 {
            font-size: 22px; font-weight: 800; color: #FFF;
            margin-bottom: 4px;
        }
        .avatar-info p {
            color: #94A3B8; font-size: 14px;
        }
        .avatar-info .role-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .profil-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }
        .profil-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 14px;
            padding: 16px 20px;
            border: 1px solid var(--border);
        }
        .profil-item .label {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .profil-item .label i { color: #60A5FA; font-size: 12px; }
        .profil-item .value {
            font-size: 14px;
            color: #E2E8F0;
            font-weight: 500;
            word-break: break-word;
        }

        /* MENU GRID */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .menu-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px 24px;
            text-decoration: none;
            color: var(--text);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 12px;
            cursor: pointer;
        }
        .menu-card:hover {
            transform: translateY(-6px);
            border-color: rgba(37, 211, 102, 0.3);
            background: rgba(37, 211, 102, 0.05);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        .menu-icon {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }
        .icon-green { background: rgba(37, 211, 102, 0.15); color: #34D399; }
        .icon-blue { background: rgba(37, 99, 235, 0.15); color: #60A5FA; }
        .icon-purple { background: rgba(124, 58, 237, 0.15); color: #A78BFA; }
        .icon-red { background: rgba(220, 38, 38, 0.15); color: #F87171; }
        .menu-card h3 {
            font-size: 17px;
            font-weight: 700;
            color: #FFF;
        }
        .menu-card p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }

        /* TOAST */
        .toast {
            position: fixed; top: 24px; right: 24px;
            padding: 14px 24px; border-radius: 12px;
            font-weight: 600; font-size: 14px; color: white;
            transform: translateX(150%);
            transition: transform 0.4s ease;
            z-index: 2000;
            display: flex; align-items: center; gap: 10px;
        }
        .toast.show { transform: translateX(0); }
        .toast.success { background: linear-gradient(135deg, #10B981, #059669); }
        .toast.error { background: linear-gradient(135deg, #EF4444, #B91C1C); }
        .toast.info { background: linear-gradient(135deg, #2563EB, #7C3AED); }

        @media (max-width: 768px) {
            .navbar { padding: 14px 18px; }
            .welcome { padding: 28px 24px; }
            .welcome h1 { font-size: 24px; }
            .profil-section { padding: 24px 20px; }
            .profil-avatar { flex-direction: column; text-align: center; }
            .toast { top: 16px; right: 16px; left: 16px; }
        }
    </style>
</head>
<body>

<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMsg"></span>
</div>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="brand">
        <i class="fab fa-whatsapp"></i>
        <span>API WhatsApp</span>
        <span class="role-badge <?= $user_role === 'admin' ? 'role-admin' : 'role-user' ?>">
            <?= strtoupper($user_role) ?>
        </span>
    </div>
    <div class="navbar-right">
        <div class="user-chip">
            <i class="fas fa-user-circle"></i>
            Halo, <strong><?= htmlspecialchars($user_nama) ?></strong>
        </div>
        <a href="api/logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<div class="container">

    <!-- WELCOME -->
    <div class="welcome">
        <div class="welcome-content">
            <h1>Welcome Idiot <span><?= htmlspecialchars($user_nama) ?></span>! </h1>
            <p>Anda login sebagai <strong><?= strtoupper($user_role) ?></strong> sistem API WhatsApp</p>
            <div class="welcome-info">
                <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($user_email) ?></div>
                <div><i class="fab fa-whatsapp"></i> <?= htmlspecialchars($user_hp) ?></div>
                <div><i class="fas fa-clock"></i> <?= date('d-m-Y H:i') ?></div>
            </div>
        </div>
    </div>

    <!-- STATISTIK PRIBADI -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-green"><i class="fas fa-user-check"></i></div>
            <div class="stat-info">
                <h3>AKTIF</h3>
                <p>Status Akun</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-blue"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info">
                <h3><?= $tglDaftar ?></h3>
                <p>Tanggal Daftar</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-purple"><i class="fas fa-history"></i></div>
            <div class="stat-info">
                <h3 style="font-size:14px;"><?= $tglLogin ?></h3>
                <p>Login Terakhir</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-orange"><i class="fas fa-user-tag"></i></div>
            <div class="stat-info">
                <h3 style="font-size:18px;"><?= strtoupper($user_role) ?></h3>
                <p>Role Anda</p>
            </div>
        </div>
    </div>

    <!-- PROFIL PRIBADI -->
    <div class="profil-section">
        <div class="profil-header">
            <h2><i class="fas fa-id-card"></i> Profil Pribadi</h2>
        </div>

        <div class="profil-avatar">
            <div class="avatar-circle">
                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
            </div>
            <div class="avatar-info">
                <h3><?= htmlspecialchars($user['nama']) ?></h3>
                <p><?= htmlspecialchars($user['email']) ?></p>
                <span class="role-tag <?= $user_role === 'admin' ? 'role-admin' : 'role-user' ?>">
                    <?= strtoupper($user_role) ?>
                </span>
            </div>
        </div>

        <div class="profil-grid">
            <div class="profil-item">
                <div class="label"><i class="fas fa-user"></i> Nama Lengkap</div>
                <div class="value"><?= htmlspecialchars($user['nama']) ?></div>
            </div>
            <div class="profil-item">
                <div class="label"><i class="fas fa-envelope"></i> Email</div>
                <div class="value"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div class="profil-item">
                <div class="label"><i class="fab fa-whatsapp"></i> Nomor WhatsApp</div>
                <div class="value"><?= htmlspecialchars($user['no_hp']) ?></div>
            </div>
            <div class="profil-item">
                <div class="label"><i class="fas fa-map-pin"></i> Alamat</div>
                <div class="value"><?= htmlspecialchars($user['alamat'] ?: '-') ?></div>
            </div>
            <div class="profil-item">
                <div class="label"><i class="fas fa-calendar-plus"></i> Terdaftar Sejak</div>
                <div class="value"><?= $tglDaftar ?></div>
            </div>
            <div class="profil-item">
                <div class="label"><i class="fas fa-clock"></i> Login Terakhir</div>
                <div class="value"><?= $tglLogin ?></div>
            </div>
        </div>
    </div>

    <!-- MENU AKSI -->
    <div class="menu-grid">
        
        <a href="profil.php" class="menu-card">
            <div class="menu-icon icon-blue"><i class="fas fa-user-edit"></i></div>
            <h3>Edit Profil</h3>
            <p>Ubah nama, email, nomor WhatsApp, atau password Anda</p>
        </a>

        <?php if ($user_role === 'admin'): ?>
        <a href="admin.php" class="menu-card">
            <div class="menu-icon icon-purple"><i class="fas fa-users-cog"></i></div>
            <h3>Kelola User</h3>
            <p>Lihat, tambah, edit, dan hapus user terdaftar</p>
        </a>
        <?php endif; ?>

       <?php if ($user_role === 'admin'): ?>
<a href="test-wa.php" target="_blank" class="menu-card">
    <div class="menu-icon icon-green"><i class="fas fa-paper-plane"></i></div>
    <h3>Test Kirim WA</h3>
    <p>Cek koneksi WhatsApp Gateway Fonnte</p>
</a>
<?php endif; ?>
        <a href="api/logout.php" class="menu-card" onclick="return confirm('Yakin ingin logout?')">
            <div class="menu-icon icon-red"><i class="fas fa-sign-out-alt"></i></div>
            <h3>Logout</h3>
            <p>Keluar dari sistem dengan aman</p>
        </a>

    </div>

</div>

<script>
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    t.className = 'toast ' + type;
    void t.offsetWidth;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

// Cek notif dari URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('notif') === 'login_success') {
    showToast('✅ Login berhasil! Notifikasi WA terkirim.', 'success');
}
</script>

</body>
</html>