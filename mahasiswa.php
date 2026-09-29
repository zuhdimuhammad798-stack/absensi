<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$user = currentUser();
$mahasiswa = $pdo->query('SELECT * FROM mahasiswa ORDER BY kelas, nama')->fetchAll();

$pageTitle = 'Kelola Mahasiswa — NEXORA';
$activePage = 'mahasiswa';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="shell">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-head">
            <div>
                <h1>Kelola Mahasiswa</h1>
                <p class="sub">Tambah mahasiswa &amp; buat kartu QR presensi mereka.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Tambah Mahasiswa Baru</h2>
                    <div class="sub">QR Code unik akan dibuat otomatis untuk setiap mahasiswa.</div>
                </div>
            </div>
            <form method="POST" action="api/tambah_mahasiswa.php" id="form-tambah">
                <div class="grid-2">
                    <div class="field">
                        <label>NIM</label>
                        <input type="text" name="nim" placeholder="2024005" required>
                    </div>
                    <div class="field">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" placeholder="Nama Mahasiswa" required>
                    </div>
                    <div class="field">
                        <label>Kelas</label>
                        <input type="text" name="kelas" placeholder="2" required>
                    </div>
                    <div class="field">
                        <label>Jurusan</label>
                        <input type="text" name="jurusan" placeholder="Teknik Informatika" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Tambah &amp; Buat QR</button>
            </form>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Daftar Mahasiswa</h2>
                    <div class="sub"><?= count($mahasiswa) ?> mahasiswa terdaftar</div>
                </div>
                <div class="head-actions" style="flex-wrap:wrap;">
                    <?php foreach (array_unique(array_column($mahasiswa, 'kelas')) as $kl): ?>
                        <a href="kartu_cetak.php?kelas=<?= urlencode($kl) ?>" target="_blank" class="btn btn-ghost btn-sm">Cetak Kartu Kelas <?= htmlspecialchars($kl) ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="field" style="min-width:220px;">
                    <input type="text" id="mhs-search" placeholder="Cari NIM, nama, atau kelas...">
                </div>
            </div>
            <div class="table-wrap">
                <table class="roster" id="mhs-table">
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Jurusan</th>
                            <th>QR Code</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mahasiswa as $m): ?>
                            <tr data-search="<?= strtolower(htmlspecialchars($m['nim'] . ' ' . $m['nama'] . ' ' . $m['kelas'])) ?>">
                                <td class="cell-nim"><?= htmlspecialchars($m['nim']) ?></td>
                                <td><?= htmlspecialchars($m['nama']) ?></td>
                                <td><?= htmlspecialchars($m['kelas']) ?></td>
                                <td><?= htmlspecialchars($m['jurusan']) ?></td>
                                <td>
                                    <a href="qr_view.php?id=<?= $m['id'] ?>" target="_blank" class="row-action">Lihat QR</a>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <form method="POST" action="api/hapus_mahasiswa.php" onsubmit="return confirm('Hapus mahasiswa ini beserta riwayat presensinya?');">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="row-action" style="color:var(--red); border-color:rgba(226,104,92,.35);">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$mahasiswa): ?>
                            <tr><td colspan="6" class="empty-state">Belum ada data mahasiswa. Tambahkan lewat form di atas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="footnote">NEXORA &middot; Sistem Presensi QR Kampus</p>
    </main>
</div>

<?php require __DIR__ . '/includes/toast.php'; ?>

<script>
document.getElementById('mhs-search').addEventListener('input', (e) => {
    const q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('#mhs-table tbody tr[data-search]').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
