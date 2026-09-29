<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$user = currentUser();

// Sesi aktif terbaru
$stmt = $pdo->query("SELECT * FROM sesi_presensi WHERE status = 'aktif' ORDER BY id DESC LIMIT 1");
$sesiAktif = $stmt->fetch();

// Semua kelas yang ada mahasiswanya (untuk form buat sesi)
$kelasList = $pdo->query('SELECT DISTINCT kelas FROM mahasiswa ORDER BY kelas')->fetchAll();

$roster = [];
$hadirCount = 0;
$telatCount = 0;

if ($sesiAktif) {
    $stmt = $pdo->prepare(
        "SELECT m.id, m.nim, m.nama, m.kelas, m.jurusan,
                p.status AS status_absen, p.waktu_absen
         FROM mahasiswa m
         LEFT JOIN presensi p ON p.mahasiswa_id = m.id AND p.sesi_id = ?
         WHERE m.kelas = ?
         ORDER BY m.nama ASC"
    );
    $stmt->execute([$sesiAktif['id'], $sesiAktif['kelas']]);
    $roster = $stmt->fetchAll();

    foreach ($roster as $r) {
        if ($r['status_absen'] === 'hadir') $hadirCount++;
        if ($r['status_absen'] === 'telat') $telatCount++;
    }
}
$belumCount = count($roster) - $hadirCount - $telatCount;

$pageTitle = 'Dashboard — NEXORA';
$activePage = 'dashboard';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="shell">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-head">
            <div>
                <h1>Dashboard</h1>
                <p class="sub">Pantau kehadiran mahasiswa secara langsung.</p>
            </div>
            <?php if ($sesiAktif): ?>
            <div class="head-actions">
                <a href="scan.php?sesi_id=<?= $sesiAktif['id'] ?>" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><circle cx="12" cy="13.5" r="3"/></svg>
                    Mulai Scan
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!$sesiAktif): ?>

            <div class="card">
                <div class="card-head">
                    <div>
                        <h2>Belum ada sesi aktif</h2>
                        <div class="sub">Buat sesi presensi baru untuk mulai mengabsen mahasiswa.</div>
                    </div>
                </div>
                <?php if (!$kelasList): ?>
                    <div class="empty-state">
                        Belum ada data mahasiswa. Tambahkan mahasiswa terlebih dahulu di halaman
                        <a href="mahasiswa.php" style="color:var(--gold-strong); font-weight:600;">Kelola Mahasiswa</a>.
                    </div>
                <?php else: ?>
                <form method="POST" action="api/buat_sesi.php">
                    <div class="grid-2">
                        <div class="field">
                            <label>Nama Sesi</label>
                            <input type="text" name="nama_sesi" placeholder="Pertemuan 1" required>
                        </div>
                        <div class="field">
                            <label>Mata Kuliah</label>
                            <input type="text" name="mata_kuliah" placeholder="Rekayasa Perangkat Lunak" required>
                        </div>
                        <div class="field">
                            <label>Kelas</label>
                            <select name="kelas" required>
                                <?php foreach ($kelasList as $k): ?>
                                    <option value="<?= htmlspecialchars($k['kelas']) ?>">Kelas <?= htmlspecialchars($k['kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Batas Toleransi Telat (menit)</label>
                            <input type="number" name="batas_telat" value="15" min="0" required>
                        </div>
                        <div class="field">
                            <label>Jam Mulai</label>
                            <input type="time" name="jam_mulai" required>
                        </div>
                        <div class="field">
                            <label>Jam Selesai</label>
                            <input type="time" name="jam_selesai" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Mulai Sesi Presensi</button>
                </form>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <div class="ticket">
                <div class="ticket-main">
                    <div class="ticket-eyebrow"><span class="pulse-dot"></span> Sesi sedang berlangsung</div>
                    <h2><?= htmlspecialchars($sesiAktif['nama_sesi']) ?></h2>
                    <div class="ticket-meta">
                        <span><?= htmlspecialchars($sesiAktif['mata_kuliah']) ?></span>
                        <span class="sep">•</span>
                        <span>Kelas <?= htmlspecialchars($sesiAktif['kelas']) ?></span>
                        <span class="sep">•</span>
                        <span><?= date('d M Y', strtotime($sesiAktif['tanggal'])) ?></span>
                    </div>
                    <div style="margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;">
                        <form method="POST" action="api/tutup_sesi.php" onsubmit="return confirm('Tutup sesi presensi ini sekarang?');">
                            <input type="hidden" name="sesi_id" value="<?= $sesiAktif['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm">Tutup Sesi</button>
                        </form>
                        <a href="laporan.php?sesi_id=<?= $sesiAktif['id'] ?>" class="btn btn-ghost btn-sm">Lihat Laporan</a>
                    </div>
                </div>
                <div class="ticket-stub">
                    <div class="ticket-divider"></div>
                    <div class="stub-label">Jam Kelas</div>
                    <div class="stub-time"><?= substr($sesiAktif['jam_mulai'], 0, 5) ?>–<?= substr($sesiAktif['jam_selesai'], 0, 5) ?></div>
                    <div class="stub-label">Toleransi <?= (int) $sesiAktif['batas_telat'] ?> menit</div>
                </div>
            </div>

            <div class="stat-row">
                <div class="stat-card teal">
                    <div class="stat-num" id="stat-hadir"><?= $hadirCount ?></div>
                    <div class="stat-label">Hadir</div>
                </div>
                <div class="stat-card amber">
                    <div class="stat-num" id="stat-telat"><?= $telatCount ?></div>
                    <div class="stat-label">Telat</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num" id="stat-belum"><?= $belumCount ?></div>
                    <div class="stat-label">Belum Hadir</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num"><?= count($roster) ?></div>
                    <div class="stat-label">Total Mahasiswa Kelas</div>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div>
                        <h2>Roster Kehadiran</h2>
                        <div class="sub">Diperbarui otomatis setiap beberapa detik.</div>
                    </div>
                    <div class="field" style="min-width:220px;">
                        <input type="text" id="roster-search" placeholder="Cari NIM atau nama...">
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="roster" id="roster-table">
                        <thead>
                            <tr>
                                <th>NIM</th>
                                <th>Nama</th>
                                <th>Jurusan</th>
                                <th>Waktu Absen</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="roster-body">
                            <?php foreach ($roster as $r): ?>
                                <tr data-mid="<?= $r['id'] ?>" data-search="<?= strtolower(htmlspecialchars($r['nim'] . ' ' . $r['nama'])) ?>">
                                    <td class="cell-nim"><?= htmlspecialchars($r['nim']) ?></td>
                                    <td><?= htmlspecialchars($r['nama']) ?></td>
                                    <td><?= htmlspecialchars($r['jurusan']) ?></td>
                                    <td class="cell-waktu"><?= $r['waktu_absen'] ? date('H:i:s', strtotime($r['waktu_absen'])) : '—' ?></td>
                                    <td class="cell-status">
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

        <?php endif; ?>

        <p class="footnote">NEXORA &middot; Sistem Presensi QR Kampus</p>
    </main>
</div>

<?php require __DIR__ . '/includes/toast.php'; ?>

<?php if ($sesiAktif): ?>
<script>
const sesiId = <?= (int) $sesiAktif['id'] ?>;

function renderStatus(status) {
    if (status === 'hadir') return '<span class="badge hadir"><span class="dot"></span>Hadir</span>';
    if (status === 'telat') return '<span class="badge telat"><span class="dot"></span>Telat</span>';
    return '<span class="badge belum"><span class="dot"></span>Belum</span>';
}

async function refreshRoster() {
    try {
        const res = await fetch(`api/get_roster.php?sesi_id=${sesiId}`);
        const data = await res.json();
        if (!data.ok) return;

        let hadir = 0, telat = 0, belum = 0;
        data.roster.forEach(r => {
            const row = document.querySelector(`tr[data-mid="${r.id}"]`);
            if (row) {
                const prevStatus = row.querySelector('.cell-status').dataset.status;
                row.querySelector('.cell-waktu').textContent = r.waktu_absen ? r.waktu_absen : '—';
                row.querySelector('.cell-status').innerHTML = renderStatus(r.status_absen);
                row.querySelector('.cell-status').dataset.status = r.status_absen;
                if (prevStatus !== undefined && prevStatus !== r.status_absen && r.status_absen !== 'belum') {
                    row.classList.add('just-scanned');
                    setTimeout(() => row.classList.remove('just-scanned'), 1600);
                }
            }
            if (r.status_absen === 'hadir') hadir++;
            else if (r.status_absen === 'telat') telat++;
            else belum++;
        });
        document.getElementById('stat-hadir').textContent = hadir;
        document.getElementById('stat-telat').textContent = telat;
        document.getElementById('stat-belum').textContent = belum;
    } catch (e) {
        console.error(e);
    }
}

document.getElementById('roster-search').addEventListener('input', (e) => {
    const q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('#roster-body tr').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});

setInterval(refreshRoster, 4000);
</script>
<?php endif; ?>
</body>
</html>
