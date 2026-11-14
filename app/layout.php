<?php

function icon(string $name): string
{
    $p = [
        'dashboard' => '<path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/>',
        'profil' => '<path d="M12 12a4 4 0 100-8 4 4 0 000 8zM4 21c0-4 3.6-6 8-6s8 2 8 6"/>',
        'siswa' => '<path d="M9 12a4 4 0 100-8 4 4 0 000 8zM2 21c0-3.3 3.1-5 7-5s7 1.7 7 5M17 8h6M20 5v6"/>',
        'mapel' => '<path d="M4 5a2 2 0 012-2h13v18H6a2 2 0 01-2-2zM8 7h7M8 11h7"/>',
        'tp' => '<path d="M9 18h6M10 21h4M12 3a6 6 0 013.5 10.9c-.9.7-1.5 1.4-1.5 2.6h-4c0-1.2-.6-1.9-1.5-2.6A6 6 0 0112 3z"/>',
        'nilai_formatif' => '<path d="M4 19V5M4 19h16M8 16V9M12 16v-4M16 16v-7"/>',
        'nilai_sumatif' => '<path d="M9 11l3 3 6-6M4 20h16M6 4h12v4H6z"/>',
        'ekstra' => '<path d="M12 3l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9z"/>',
        'nilai_ekstra' => '<path d="M4 5h16v14H4zM8 9h8M8 13h5"/>',
        'nilai_kokurikuler' => '<path d="M9 5h6M9 3h6v4H9zM7 5H5v16h14V5h-2M9 12h6M9 16h4"/>',
        'kehadiran' => '<path d="M7 3h10v4H7zM5 5h2M17 5h2v16H5V5M9 14l2 2 4-4"/>',
        'catatan_wali' => '<path d="M4 4h16v12H8l-4 4zM8 8h8M8 12h5"/>',
        'cetak' => '<path d="M7 8V3h10v5M7 17H5a2 2 0 01-2-2v-4a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2h-2M7 14h10v7H7z"/>',
        'sampul' => '<path d="M6 3h8l4 4v14H6zM14 3v4h4M9 12h6M9 16h6"/>',
        'backup' => '<path d="M12 3v10m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>',
        'users' => '<path d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 11a4 4 0 100-8 4 4 0 000 8M21 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8"/>',
        'logout' => '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>',
    ];
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($p[$name] ?? $p['dashboard']) . '</svg>';
}

function menu_items(): array
{
    $all = [
        ['dashboard', 'Dashboard', 'dashboard', []],
        ['profil', 'Profil', 'profil', []],
        ['siswa', 'Data Siswa', 'siswa', []],
        ['mapel', 'Mata Pelajaran', 'mapel', []],
        ['tp', 'Tujuan Pembelajaran', 'tp', []],
        ['nilai_formatif', 'Nilai Formatif', 'nilai_formatif', []],
        ['nilai_sumatif', 'Nilai Sumatif', 'nilai_sumatif', []],
        ['nilai_kokurikuler', 'Nilai Kokurikuler', 'nilai_kokurikuler', []],
        ['ekstra', 'Ekstrakurikuler', 'ekstra', []],
        ['nilai_ekstra', 'Nilai Ekstrakurikuler', 'nilai_ekstra', []],
        ['kehadiran', 'Kehadiran Siswa', 'kehadiran', []],
        ['catatan_wali', 'Catatan Wali Kelas', 'catatan_wali', []],
        ['leger', 'Leger Nilai', 'nilai_sumatif', []],
        ['cetak', 'Cetak Raport', 'cetak', []],
        ['sampul', 'Cetak Sampul Raport', 'sampul', []],
        ['backup', 'Backup & Restore', 'backup', []],
        ['users', 'Pengguna', 'users', []],
    ];
    return array_values(array_filter($all, fn($i) => menu_allowed($i[0])));
}

function render_layout(string $content, string $title, string $page): string
{
    $u = user();
    $p = profil();
    $items = menu_items();
    $flashes = flash_pop();
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
    <div class="app">
      <aside class="sidebar" id="sidebar">
        <div class="brand">
          <div class="brand-mark">R</div>
          <div>
            <div class="brand-name"><?= e(APP_SHORT) ?></div>
            <div class="brand-sub"><?= e(mb_substr($p['sekolah'] ?: 'Sekolah Dasar', 0, 24)) ?></div>
          </div>
        </div>
        <nav class="nav">
          <?php foreach ($items as [$key, $label, $ic]): ?>
            <a class="nav-item<?= $page === $key ? ' active' : '' ?>" href="index.php?page=<?= e($key) ?>"><?= icon($ic) ?><span><?= e($label) ?></span></a>
          <?php endforeach; ?>
          <div class="nav-sep">Akun</div>
          <a class="nav-item" href="index.php?page=logout"><?= icon('logout') ?><span>Keluar</span></a>
        </nav>
      </aside>
      <div class="main">
        <header class="topbar">
          <button class="btn-icon" id="toggleSidebar" type="button" aria-label="Menu">&#9776;</button>
          <div class="topbar-title"><?= e($title) ?></div>
          <form class="class-switch" method="post" action="index.php?page=<?= e($page) ?>" title="Ganti kelas konteks — data kelas lain tetap tersimpan">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="switch_kelas">
            <input type="hidden" name="kembali" value="<?= e($page) ?>">
            <label for="kelasSwitch">Kelas</label>
            <select id="kelasSwitch" name="kelas" onchange="this.form.submit()">
              <?php for ($k = 1; $k <= 6; $k++): ?>
                <option value="<?= $k ?>"<?= (int) $p['kelas_aktif'] === $k ? ' selected' : '' ?>><?= $k ?></option>
              <?php endfor; ?>
            </select>
          </form>
          <div class="topbar-user">
            <span class="chip"><?= e(role_label($u['role'])) ?></span>
            <strong><?= e($u['nama']) ?></strong>
          </div>
        </header>
        <div class="content">
          <?php foreach ($flashes as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
          <?php endforeach; ?>
          <?= $content ?>
        </div>
        <footer class="footer">© <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; Semester <?= (int) $p['semester'] ?> &middot; Tahun Ajaran <?= e($p['ta']) ?> &middot; Kelas <?= (int) $p['kelas_aktif'] ?> (<?= e($p['fase']) ?>)</footer>
      </div>
    </div>
    <script src="assets/app.js"></script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

function render_auth(string $content, string $title): string
{
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    </head>
    <body class="auth-body">
    <div class="auth-wrap">
      <div class="auth-brand">
        <div class="brand-mark big">R</div>
        <h1><?= e(APP_NAME) ?></h1>
        <p>Raport digital kelas 1-6 &middot; Semester 1 &amp; 2 &middot; Kurikulum Merdeka</p>
      </div>
      <div class="card auth-card">
        <?= $content ?>
      </div>
    </div>
    <script src="assets/app.js"></script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

function context_bar(): string
{
    $c = ctx();
    return '<div class="ctx-bar"><span class="ctx-item"><b>Kelas</b> ' . $c['kelas'] . '</span>'
        . '<span class="ctx-item"><b>Fase</b> ' . e($c['fase']) . '</span>'
        . '<span class="ctx-item"><b>Semester</b> ' . $c['semester'] . '</span>'
        . '<span class="ctx-item"><b>Tahun Ajaran</b> ' . e($c['ta']) . '</span>'
        . '<a class="ctx-link" href="index.php?page=profil">Ubah di Profil</a></div>';
}

function page_head(string $title, string $subtitle = '', string $right = ''): void
{
    echo '<div class="page-head"><div><h2>' . e($title) . '</h2>';
    if ($subtitle !== '') {
        echo '<p>' . e($subtitle) . '</p>';
    }
    echo '</div>' . ($right ? '<div class="page-actions">' . $right . '</div>' : '') . '</div>';
}
