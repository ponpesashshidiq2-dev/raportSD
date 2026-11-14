<?php

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        if (!is_dir(DATA_DIR)) {
            mkdir(DATA_DIR, 0777, true);
        }
        $fresh = !file_exists(DB_FILE);
        $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        if ($fresh || !table_exists($pdo, 'users')) {
            install_db($pdo);
        }
        migrate_walikelas($pdo);
        migrate_users($pdo);
        migrate_siswa_detail($pdo);
        migrate_catatan_wali($pdo);
        migrate_ekstra_deskripsi($pdo);
        migrate_kokurikuler($pdo);
        migrate_tp_kelas($pdo);
    }
    return $pdo;
}

function table_exists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
    $st->execute([$table]);
    return (bool) $st->fetch();
}

function all(string $sql, array $p = []): array
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->fetchAll();
}

function one(string $sql, array $p = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($p);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function scalar(string $sql, array $p = [])
{
    $st = db()->prepare($sql);
    $st->execute($p);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}

function run(string $sql, array $p = []): int
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st->rowCount();
}

function insert_row(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' .
        implode(',', array_fill(0, count($cols), '?')) . ')';
    run($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function update_row(string $table, array $data, string $where, array $wp): int
{
    $sets = [];
    foreach (array_keys($data) as $c) {
        $sets[] = $c . '=?';
    }
    $sql = 'UPDATE ' . $table . ' SET ' . implode(',', $sets) . ' WHERE ' . $where;
    $n = substr_count($where, '?');
    return run($sql, array_merge(array_values($data), array_slice($wp, 0, $n)));
}

function e($s): string
{
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $page, array $qs = []): void
{
    $url = 'index.php?page=' . urlencode($page);
    foreach ($qs as $k => $v) {
        $url .= '&' . urlencode($k) . '=' . urlencode((string) $v);
    }
    header('Location: ' . $url);
    exit;
}

function flash_set(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_pop(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $t = $_POST['_token'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        exit('Token keamanan tidak valid. Silakan muat ulang halaman.');
    }
}

function user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $u = null;
    static $id = null;
    if ($u === null || $id !== $_SESSION['user_id']) {
        $id = $_SESSION['user_id'];
        $u = one('SELECT * FROM users WHERE id=? AND aktif=1', [$id]);
        if (!$u) {
            unset($_SESSION['user_id']);
            $u = null;
        }
    }
    return $u;
}

function is_admin(): bool
{
    $u = user();
    return $u && $u['role'] === 'admin';
}

function can_edit(): bool
{
    $u = user();
    return $u && in_array($u['role'], ['admin', 'wali'], true);
}

function role_label(string $r): string
{
    return ['admin' => 'Administrator', 'wali' => 'Wali Kelas', 'kepsek' => 'Kepala Sekolah'][$r] ?? $r;
}

function menu_allowed(string $page): bool
{
    $map = [
        'dashboard' => ['admin', 'wali', 'kepsek'],
        'profil' => ['admin', 'wali'],
        'siswa' => ['admin', 'wali', 'kepsek'],
        'mapel' => ['admin', 'wali', 'kepsek'],
        'tp' => ['admin', 'wali', 'kepsek'],
        'nilai_formatif' => ['admin', 'wali'],
        'nilai_sumatif' => ['admin', 'wali'],
        'ekstra' => ['admin', 'wali', 'kepsek'],
        'nilai_ekstra' => ['admin', 'wali'],
        'nilai_kokurikuler' => ['admin', 'wali'],
        'kehadiran' => ['admin', 'wali'],
        'cetak' => ['admin', 'wali', 'kepsek'],
        'backup' => ['admin'],
        'users' => ['admin'],
    ];
    $u = user();
    if (!$u) {
        return false;
    }
    return in_array($u['role'], $map[$page] ?? ['admin'], true);
}

function page_title(string $page): string
{
    $t = [
        'dashboard' => 'Dashboard',
        'profil' => 'Profil Sekolah',
        'siswa' => 'Data Siswa',
        'mapel' => 'Mata Pelajaran',
        'tp' => 'Tujuan Pembelajaran',
        'nilai_formatif' => 'Input Nilai Formatif',
        'nilai_sumatif' => 'Input Nilai Sumatif',
        'ekstra' => 'Ekstrakurikuler',
        'nilai_ekstra' => 'Input Nilai Ekstrakurikuler',
        'nilai_kokurikuler' => 'Input Nilai Kokurikuler',
        'kehadiran' => 'Kehadiran Siswa',
        'cetak' => 'Cetak Raport',
        'backup' => 'Backup & Restore',
        'users' => 'Pengguna',
    ];
    return $t[$page] ?? 'Raport';
}

function login_attempt(string $username, string $password): bool
{
    $u = one('SELECT * FROM users WHERE username=? AND aktif=1', [$username]);
    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        return true;
    }
    return false;
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

function profil(): array
{
    $p = one('SELECT * FROM profil WHERE id=1');
    if (!$p) {
        insert_row('profil', ['id' => 1, 'ta' => '2025/2026', 'semester' => 1, 'kelas_aktif' => 1, 'fase' => 'A']);
        $p = one('SELECT * FROM profil WHERE id=1');
    }
    return $p;
}

function ctx(): array
{
    static $c = null;
    if ($c === null) {
        $p = profil();
        $c = [
            'ta' => $p['ta'],
            'semester' => (int) $p['semester'],
            'kelas' => (int) $p['kelas_aktif'],
            'fase' => $p['fase'] ?: fase_of((int) $p['kelas_aktif']),
        ];
    }
    return $c;
}

function fase_of(int $kelas): string
{
    if ($kelas <= 2) return 'A';
    if ($kelas <= 4) return 'B';
    return 'C';
}

function kelas_of_fase(string $fase): array
{
    if ($fase === 'B') return [3, 4];
    if ($fase === 'C') return [5, 6];
    return [1, 2];
}

function fase_label(string $f): string
{
    return ['A' => 'Fase A (Kelas 1-2)', 'B' => 'Fase B (Kelas 3-4)', 'C' => 'Fase C (Kelas 5-6)'][$f] ?? 'Fase ' . $f;
}

function ta_start(string $ta): int
{
    return (int) substr($ta, 0, 4);
}

function seq(string $ta, int $sem): int
{
    return ta_start($ta) * 2 + $sem;
}

function avg_formatif(int $siswa, int $mapel, string $ta, int $sem): ?float
{
    $v = scalar('SELECT AVG(nilai) FROM nilai_formatif WHERE siswa_id=? AND mapel_id=? AND ta=? AND semester=?',
        [$siswa, $mapel, $ta, $sem]);
    return $v === null ? null : (float) $v;
}

function avg_sumatif(int $siswa, int $mapel, string $ta, int $sem): ?float
{
    $v = scalar('SELECT AVG(nilai) FROM nilai_sumatif WHERE siswa_id=? AND mapel_id=? AND ta=? AND semester=?',
        [$siswa, $mapel, $ta, $sem]);
    return $v === null ? null : (float) $v;
}

function nilai_akhir(int $siswa, int $mapel, string $ta, int $sem): ?float
{
    $f = avg_formatif($siswa, $mapel, $ta, $sem);
    $s = avg_sumatif($siswa, $mapel, $ta, $sem);
    if ($f === null && $s === null) {
        return null;
    }
    $p = profil();
    $wf = (float) ($p['bobot_formatif'] ?? DEFAULT_BOBOT_FORMATIF);
    $ws = (float) ($p['bobot_sumatif'] ?? DEFAULT_BOBOT_SUMATIF);
    if ($f !== null && $s !== null) {
        $tot = $wf + $ws;
        if ($tot <= 0) $tot = 100;
        return round(($f * $wf + $s * $ws) / $tot, 1);
    }
    return round($f !== null ? $f : $s, 1);
}

function predikat(?float $n): string
{
    if ($n === null) return '-';
    if ($n >= 90) return 'A';
    if ($n >= 80) return 'B';
    if ($n >= 70) return 'C';
    return 'D';
}

function nilai_sebelumnya(int $siswa, int $mapel, string $ta, int $sem): ?float
{
    $cur = seq($ta, $sem);
    $kunci = [];
    foreach (['nilai_formatif', 'nilai_sumatif'] as $t) {
        foreach (all("SELECT DISTINCT ta, semester FROM $t WHERE siswa_id=? AND mapel_id=?", [$siswa, $mapel]) as $r) {
            $s = seq($r['ta'], (int) $r['semester']);
            if ($s < $cur) {
                $kunci[$s] = [$r['ta'], (int) $r['semester']];
            }
        }
    }
    if (!$kunci) {
        return null;
    }
    krsort($kunci);
    [$pta, $psem] = reset($kunci);
    return nilai_akhir($siswa, $mapel, $pta, $psem);
}

function capaian_kompetensi(int $siswa, int $mapel, string $ta, int $sem, string $fase): array
{
    $rows = all(
        'SELECT tp.id, tp.deskripsi, AVG(n.nilai) AS rata FROM nilai_formatif n
         JOIN tp ON tp.id = n.tp_id
         WHERE n.siswa_id=? AND n.mapel_id=? AND n.ta=? AND n.semester=?
         GROUP BY tp.id, tp.deskripsi ORDER BY rata ASC',
        [$siswa, $mapel, $ta, $sem]
    );
    if (!$rows) {
        $cp = one('SELECT deskripsi FROM cp WHERE mapel_id=? AND fase=?', [$mapel, $fase]);
        $text = $cp ? $cp['deskripsi'] : 'Belum ada data penilaian formatif.';
        return [
            'tinggi' => 'Capaian pembelajaran: ' . $text,
            'rendah' => 'Penilaian formatif belum diisi, sehingga capaian terendah belum dapat ditentukan.',
        ];
    }
    $low = $rows[0];
    $high = $rows[count($rows) - 1];
    $t1 = sprintf('Capaian tertinggi: mampu %s (nilai %s).', $high['deskripsi'], format_nilai((float) $high['rata']));
    if (count($rows) === 1) {
        $t2 = sprintf('Perlu dipertahankan dan diperdalam: %s (nilai %s).', $low['deskripsi'], format_nilai((float) $low['rata']));
    } else {
        $t2 = sprintf('Perlu penguatan: %s (nilai %s).', $low['deskripsi'], format_nilai((float) $low['rata']));
    }
    return ['tinggi' => $t1, 'rendah' => $t2];
}

function format_nilai(float $n): string
{
    return (string) (floor($n) == $n ? (int) $n : round($n, 1));
}

function mapel_raport(int $kelas): array
{
    return all(
        'SELECT * FROM mapel WHERE aktif=1 AND kkm IS NOT NULL AND kkm<>""
         AND ? BETWEEN kelas_min AND kelas_max ORDER BY muatan, urutan, nama',
        [$kelas]
    );
}

function mapel_kelas(int $kelas): array
{
    return all(
        'SELECT * FROM mapel WHERE aktif=1 AND ? BETWEEN kelas_min AND kelas_max ORDER BY muatan, urutan, nama',
        [$kelas]
    );
}

function walikelas(int $kelas, ?int $semester = null): array
{
    if ($semester === null) {
        $semester = (int) profil()['semester'];
    }
    return one('SELECT * FROM walikelas WHERE kelas=? AND semester=?', [$kelas, $semester])
        ?? ['kelas' => $kelas, 'semester' => $semester, 'nama' => '', 'nip' => ''];
}

function tgl_indo(?string $date = null): string
{
    $d = $date ? new DateTime($date) : new DateTime('now');
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $d->format('j') . ' ' . $bulan[(int) $d->format('n')] . ' ' . $d->format('Y');
}

function kkm_mapel(int $mapel): ?int
{
    $m = one('SELECT kkm FROM mapel WHERE id=?', [$mapel]);
    if (!$m || $m['kkm'] === null || $m['kkm'] === '') {
        return null;
    }
    return (int) $m['kkm'];
}

function js_alert(string $msg): void
{
    echo '<script>alert(' . json_encode($msg) . ');</script>';
}
