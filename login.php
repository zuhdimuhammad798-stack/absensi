<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

// Instalasi pertama: bila belum ada user sama sekali, buat admin default (admin / admin123).
if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
    $pdo->prepare('INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, "admin")')
        ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrator']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['role']     = $user['role'];
            redirect('dashboard.php');
        }
        $error = 'Username atau password salah.';
    }
}

$pageTitle = 'Masuk — NEXORA';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="login-shell">

    <div class="login-visual">
        <div>
            <div style="display:flex;align-items:center;gap:11px;">
                <div class="brand-mark">NX</div>
                <div class="brand-word" style="font-size:20px;">NEX<span style="color:var(--gold-strong)">ORA</span></div>
            </div>
            <p class="login-quote" style="margin-top:60px;">
                Setiap mahasiswa membawa <span class="accent">kartu masuknya sendiri</span> —
                satu pindai QR, kehadiran tercatat seketika.
            </p>
        </div>

        <div class="login-pass">
            <div class="swatch"></div>
            <div>
                <div class="pass-name">Rekayasa Perangkat Lunak</div>
                <div class="pass-meta">KELAS 2 · 08:00–09:40</div>
            </div>
        </div>
    </div>

    <div class="login-form-wrap">
        <div class="login-card">
            <div class="brand-mark">NX</div>
            <h1>Masuk ke NEXORA</h1>
            <p class="sub">Kelola sesi &amp; data kehadiran mahasiswa kamu.</p>

            <?php if ($error): ?>
                <div class="alert err">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="admin" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </form>

            <div class="hint-box">
                Akun demo — Username <b>admin</b> · Password <b>admin123</b>. Segera ganti untuk produksi.
            </div>
        </div>
    </div>

</div>
</body>
</html>
