<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}
$user_nama = $_SESSION['user_nama'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(145deg, #111827 0%, #374151 100%);
            color: #f3f4f6;
            padding: 20px;
        }
        .container { max-width: 1300px; margin: 0 auto; }
        .navbar, .table-wrapper, .modal-box {
            background: rgba(31, 41, 55, 0.86);
            border: 1px solid rgba(156, 163, 175, 0.35);
        }
        .navbar {
            border-radius: 20px;
            padding: 16px 28px;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px;
            margin-bottom: 24px;
        }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 20px; }
        .brand i { color: #d1d5db; font-size: 24px; }
        .role-badge {
            padding: 4px 12px; border-radius: 30px; font-size: 11px; font-weight: 700;
            background: #4b5563; color: #f9fafb; border: 1px solid #9ca3af;
        }
        .navbar-right { display: flex; align-items: center; gap: 12px; }
        .btn-back, .btn-add, .btn, .btn-save, .btn-cancel {
            border: 0; border-radius: 40px; cursor: pointer;
            font-family: 'Inter', sans-serif; font-weight: 700; text-decoration: none;
        }
        .btn-back {
            padding: 8px 18px; background: #4b5563; color: #f9fafb; font-size: 13px;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-add, .btn-save { background: #6b7280; color: #fff; }
        .btn-add { padding: 12px 24px; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        .btn-add:hover, .btn-save:hover, .btn-back:hover { background: #9ca3af; color: #111827; }
        .page-title {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px; margin-bottom: 24px;
        }
        .page-title h1 { font-size: 28px; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        .page-title h1 i { color: #d1d5db; }
        .table-wrapper { border-radius: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; font-size: 13px; }
        thead { background: #4b5563; }
        th {
            text-align: left; padding: 16px 18px; font-size: 11px; text-transform: uppercase;
            color: #e5e7eb; border-bottom: 1px solid rgba(156, 163, 175, 0.3);
        }
        td { padding: 14px 18px; border-bottom: 1px solid rgba(156, 163, 175, 0.18); }
        tbody tr:nth-child(even) { background: rgba(255,255,255,0.03); }
        tbody tr:hover { background: rgba(156, 163, 175, 0.16); }
        .badge {
            display: inline-block; padding: 4px 12px; border-radius: 30px;
            font-size: 11px; font-weight: 700; background: #6b7280; color: #fff;
        }
        .aksi-cell { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { padding: 6px 14px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; }
        .btn-edit { background: #6b7280; color: #fff; }
        .btn-delete { background: #374151; color: #e5e7eb; border: 1px solid #9ca3af; }
        .loading { text-align: center; padding: 60px 20px; color: #d1d5db; }
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(17, 24, 39, 0.75);
            display: none; justify-content: center; align-items: center;
            z-index: 1000; padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            border-radius: 24px; max-width: 520px; width: 100%;
            padding: 32px 28px; max-height: 90vh; overflow-y: auto;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-header h2 { font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .modal-close {
            width: 36px; height: 36px; border-radius: 50%; border: 0;
            background: #4b5563; color: #fff; cursor: pointer;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #d1d5db; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 11px 14px; background: #111827; color: #fff;
            border: 1px solid #6b7280; border-radius: 10px; font-family: 'Inter', sans-serif;
        }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .modal-actions { display: flex; gap: 12px; margin-top: 24px; }
        .btn-save, .btn-cancel { padding: 13px 24px; border-radius: 12px; font-size: 15px; }
        .btn-save { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-cancel { background: transparent; color: #e5e7eb; border: 1px solid #9ca3af; }
        .toast {
            position: fixed; top: 24px; right: 24px; padding: 14px 24px; border-radius: 12px;
            font-weight: 600; color: white; background: #4b5563;
            transform: translateX(150%); transition: transform 0.4s ease; z-index: 2000;
        }
        .toast.show { transform: translateX(0); }
        @media (max-width: 768px) {
            .page-title h1 { font-size: 20px; }
            .btn-add { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="toast" id="toast"><span id="toastMsg"></span></div>

<div class="modal-overlay" id="modalForm">
    <div class="modal-box">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> <span id="modalTitle">Tambah User</span></h2>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="userForm" onsubmit="return false;">
            <input type="hidden" id="inputId">
            <input type="hidden" id="inputMode" value="create">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" id="inputNama" placeholder="Nama lengkap" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="inputEmail" placeholder="email@example.com" required>
            </div>
            <div class="form-group">
                <label>Nomor WhatsApp</label>
                <input type="tel" id="inputHp" placeholder="081234567890" required>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <textarea id="inputAlamat" placeholder="Alamat lengkap" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select id="inputRole">
                    <option value="user">USER</option>
                    <option value="admin">ADMIN</option>
                </select>
            </div>
            <div class="form-group">
                <label>Password <span id="passNote">(wajib)</span></label>
                <input type="password" id="inputPassword" placeholder="Minimal 6 karakter">
                <small style="color:#d1d5db; font-size:11px; display:block; margin-top:4px;" id="passHelp">Wajib diisi untuk user baru</small>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="button" class="btn-save" id="btnSave" onclick="simpanUser()">
                    <span id="btnSaveText">Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="container">
    <nav class="navbar">
        <div class="brand">
            <i class="fas fa-users-cog"></i>
            <span>Kelola User</span>
            <span class="role-badge">ADMIN</span>
        </div>
        <div class="navbar-right">
            <span style="color:#d1d5db; font-size:13px;"><?= htmlspecialchars($user_nama) ?></span>
            <a href="dashboard.php" class="btn-back">Dashboard</a>
        </div>
    </nav>

    <div class="page-title">
        <h1><i class="fas fa-users"></i> Daftar Semua User</h1>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn-back" onclick="loadUsers()">Refresh</button>
            <button class="btn-add" onclick="openModalCreate()">Tambah User</button>
        </div>
    </div>

    <div class="table-wrapper">
        <div id="hasil">
            <div class="loading">Memuat data user...</div>
        </div>
    </div>
</div>

<script>
async function loadUsers() {
    const hasil = document.getElementById('hasil');
    hasil.innerHTML = `<div class="loading">Memuat data user...</div>`;
    const fd = new FormData();
    fd.append('action', 'get_all');
    try {
        const res = await fetch('api/admin.php', { method: 'POST', body: fd });
        const result = await res.json();
        if (!result.status) {
            hasil.innerHTML = `<div class="loading">${result.message}</div>`;
            return;
        }
        let html = `<table><thead><tr>
            <th>No</th><th>Nama</th><th>Email</th><th>No HP</th><th>Alamat</th><th>Role</th><th>Terdaftar</th><th>Aksi</th>
        </tr></thead><tbody>`;
        result.data.forEach((u, i) => {
            const tgl = u.created_at ? new Date(u.created_at).toLocaleDateString('id-ID') : '-';
            html += `<tr>
                <td>${i + 1}</td>
                <td>${u.nama}</td>
                <td>${u.email}</td>
                <td>${u.no_hp}</td>
                <td>${u.alamat || '-'}</td>
                <td><span class="badge">${u.role}</span></td>
                <td>${tgl}</td>
                <td><div class="aksi-cell">
                    <button class="btn btn-edit" onclick='openModalEdit(${JSON.stringify(u)})'>Edit</button>
                    <button class="btn btn-delete" onclick="hapusUser(${u.id}, '${u.nama.replace(/'/g, "\\'")}')">Hapus</button>
                </div></td>
            </tr>`;
        });
        hasil.innerHTML = html + `</tbody></table>`;
    } catch (err) {
        hasil.innerHTML = `<div class="loading">Error: ${err.message}</div>`;
    }
}

function openModalCreate() {
    document.getElementById('modalTitle').textContent = 'Tambah User';
    document.getElementById('inputMode').value = 'create';
    document.getElementById('inputId').value = '';
    ['inputNama','inputEmail','inputHp','inputAlamat','inputPassword'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('inputRole').value = 'user';
    document.getElementById('passNote').textContent = '(wajib)';
    document.getElementById('passHelp').textContent = 'Wajib diisi untuk user baru';
    document.getElementById('inputPassword').required = true;
    document.getElementById('modalForm').classList.add('active');
}

function openModalEdit(user) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('inputMode').value = 'update';
    document.getElementById('inputId').value = user.id;
    document.getElementById('inputNama').value = user.nama || '';
    document.getElementById('inputEmail').value = user.email || '';
    document.getElementById('inputHp').value = user.no_hp || '';
    document.getElementById('inputAlamat').value = user.alamat || '';
    document.getElementById('inputRole').value = user.role || 'user';
    document.getElementById('inputPassword').value = '';
    document.getElementById('passNote').textContent = '(kosongkan jika tidak diubah)';
    document.getElementById('passHelp').textContent = 'Kosongkan jika tidak ingin mengubah password';
    document.getElementById('inputPassword').required = false;
    document.getElementById('modalForm').classList.add('active');
}

function closeModal() {
    document.getElementById('modalForm').classList.remove('active');
}

document.getElementById('modalForm').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

async function simpanUser() {
    const mode = document.getElementById('inputMode').value;
    const btn = document.getElementById('btnSave');
    const btnText = document.getElementById('btnSaveText');
    const data = {
        action: mode,
        id: document.getElementById('inputId').value,
        nama: document.getElementById('inputNama').value.trim(),
        email: document.getElementById('inputEmail').value.trim(),
        no_hp: document.getElementById('inputHp').value.trim(),
        alamat: document.getElementById('inputAlamat').value.trim(),
        role: document.getElementById('inputRole').value,
        password: document.getElementById('inputPassword').value
    };
    if (!data.nama || !data.email || !data.no_hp) {
        showToast('Nama, Email, dan No HP wajib diisi!', 'error');
        return;
    }
    if (mode === 'create' && (!data.password || data.password.length < 6)) {
        showToast('Password wajib diisi minimal 6 karakter!', 'error');
        return;
    }
    if (mode === 'update' && data.password && data.password.length < 6) {
        showToast('Password minimal 6 karakter!', 'error');
        return;
    }
    btn.disabled = true;
    btnText.textContent = 'Menyimpan...';
    try {
        const res = await fetch('api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.status) {
            showToast(result.message + ' (WA: ' + result.whatsapp.status + ')');
            closeModal();
            loadUsers();
        } else {
            showToast(result.message);
        }
    } catch (err) {
        showToast('Error: ' + err.message);
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Simpan';
    }
}

async function hapusUser(id, nama) {
    if (!confirm(`Yakin ingin menghapus user "${nama}"?`)) return;
    try {
        const res = await fetch('api/admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        });
        const result = await res.json();
        showToast(result.message);
        if (result.status) loadUsers();
    } catch (err) {
        showToast('Error: ' + err.message);
    }
}

function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3500);
}

loadUsers();
</script>
</body>
</html>