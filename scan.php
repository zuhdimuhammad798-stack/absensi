<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
requireLogin();

$sesiId = (int) ($_GET['sesi_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM sesi_presensi WHERE id = ? AND status = 'aktif'");
$stmt->execute([$sesiId]);
$sesi = $stmt->fetch();

if (!$sesi) {
    setFlash('error', 'Sesi tidak aktif atau tidak ditemukan.');
    redirect('dashboard.php');
}

$pageTitle = 'Scan QR — ' . $sesi['nama_sesi'];
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="shell">
    <main class="content" style="max-width:1020px;">
        <div class="page-head">
            <div>
                <h1>Scan QR Code</h1>
                <p class="sub"><?= htmlspecialchars($sesi['nama_sesi']) ?> &middot; Kelas <?= htmlspecialchars($sesi['kelas']) ?> &middot; <?= htmlspecialchars($sesi['mata_kuliah']) ?></p>
            </div>
            <div class="head-actions">
                <a href="dashboard.php" class="btn btn-ghost">Kembali ke Dashboard</a>
            </div>
        </div>

        <div class="scan-shell">
            <div>
                <div class="scan-frame-wrap">
                    <div class="scan-stage" id="stage">
                        <video id="cam" playsinline muted></video>
                        <div id="reader-fb"></div>
                        <div class="scan-idle" id="idle">
                            <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/></svg>
                            <div>Kamera belum aktif</div>
                        </div>
                        <div class="scan-corners" id="corners"></div>
                    </div>
                    <div class="scan-controls">
                        <button class="btn btn-primary" id="btn-start" type="button">Nyalakan Kamera</button>
                        <button class="btn btn-ghost" id="btn-stop" type="button" style="display:none">Matikan</button>
                        <button class="btn btn-ghost" id="btn-flip" type="button" style="display:none">Ganti Kamera</button>
                    </div>
                    <div id="scan-result" class="scan-result">Nyalakan kamera lalu arahkan ke QR Code mahasiswa.</div>
                    <div id="secure-warn" class="alert err" style="display:none;margin:12px 0 0;">
                        Kamera diblokir browser karena halaman ini tidak memakai <b>HTTPS</b>. Buka lewat
                        <b>http://localhost/</b> di laptop, atau pasang HTTPS. Sementara itu gunakan kolom input di bawah.
                    </div>
                </div>

                <div class="card manual-card">
                    <div class="card-head" style="margin-bottom:12px;">
                        <div>
                            <h2 style="font-size:16px;">Input Manual / Alat Scan USB</h2>
                            <div class="sub">Ketik NIM, atau arahkan alat scan barcode ke sini lalu tekan Enter.</div>
                        </div>
                    </div>
                    <form id="manual-form" class="manual-row" autocomplete="off">
                        <input type="text" id="manual-input" placeholder="NIM atau isi QR…" inputmode="text" autocapitalize="off" spellcheck="false">
                        <button class="btn btn-primary" type="submit">Catat</button>
                    </form>
                </div>
            </div>

            <div class="card" style="margin-bottom:0;">
                <div class="card-head" style="margin-bottom:14px;">
                    <div>
                        <h2 style="font-size:16px;">Riwayat Scan</h2>
                        <div class="sub"><span id="cnt-h">0</span> hadir · <span id="cnt-t">0</span> telat (sesi layar ini)</div>
                    </div>
                </div>
                <div class="scan-log" id="scan-log">
                    <div class="scan-log-empty" id="scan-log-empty">Belum ada mahasiswa yang di-scan.</div>
                </div>
            </div>
        </div>

        <p class="footnote">NEXORA &middot; Sistem Presensi QR Kampus</p>
    </main>
</div>

<script>
const sesiId = <?= (int) $sesi['id'] ?>;
const $ = (id) => document.getElementById(id);
const resultBox = $('scan-result'), logBox = $('scan-log');
let lastToken = null, lastTime = 0, cntH = 0, cntT = 0, audioCtx = null;

function showResult(cls, msg) { resultBox.className = 'scan-result ' + cls; resultBox.textContent = msg; }

function beep(ok) {
    try {
        audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
        const o = audioCtx.createOscillator(), g = audioCtx.createGain();
        o.frequency.value = ok ? 880 : 220; o.type = 'sine';
        g.gain.setValueAtTime(0.15, audioCtx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.18);
        o.connect(g); g.connect(audioCtx.destination); o.start(); o.stop(audioCtx.currentTime + 0.2);
    } catch (e) {}
    if (navigator.vibrate) navigator.vibrate(ok ? 60 : [40, 40, 40]);
}

function addLogEntry(nama, nim, status, waktu, ulang) {
    const empty = $('scan-log-empty'); if (empty) empty.remove();
    const item = document.createElement('div');
    item.className = 'scan-log-item';
    const av = document.createElement('div'); av.className = 'avatar'; av.textContent = nama.charAt(0).toUpperCase();
    const mid = document.createElement('div'); mid.style.cssText = 'flex:1;min-width:0;';
    const n = document.createElement('div'); n.className = 'sl-name'; n.textContent = nama;
    const m = document.createElement('div'); m.className = 'sl-meta'; m.textContent = nim + ' · ' + waktu + (ulang ? ' · sudah tercatat' : '');
    mid.append(n, m);
    const b = document.createElement('span'); b.className = 'badge ' + (status === 'hadir' ? 'hadir' : 'telat');
    b.innerHTML = '<span class="dot"></span>' + (status === 'hadir' ? 'Hadir' : 'Telat');
    item.append(av, mid, b);
    logBox.prepend(item);
}

async function submitCode(raw, fromManual) {
    const code = (raw || '').trim();
    if (!code) return;
    const now = Date.now();
    if (!fromManual && code === lastToken && now - lastTime < 3000) return;
    lastToken = code; lastTime = now;
    try {
        const res = await fetch('api/absen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'token=' + encodeURIComponent(code) + '&sesi_id=' + sesiId
        });
        if (res.status === 401) { showResult('err', '✖ Sesi login habis. Muat ulang halaman & login lagi.'); beep(false); return; }
        const data = await res.json();
        if (data.ok) {
            const label = data.status.toUpperCase();
            showResult('ok', data.ulang
                ? `ℹ ${data.nama} (${data.nim}) sudah tercatat ${label} pukul ${data.waktu}`
                : `✔ ${data.nama} (${data.nim}) tercatat ${label} pukul ${data.waktu}`);
            if (!data.ulang) { data.status === 'hadir' ? cntH++ : cntT++; $('cnt-h').textContent = cntH; $('cnt-t').textContent = cntT; }
            addLogEntry(data.nama, data.nim, data.status, data.waktu, data.ulang);
            beep(true);
        } else { showResult('err', '✖ ' + data.message); beep(false); }
    } catch (e) { showResult('err', '✖ Gagal terhubung ke server.'); beep(false); }
}

/* ---------- Input manual / alat scan USB ---------- */
$('manual-form').addEventListener('submit', (e) => {
    e.preventDefault();
    const inp = $('manual-input'); submitCode(inp.value, true); inp.value = ''; inp.focus();
});

/* ---------- Pemindai kamera berlapis ----------
   1) BarcodeDetector bawaan browser (cepat, tanpa library)
   2) html5-qrcode (file lokal assets/js/vendor/ → CDN cadangan)
   3) Input manual di atas                                        */
const stage = $('stage'), video = $('cam'), fb = $('reader-fb');
let stream = null, rafId = null, detector = null, fbScanner = null, facing = 'environment', running = false;

function setRunning(on) {
    running = on;
    $('btn-start').style.display = on ? 'none' : '';
    $('btn-stop').style.display = on ? '' : 'none';
    $('btn-flip').style.display = on ? '' : 'none';
    $('idle').style.display = on ? 'none' : '';
    $('corners').classList.toggle('live', on);
}

async function nativeAvailable() {
    if (!('BarcodeDetector' in window)) return false;
    try { const f = await BarcodeDetector.getSupportedFormats(); return f.includes('qr_code'); } catch (e) { return false; }
}

async function startNative() {
    detector = detector || new BarcodeDetector({ formats: ['qr_code'] });
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: facing }, width: { ideal: 1280 } }, audio: false });
    video.srcObject = stream; video.style.display = 'block'; await video.play();
    let busy = false, last = 0;
    const loop = async (t) => {
        if (!stream) return;
        if (!busy && t - last > 130 && video.readyState >= 2) {
            busy = true; last = t;
            try { const codes = await detector.detect(video); if (codes.length) submitCode(codes[0].rawValue, false); } catch (e) {}
            busy = false;
        }
        rafId = requestAnimationFrame(loop);
    };
    rafId = requestAnimationFrame(loop);
}

function loadScript(src) {
    return new Promise((ok, no) => { const s = document.createElement('script'); s.src = src; s.onload = ok; s.onerror = () => no(); document.head.appendChild(s); });
}

async function ensureH5() {
    if (window.Html5Qrcode) return true;
    const srcs = ['node_modules/html5-qrcode/html5-qrcode.min.js',
        'assets/js/vendor/html5-qrcode.min.js',
        'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js',
        'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js'];
    for (const s of srcs) { try { await loadScript(s); if (window.Html5Qrcode) return true; } catch (e) {} }
    return false;
}

async function startFallback() {
    if (!(await ensureH5())) throw new Error('LIB');
    fb.style.display = 'block';
    fbScanner = new Html5Qrcode('reader-fb');
    await fbScanner.start({ facingMode: facing }, { fps: 10, qrbox: { width: 240, height: 240 } }, (txt) => submitCode(txt, false));
}

async function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        $('secure-warn').style.display = 'block';
        showResult('err', '✖ Kamera tidak tersedia di koneksi ini. Pakai input manual.');
        return;
    }
    showResult('', 'Menyalakan kamera…');
    try {
        if (await nativeAvailable()) await startNative(); else await startFallback();
        setRunning(true);
        showResult('', 'Arahkan kamera ke QR Code mahasiswa.');
    } catch (err) {
        await stopCamera();
        let msg = 'Tidak bisa mengakses kamera.';
        if (err && err.message === 'LIB') msg = 'Pustaka pemindai tidak tersedia. Jalankan npm install atau cek internet untuk CDN, lalu coba lagi.';
        else if (err && err.name === 'NotAllowedError') msg = 'Izin kamera ditolak. Izinkan kamera di pengaturan browser lalu coba lagi.';
        else if (err && err.name === 'NotFoundError') msg = 'Kamera tidak ditemukan di perangkat ini.';
        else if (err && err.name === 'NotReadableError') msg = 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
        else if (!window.isSecureContext) msg = 'Kamera hanya dapat dipakai lewat HTTPS atau http://localhost. Buka alamat localhost atau gunakan input manual.';
        showResult('err', '✖ ' + msg);
    }
}

async function stopCamera() {
    if (rafId) cancelAnimationFrame(rafId); rafId = null;
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    video.srcObject = null; video.style.display = 'none';
    if (fbScanner) { try { await fbScanner.stop(); fbScanner.clear(); } catch (e) {} fbScanner = null; }
    fb.style.display = 'none';
    setRunning(false);
}

$('btn-start').addEventListener('click', startCamera);
$('btn-stop').addEventListener('click', stopCamera);
$('btn-flip').addEventListener('click', async () => { facing = facing === 'environment' ? 'user' : 'environment'; await stopCamera(); startCamera(); });
if (!window.isSecureContext) $('secure-warn').style.display = 'block';
window.addEventListener('pagehide', stopCamera);
</script>
</body>
</html>
