<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$user = currentUser();
$sesiList = $pdo->query('SELECT * FROM sesi_presensi ORDER BY tanggal DESC, id DESC')->fetchAll();

$sesiId = (int) ($_GET['sesi_id'] ?? ($sesiList[0]['id'] ?? 0));
$rows = [];
$sesiTerpilih = null;
$hadirCount = 0;
$telatCount = 0;

if ($sesiId) {
    foreach ($sesiList as $s) {
        if ($s['id'] == $sesiId) { $sesiTerpilih = $s; break; }
    }
    if ($sesiTerpilih) {
        $stmt = $pdo->prepare(
            "SELECT m.nim, m.nama, m.kelas, m.jurusan,
                    p.status AS status_absen, p.waktu_absen
             FROM mahasiswa m
             LEFT JOIN presensi p ON p.mahasiswa_id = m.id AND p.sesi_id = ?
             WHERE m.kelas = ?
             ORDER BY m.nama"
        );
        $stmt->execute([$sesiId, $sesiTerpilih['kelas']]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            if ($r['status_absen'] === 'hadir') $hadirCount++;
            if ($r['status_absen'] === 'telat') $telatCount++;
        }
    }
}
$belumCount = count($rows) - $hadirCount - $telatCount;

$pageTitle = 'Laporan — NEXORA';
$activePage = 'laporan';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="shell">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-head">
            <div>
                <h1>Laporan Presensi</h1>
                <p class="sub">Rekap kehadiran per sesi kelas.</p>
            </div>
            <?php if ($sesiTerpilih): ?>
            <div class="head-actions">
                <a class="btn btn-primary" href="api/export_csv.php?sesi_id=<?= $sesiId ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                    Export CSV
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-head">
                <div><h2>Pilih Sesi</h2></div>
            </div>
            <form method="GET" class="field" style="max-width:460px;">
                <select name="sesi_id" onchange="this.form.submit()">
                    <?php foreach ($sesiList as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $s['id'] == $sesiId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nama_sesi']) ?> — Kelas <?= htmlspecialchars($s['kelas']) ?> — <?= date('d M Y', strtotime($s['tanggal'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($sesiTerpilih): ?>

        <div class="stat-row">
            <div class="stat-card teal">
                <div class="stat-num"><?= $hadirCount ?></div>
                <div class="stat-label">Hadir</div>
            </div>
            <div class="stat-card amber">
                <div class="stat-num"><?= $telatCount ?></div>
                <div class="stat-label">Telat</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?= $belumCount ?></div>
                <div class="stat-label">Belum Hadir</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?= count($rows) ?></div>
                <div class="stat-label">Total Mahasiswa</div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2><?= htmlspecialchars($sesiTerpilih['nama_sesi']) ?> &middot; <?= htmlspecialchars($sesiTerpilih['mata_kuliah']) ?></h2>
                    <div class="sub">Kelas <?= htmlspecialchars($sesiTerpilih['kelas']) ?> &nbsp;|&nbsp; <?= date('d M Y', strtotime($sesiTerpilih['tanggal'])) ?></div>
                </div>
            </div>
            <div class="table-wrap">
                <table class="roster">
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Jurusan</th>
                            <th>Waktu Absen</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="cell-nim"><?= htmlspecialchars($r['nim']) ?></td>
                                <td><?= htmlspecialchars($r['nama']) ?></td>
                                <td><?= htmlspecialchars($r['jurusan']) ?></td>
                                <td class="cell-waktu"><?= $r['waktu_absen'] ? date('H:i:s', strtotime($r['waktu_absen'])) : '—' ?></td>
                                <td>
                                    <?php if ($r['status_absen'] === 'hadir'): ?>
                                        <span class="badge hadir"><span class="dot"></span>Hadir</span>
                                    <?php elseif ($r['status_absen'] === 'telat'): ?>
                                        <span class="badge telat"><span class="dot"></span>Telat</span>
                                    <?php else: ?>
                                        <span class="badge belum"><span class="dot"></span>Belum</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
            <div class="card"><div class="empty-state">Belum ada sesi presensi yang tercatat.</div></div>
        <?php endif; ?>

        <p class="footnote">NEXORA &middot; Sistem Presensi QR Kampus</p>
    </main>
</div>

<?php require __DIR__ . '/includes/toast.php'; ?>
</body>
</html>
