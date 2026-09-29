<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM mahasiswa WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();

if (!$m) {
    http_response_code(404);
    die('Mahasiswa tidak ditemukan.');
}

$pageTitle = 'Kartu QR — ' . $m['nama'];
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="center-screen">
    <div class="id-pass">
        <div class="id-kicker">NEXORA · KARTU PRESENSI</div>
        <h2><?= htmlspecialchars($m['nama']) ?></h2>
        <div class="id-meta">NIM <?= htmlspecialchars($m['nim']) ?> · Kelas <?= htmlspecialchars($m['kelas']) ?></div>

        <div class="qr-frame">
            <div id="qrcode"></div>
        </div>
        <div class="qr-caption">Tunjukkan kode ini ke dosen untuk presensi</div>

        <div class="id-actions">
            <button class="btn btn-ghost btn-block" onclick="downloadQR()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                Unduh
            </button>
            <button class="btn btn-ghost btn-block" onclick="window.print()">Cetak</button>
            <a href="mahasiswa.php" class="btn btn-ghost btn-block">Kembali</a>
        </div>
    </div>
</div>

<script src="assets/js/qrcode.js"></script>
<script>
const token = <?= json_encode($m['qr_token']) ?>;
const namaFile = <?= json_encode(preg_replace('/[^A-Za-z0-9_-]+/', '_', $m['nim'] . '_' . $m['nama'])) ?>;
const canvas = NxQR.toCanvas(token, 240, { level: 'M' });
canvas.style.cssText = 'width:100%;max-width:240px;height:auto;display:block;image-rendering:pixelated;';
document.getElementById('qrcode').appendChild(canvas);

function downloadQR() {
    const link = document.createElement('a');
    link.download = 'QR_' + namaFile + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}
</script>
</body>
</html>
