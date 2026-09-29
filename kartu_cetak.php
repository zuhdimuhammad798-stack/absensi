<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$kelas = trim($_GET['kelas'] ?? '');
$stmt = $pdo->prepare('SELECT * FROM mahasiswa WHERE kelas = ? ORDER BY nama');
$stmt->execute([$kelas]);
$list = $stmt->fetchAll();

$pageTitle = 'Cetak Kartu QR — Kelas ' . $kelas;
require __DIR__ . '/includes/head.php';
?>
<body class="print-body">
<div class="print-bar no-print">
    <a href="mahasiswa.php" class="btn btn-ghost btn-sm">← Kembali</a>
    <span>Kelas <?= htmlspecialchars($kelas) ?> · <?= count($list) ?> kartu</span>
    <button class="btn btn-primary btn-sm" onclick="window.print()">Cetak</button>
</div>
<div class="print-grid">
    <?php foreach ($list as $m): ?>
        <div class="print-card">
            <div class="pc-brand">NEXORA · KARTU PRESENSI</div>
            <div class="pc-qr" data-token="<?= htmlspecialchars($m['qr_token']) ?>"></div>
            <div class="pc-name"><?= htmlspecialchars($m['nama']) ?></div>
            <div class="pc-meta"><?= htmlspecialchars($m['nim']) ?> · Kelas <?= htmlspecialchars($m['kelas']) ?></div>
        </div>
    <?php endforeach; ?>
    <?php if (!$list): ?><p style="padding:30px;color:#888;">Tidak ada mahasiswa di kelas ini.</p><?php endif; ?>
</div>
<script src="assets/js/qrcode.js"></script>
<script>
document.querySelectorAll('.pc-qr').forEach(el => {
    const c = NxQR.toCanvas(el.dataset.token, 200, { level: 'M' });
    c.style.cssText = 'width:100%;height:auto;display:block;image-rendering:pixelated;';
    el.appendChild(c);
});
</script>
</body>
</html>
