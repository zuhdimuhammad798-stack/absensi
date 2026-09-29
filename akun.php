<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$user = currentUser();
$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaInput = $_POST['nama'] ?? '';
    $passwordLamaInput = $_POST['password_lama'] ?? '';
    $passwordBaruInput = $_POST['password_baru'] ?? '';
    $konfirmasiInput = $_POST['konfirmasi_password'] ?? '';
    $tokenInput = $_POST['csrf_token'] ?? '';
    $nama = is_string($namaInput) ? trim($namaInput) : '';
    $passwordLama = is_string($passwordLamaInput) ? $passwordLamaInput : '';
    $passwordBaru = is_string($passwordBaruInput) ? $passwordBaruInput : '';
    $konfirmasi = is_string($konfirmasiInput) ? $konfirmasiInput : '';
    $namaLength = preg_match_all('/./us', $nama);
    $passwordLength = preg_match_all('/./us', $passwordBaru);

    if (!is_string($tokenInput) || !hash_equals($csrfToken, $tokenInput)) {
        $error = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($namaLength === false || $namaLength === 0 || $namaLength > 100) {
        $error = 'Nama wajib diisi dan maksimal 100 karakter.';
    } elseif ($passwordLength === false || $passwordLength < 8) {
        $error = 'Password baru minimal 8 karakter.';
    } elseif (!hash_equals($passwordBaru, $konfirmasi)) {
        $error = 'Konfirmasi password baru tidak cocok.';
    } else {
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $akun = $stmt->fetch();

        if (!$akun || !password_verify($passwordLama, $akun['password'])) {
            $error = 'Password saat ini salah.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET nama = ?, password = ? WHERE id = ?');
            $stmt->execute([$nama, password_hash($passwordBaru, PASSWORD_DEFAULT), $user['id']]);
            $_SESSION['nama'] = $nama;
            setFlash('success', 'Nama dan password akun berhasil diperbarui.');
            redirect('akun.php');
        }
    }
}

$pageTitle = 'Akun — NEXORA';
$activePage = 'akun';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="shell">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
        <div class="page-head">
            <div>
                <h1>Pengaturan Akun</h1>
                <p class="sub">Perbarui identitas dan password login Anda.</p>
            </div>
        </div>

        <div class="card" style="max-width:680px;">
            <div class="card-head">
                <div>
                    <h2>Informasi Login</h2>
                    <div class="sub">Username <strong><?= htmlspecialchars($user['username']) ?></strong> tidak dapat diubah.</div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert err" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="grid-2">
                    <div class="field">
                        <label for="nama">Nama tampilan</label>
                        <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? $user['nama']) ?>" maxlength="100" autocomplete="name" required>
                    </div>
                    <div class="field">
                        <label for="password-lama">Password saat ini</label>
                        <input type="password" id="password-lama" name="password_lama" autocomplete="current-password" required>
                    </div>
                    <div class="field">
                        <label for="password-baru">Password baru</label>
                        <input type="password" id="password-baru" name="password_baru" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="field">
                        <label for="konfirmasi-password">Konfirmasi password baru</label>
                        <input type="password" id="konfirmasi-password" name="konfirmasi_password" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            </form>
        </div>

        <p class="footnote">NEXORA &middot; Sistem Presensi QR Kampus</p>
    </main>
</div>

<?php require __DIR__ . '/includes/toast.php'; ?>
</body>
</html>
