<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require_once __DIR__ . "/config/database.php";

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, nama, email, no_hp, alamat, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - API WhatsApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(145deg, #0F172A 0%, #1E293B 100%);
            color: #F1F5F9;
            padding: 20px;
        }
        .container {
            max-width: 560px; margin: 0 auto;
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 36px;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        }
        .header { text-align: center; margin-bottom: 32px; }
        .header .icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #2563EB, #7C3AED);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px; font-size: 32px; color: white;
        }
        .header h1 { font-size: 24px; font-weight: 800; color: #FFF; }
        .header p { color: #94A3B8; font-size: 14px; margin-top: 4px; }

        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block; font-size: 13px; font-weight: 600;
            color: #E2E8F0; margin-bottom: 6px;
        }
        .form-group label i { margin-right: 8px; color: #25D366; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 12px 16px;
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.08);
            border-radius: 12px; font-size: 14px;
            font-family: 'Inter', sans-serif; color: #FFF;
            transition: all 0.3s ease; outline: none;
        }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #25D366;
            box-shadow: 0 0 0 4px rgba(37,211,102,0.1);
        }
        .form-group input::placeholder, .form-group textarea::placeholder { color: #64748B; }
        .form-group .note { color: #64748B; font-size: 12px; margin-top: 4px; }
        .form-group .readonly {
            background: rgba(255,255,255,0.03);
            color: #94A3B8;
            cursor: not-allowed;
        }

        .btn-row { display: flex; gap: 12px; margin-top: 24px; }
        .btn {
            padding: 13px 24px;
            border: none; border-radius: 12px;
            font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all 0.3s ease;
            display: flex; align-items: center; justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }
        .btn-update {
            flex: 2;
            background: linear-gradient(135deg, #2563EB, #7C3AED);
            color: white;
        }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(37,99,235,0.3); }
        .btn-delete {
            flex: 1;
            background: linear-gradient(135deg, #DC2626, #B91C1C);
            color: white;
        }
        .btn-delete:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(220,38,38,0.3); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        .alert {
            padding: 14px 18px; border-radius: 12px;
            font-size: 13px; margin-bottom: 18px; display: none;
        }
        .alert.success { background: rgba(37,211,102,0.15); color: #6EE7B7; display: block; border: 1px solid rgba(37,211,102,0.2); }
        .alert.error { background: rgba(220,38,38,0.15); color: #FCA5A5; display: block; border: 1px solid rgba(220,38,38,0.2); }

        .wa-status {
            margin-top: 12px; padding: 12px 16px;
            background: rgba(255,255,255,0.04);
            border-radius: 10px;
            display: none; align-items: center; gap: 10px;
            font-size: 13px; color: #94A3B8;
        }
        .wa-status.success { color: #34D399; border: 1px solid rgba(37,211,102,0.2); display: flex; }
        .wa-status.failed { color: #F87171; border: 1px solid rgba(220,38,38,0.2); display: flex; }

        .spinner {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white; border-radius: 50%;
            animation: spin 0.8s linear infinite; display: none;
        }
        .spinner.active { display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .footer-link {
            text-align: center; margin-top: 20px;
        }
        .footer-link a {
            color: #25D366; text-decoration: none;
            font-size: 13px; font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="icon"><i class="fas fa-user-edit"></i></div>
        <h1>Edit Profil</h1>
        <p>Perbarui data pribadi Anda</p>
    </div>

    <div id="alert" class="alert"></div>

    <form id="profilForm">
        <input type="hidden" id="userId" value="<?= $user['id'] ?>">

        <div class="form-group">
            <label><i class="fas fa-user"></i> Nama Lengkap</label>
            <input type="text" id="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fab fa-whatsapp"></i> Nomor WhatsApp</label>
            <input type="tel" id="no_hp" value="<?= htmlspecialchars($user['no_hp']) ?>" required>
        </div>

        <div class="form-group">
            <label><i class="fas fa-map-pin"></i> Alamat</label>
            <textarea id="alamat" placeholder="Alamat lengkap" rows="2"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label><i class="fas fa-lock"></i> Password Baru (Opsional)</label>
            <input type="password" id="password" placeholder="Kosongkan jika tidak ingin ubah">
            <div class="note">Minimal 6 karakter. Kosongkan jika tidak ingin mengubah.</div>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn btn-update" id="btnUpdate">
                <span id="btnText">Simpan Perubahan</span>
                <span class="spinner" id="spinner"></span>
                <i class="fas fa-save"></i>
            </button>
            <button type="button" class="btn btn-delete" id="btnDelete" onclick="hapusAkun()">
                <i class="fas fa-trash"></i> Hapus Akun
            </button>
        </div>
    </form>

    <div class="wa-status" id="waStatus">
        <i class="fab fa-whatsapp"></i>
        <span id="waText">-</span>
    </div>

    <div class="footer-link">
        <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    </div>
</div>

<script>
// Update profil
document.getElementById('profilForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const alertBox = document.getElementById('alert');
    const btn = document.getElementById('btnUpdate');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('spinner');
    const waStatus = document.getElementById('waStatus');
    const waText = document.getElementById('waText');

    alertBox.className = 'alert';
    waStatus.className = 'wa-status';
    btn.disabled = true;
    btnText.textContent = 'Menyimpan...';
    spinner.classList.add('active');

    const data = {
        id: document.getElementById('userId').value,
        nama: document.getElementById('nama').value,
        email: document.getElementById('email').value,
        no_hp: document.getElementById('no_hp').value,
        alamat: document.getElementById('alamat').value,
        password: document.getElementById('password').value
    };

    try {
        const res = await fetch('api/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();

        if (result.status) {
            alertBox.className = 'alert success';
            alertBox.textContent = '✅ ' + result.message;
            
            waStatus.className = 'wa-status success';
            waStatus.style.display = 'flex';
            waText.textContent = result.whatsapp && result.whatsapp.status === 'terkirim' 
                ? '✅ Notifikasi WhatsApp berhasil dikirim ke ' + result.whatsapp.target
                : '⚠️ Notifikasi WA gagal dikirim';
        } else {
            alertBox.className = 'alert error';
            alertBox.textContent = '❌ ' + result.message;
        }
    } catch (err) {
        alertBox.className = 'alert error';
        alertBox.textContent = '❌ Error: ' + err.message;
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Simpan Perubahan';
        spinner.classList.remove('active');
    }
});

// Hapus akun
async function hapusAkun() {
    if (!confirm('⚠️ Yakin ingin MENGHAPUS akun Anda?')) return;

    const id = document.getElementById('userId').value;

    try {
        const res = await fetch(`api/delete.php?id=${id}`, { method: 'DELETE' });
        const result = await res.json();

        if (result.status) {
            alert('✅ ' + result.message);
            localStorage.removeItem('userData');
            setTimeout(() => window.location.href = 'login.html', 2000);
        } else {
            alert('❌ ' + result.message);
        }
    } catch (err) {
        alert('❌ Error: ' + err.message);
    }
}
</script>

</body>
</html>