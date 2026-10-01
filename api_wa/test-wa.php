<?php
session_start();

// ============================================================
// PROTEKSI AKSES - HANYA ADMIN
// ============================================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php?error=akses_ditolak");
    exit();
}

require_once "functions/fonnte.php";

$hasil = null;
$nomor = "";
$pesan = "";
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;
    $nomor = trim($_POST['nomor'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');

    // Validasi
    if (empty($nomor)) {
        $hasil = ['status' => false, 'message' => 'Nomor tujuan wajib diisi'];
    } elseif (empty($pesan)) {
        $hasil = ['status' => false, 'message' => 'Pesan wajib diisi'];
    } else {
        $nomorFormat = formatNomor($nomor);
        $hasil = kirimWhatsApp($nomorFormat, $pesan);
        $hasil['nomor_format'] = $nomorFormat;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test WhatsApp Gateway - Fonnte</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(145deg, #0F172A 0%, #1E293B 100%);
            color: #F1F5F9;
            padding: 24px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .container {
            max-width: 680px;
            width: 100%;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 36px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
        }

        /* ADMIN BADGE */
        .admin-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(124, 58, 237, 0.15);
            border: 1px solid rgba(124, 58, 237, 0.3);
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            color: #A78BFA;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        /* HEADER */
        .header { text-align: center; margin-bottom: 32px; }
        .header .icon {
            width: 76px; height: 76px;
            background: linear-gradient(135deg, #25D366, #128C7E);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            font-size: 34px; color: white;
            box-shadow: 0 10px 30px rgba(37, 211, 102, 0.3);
        }
        .header h1 { font-size: 26px; font-weight: 800; color: #FFF; }
        .header p { color: #94A3B8; font-size: 14px; margin-top: 6px; }

        /* INFO BOX */
        .info-box {
            background: rgba(37, 211, 102, 0.08);
            border: 1px solid rgba(37, 211, 102, 0.2);
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #86EFAC;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .info-box i { margin-top: 2px; }

        /* FORM */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94A3B8;
            margin-bottom: 8px;
        }
        .form-group label i { margin-right: 6px; color: #25D366; }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 13px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1.5px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: #FFF;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.3s ease;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.6;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #25D366;
            background: rgba(37, 211, 102, 0.05);
            box-shadow: 0 0 0 4px rgba(37, 211, 102, 0.1);
        }
        .form-group input::placeholder, .form-group textarea::placeholder { color: #64748B; }
        .form-group .hint {
            color: #64748B;
            font-size: 12px;
            margin-top: 6px;
            display: block;
        }

        .btn-send {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 20px rgba(37, 211, 102, 0.3);
        }
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(37, 211, 102, 0.4);
        }
        .btn-send:active { transform: scale(0.98); }

        /* RESULT */
        .result-box {
            margin-top: 28px;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid;
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result-success {
            background: rgba(37, 211, 102, 0.08);
            border-color: rgba(37, 211, 102, 0.25);
        }
        .result-error {
            background: rgba(220, 38, 38, 0.08);
            border-color: rgba(220, 38, 38, 0.25);
        }

        .result-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .result-header .icon-circle {
            width: 48px; height: 48px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }
        .result-success .icon-circle {
            background: rgba(37, 211, 102, 0.2);
            color: #4ADE80;
        }
        .result-error .icon-circle {
            background: rgba(220, 38, 38, 0.2);
            color: #F87171;
        }
        .result-header h3 {
            font-size: 18px;
            font-weight: 800;
            color: #FFF;
        }
        .result-header p {
            font-size: 13px;
            color: #94A3B8;
            margin-top: 2px;
        }

        .result-detail {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 8px 16px;
            font-size: 13px;
            padding: 16px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            margin-top: 16px;
        }
        .result-detail .label {
            color: #94A3B8;
            font-weight: 600;
        }
        .result-detail .value {
            color: #E2E8F0;
            word-break: break-word;
        }
        .value.success { color: #4ADE80; font-weight: 700; }
        .value.error { color: #F87171; font-weight: 700; }

        .raw-response {
            margin-top: 16px;
            padding: 14px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            color: #94A3B8;
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
            line-height: 1.6;
        }
        .raw-response::-webkit-scrollbar { width: 6px; }
        .raw-response::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .raw-response::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }

        .footer-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }
        .footer-link a {
            color: #25D366;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .footer-link a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .container { padding: 24px 18px; }
            .header h1 { font-size: 20px; }
            .result-detail { grid-template-columns: 1fr; gap: 4px 0; }
            .result-detail .label { font-size: 11px; text-transform: uppercase; }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- ADMIN BADGE -->
    <div class="admin-badge">
        <i class="fas fa-user-shield"></i> Admin Only
    </div>

    <div class="header">
        <div class="icon"><i class="fab fa-whatsapp"></i></div>
        <h1>Test WhatsApp Gateway</h1>
        <p>Cek koneksi Fonnte API</p>
    </div>

    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Catatan:</strong> Gunakan nomor yang sudah terdaftar di device Fonnte.
            Format nomor otomatis dikonversi (0812xxx → 62812xxx).
        </div>
    </div>

    <form method="POST" action="">
        <div class="form-group">
            <label><i class="fas fa-phone"></i> Nomor WhatsApp Tujuan</label>
            <input type="tel" name="nomor" 
                value="<?= htmlspecialchars($nomor ?: '6285134777683') ?>" 
                placeholder="Contoh: 085134777683" required>
            <span class="hint">Contoh: 085134777683 atau 6285134777683</span>
        </div>

        <div class="form-group">
            <label><i class="fas fa-comment-dots"></i> Isi Pesan</label>
            <textarea name="pesan" placeholder="Tulis pesan di sini..." required><?= htmlspecialchars($pesan ?: "Halo! 👋\n\nIni adalah pesan percobaan dari project API Fonnte saya.\n\nJika pesan ini masuk, berarti koneksi Fonnte berhasil.") ?></textarea>
        </div>

        <button type="submit" class="btn-send">
            <i class="fas fa-paper-plane"></i> Kirim Pesan WhatsApp
        </button>
    </form>

    <?php if ($submitted && $hasil): ?>
        <?php if ($hasil['status']): ?>
            <!-- HASIL SUKSES -->
            <div class="result-box result-success">
                <div class="result-header">
                    <div class="icon-circle">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h3>✅ Pesan Berhasil Dikirim!</h3>
                        <p>Notifikasi WhatsApp telah dikirim oleh Fonnte</p>
                    </div>
                </div>

                <div class="result-detail">
                    <div class="label">Nomor Tujuan</div>
                    <div class="value"><?= htmlspecialchars($hasil['nomor_format'] ?? '-') ?></div>

                    <div class="label">HTTP Code</div>
                    <div class="value success"><?= $hasil['http_code'] ?? '-' ?></div>

                    <div class="label">Status</div>
                    <div class="value success">✅ TERKIRIM</div>

                    <div class="label">Message</div>
                    <div class="value"><?= htmlspecialchars($hasil['message'] ?? 'OK') ?></div>
                </div>

                <div class="raw-response">📥 Raw Response:
<?= htmlspecialchars(print_r($hasil['response'] ?? $hasil, true)) ?></div>
            </div>
        <?php else: ?>
            <!-- HASIL GAGAL -->
            <div class="result-box result-error">
                <div class="result-header">
                    <div class="icon-circle">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <h3>❌ Pesan Gagal Dikirim</h3>
                        <p>Ada masalah saat mengirim pesan</p>
                    </div>
                </div>

                <div class="result-detail">
                    <div class="label">Nomor Tujuan</div>
                    <div class="value"><?= htmlspecialchars($hasil['nomor_format'] ?? $nomor) ?></div>

                    <div class="label">HTTP Code</div>
                    <div class="value error"><?= $hasil['http_code'] ?? '-' ?></div>

                    <div class="label">Status</div>
                    <div class="value error">❌ GAGAL</div>

                    <div class="label">Pesan Error</div>
                    <div class="value"><?= htmlspecialchars($hasil['message'] ?? 'Unknown error') ?></div>
                </div>

                <div class="raw-response">📥 Raw Response:
<?= htmlspecialchars(print_r($hasil, true)) ?></div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="footer-link">
        <a href="dashboard.php">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

</div>

</body>
</html>