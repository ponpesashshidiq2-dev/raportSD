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
        migrate_dimensi_ket($pdo);
        migrate_kelas_tabel($pdo);
        migrate_sumatif_kolom($pdo);
        migrate_user_scope($pdo);
        migrate_tp_kelas($pdo);
        migrate_jilid($pdo);
        migrate_cp_user($pdo);
        migrate_profil_tambahan($pdo);
        migrate_tanggal_context($pdo);
        migrate_kelas_nama($pdo);
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

function scope_user(string $kolom = 'user_id'): string
{
    $u = user();
    if (!$u || in_array($u['role'], ['admin', 'kepsek'], true)) {
        return '';
    }
    return ' AND ' . $kolom . '=' . (int) $u['id'];
}

function scope_status(int $kelas): string
{
    if ($kelas >= 1 && $kelas <= 6) {
        return "(status='aktif' OR (status='pindah' AND kelas=$kelas))";
    }
    return "status='aktif'";
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
        'catatan_wali' => ['admin', 'wali'],
        'leger' => ['admin', 'wali', 'kepsek'],
        'cetak' => ['admin', 'wali', 'kepsek'],
        'sampul' => ['admin', 'wali', 'kepsek'],
        'backup' => ['admin', 'wali', 'kepsek'],
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
        'catatan_wali' => 'Catatan Wali Kelas',
        'leger' => 'Leger Nilai',
        'cetak' => 'Cetak Raport',
        'sampul' => 'Cetak Sampul Raport',
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
            'ta' => ta_oto($p),
            'semester' => (int) $p['semester'],
            'kelas' => (int) $p['kelas_aktif'],
            'fase' => $p['fase'] ?: fase_of((int) $p['kelas_aktif']),
        ];
    }
    return $c;
}

function ta_oto(?array $p = null): string
{
    $u = user();
    $ang = (int) ($u['angkatan'] ?? 0);
    $p = $p ?? profil();
    $k = (int) ($p['kelas_aktif'] ?? 1);
    if ($ang > 0 && $k >= 1 && $k <= 6) {
        $start = $ang + $k - 1;
        return $start . '/' . ($start + 1);
    }
    return (string) $p['ta'];
}

function tanggal_ctx(string $kolom, ?int $kelas = null, ?int $sem = null): string
{
    if (!in_array($kolom, ['tanggal_rapor', 'tanggal_biodata'], true)) {
        return '';
    }
    $c = ctx();
    $k = $kelas ?? (int) $c['kelas'];
    $s = $sem ?? (int) $c['semester'];
    $r = one("SELECT $kolom AS v FROM tanggal_context WHERE kelas=? AND semester=?", [$k, $s]);
    $v = $r === null ? trim((string) (profil()[$kolom] ?? '')) : trim((string) $r['v']);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
}

function tanggal_rapor_ctx(?int $kelas = null, ?int $sem = null): string
{
    return tanggal_ctx('tanggal_rapor', $kelas, $sem);
}

function tanggal_biodata_ctx(?int $kelas = null, ?int $sem = null): string
{
    return tanggal_ctx('tanggal_biodata', $kelas, $sem);
}

function tanggal_ctx_set(int $kelas, int $sem, string $rapor, string $biodata): void
{
    $data = [
        'tanggal_rapor' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $rapor) ? $rapor : '',
        'tanggal_biodata' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $biodata) ? $biodata : '',
    ];
    if (one('SELECT kelas FROM tanggal_context WHERE kelas=? AND semester=?', [$kelas, $sem])) {
        update_row('tanggal_context', $data, 'kelas=? AND semester=?', [$kelas, $sem]);
    } else {
        insert_row('tanggal_context', $data + ['kelas' => $kelas, 'semester' => $sem]);
    }
}

function fase_of(int $kelas): string
{
    if ($kelas <= 2) return 'A';
    if ($kelas <= 4) return 'B';
    return 'C';
}

function kelas_angkatan(): ?int
{
    $u = user();
    if (!$u) {
        return null;
    }
    $ang = (int) ($u['angkatan'] ?? 0);
    if ($ang <= 0) {
        return null;
    }
    $taStart = (int) substr((string) (ctx()['ta'] ?? ''), 0, 4);
    return $taStart - $ang + 1;
}

function naik_kelas_otomatis(): void
{
    static $selesai = false;
    if ($selesai) {
        return;
    }
    $selesai = true;

    $ta = ta_oto();
    $start = (int) substr($ta, 0, 4);
    if ($start < 2000 || $start > 2100) {
        return;
    }
    $kini = (int) date('Y');
    $last = (int) substr((string) (profil()['ta_promosi'] ?? ''), 0, 4);
    if ($last < 2000) {
        update_row('profil', ['ta_promosi' => (string) max(2000, min($start, $kini))], 'id=?', [1]);
        return;
    }
    if ($start <= $last || $start > $kini) {
        return;
    }

    $nTahun = min(6, $start - $last);
    $n = 0;
    for ($i = 0; $i < $nTahun; $i++) {
        $n = run("UPDATE siswa SET kelas = kelas + 1 WHERE status='aktif' AND kelas < 6");
    }
    update_row('profil', ['ta_promosi' => (string) $start], 'id=?', [1]);
    flash_set('success', 'Tahun ajaran ' . $ta . ' terdeteksi — ' . $n . ' siswa otomatis dinaikkan satu tingkat kelas.');
}

function angkatan_lock_alert(): string
{
    $ka = kelas_angkatan();
    if ($ka === null) {
        return '';
    }
    $ang = (int) (user()['angkatan'] ?? 0);
    if ($ka >= 1 && $ka <= 6) {
        return '<div class="alert alert-info">Daftar siswa akun ini terkunci ke <b>angkatan ' . $ang . '</b> (siswa Kelas ' . $ka . ') — tetap tampil walau kelas/semester diganti; nilai mengikuti kelas yang dibuka.</div>';
    }
    return '<div class="alert alert-warning">Akun ini terkunci ke <b>angkatan ' . $ang . '</b>, tetapi kelas angkatan di luar 1-6 sehingga tidak ada siswa yang cocok. Periksa tahun angkatan pada menu <a href="index.php?page=siswa">Data Siswa</a>.</div>';
}

function teks_kosong_siswa(?int $kaKunci, int $kelas): string
{
    if ($kaKunci !== null) {
        return 'Belum ada siswa pada daftar angkatan Anda. Periksa tahun angkatan pada menu <a href="index.php?page=siswa">Data Siswa</a>.';
    }
    return 'Belum ada siswa pada kelas ' . $kelas . '. <a href="index.php?page=siswa">Tambah data siswa</a>.';
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

function kelas_sahabat(int $k): string
{
    static $daftar = [
        1 => 'Abu Bakar Ash-Shiddiq',
        2 => 'Umar Bin Khattab',
        3 => 'Ustman Bin Affan',
        4 => 'Ali bin Abi Thalib',
        5 => 'Thalhah bin Ubaidillah',
        6 => 'Zubair bin Awwam',
    ];
    return $daftar[$k] ?? '';
}

function kelas_label(int $k): string
{
    $s = kelas_sahabat($k);
    return 'Kelas ' . $k . ($s !== '' ? ' ' . $s : '');
}

function ta_start(string $ta): int
{
    return (int) substr($ta, 0, 4);
}

function seq(string $ta, int $sem): int
{
    return ta_start($ta) * 2 + $sem;
}

function avg_formatif(int $siswa, int $mapel, string $ta, int $sem, ?int $kelas = null): ?float
{
    $v = scalar('SELECT AVG(nilai) FROM nilai_formatif WHERE siswa_id=? AND mapel_id=? AND ta=? AND semester=?',
        [$siswa, $mapel, $ta, $sem]);
    return $v === null ? null : (float) $v;
}

function avg_sumatif(int $siswa, int $mapel, string $ta, int $sem, ?int $kelas = null): ?float
{
    $ambil = fn(string $tipe) => scalar(
        'SELECT AVG(n.nilai) FROM nilai_sumatif n JOIN sumatif_jenis j ON j.id=n.jenis_id'
        . ' WHERE n.siswa_id=? AND n.mapel_id=? AND n.ta=? AND n.semester=? AND j.tipe=?',
        [$siswa, $mapel, $ta, $sem, $tipe]
    );
    $l = $ambil('lingkup');
    $a = $ambil('aes');
    if ($l === null && $a === null) {
        return null;
    }
    if ($l === null) {
        return round((float) $a, 1);
    }
    if ($a === null) {
        return round((float) $l, 1);
    }
    return round(((float) $l + (float) $a) / 2, 1);
}

function sumatif_kolom_list(int $mapel, int $kelas, string $ta, int $sem, int $uid): array
{
    return all(
        "SELECT * FROM sumatif_jenis WHERE mapel_id=? AND kelas=? AND ta=? AND semester=? AND user_id=?
         ORDER BY CASE tipe WHEN 'lingkup' THEN 0 ELSE 1 END, urutan, id",
        [$mapel, $kelas, $ta, $sem, $uid]
    );
}

function sumatif_aes_pastikan(int $mapel, int $kelas, string $ta, int $sem, int $uid): void
{
    foreach ([1 => 'Asesmen Akhir Semester Non-Tes', 2 => 'Asesmen Akhir Semester Tes'] as $urutan => $nama) {
        $ada = one(
            'SELECT id FROM sumatif_jenis WHERE mapel_id=? AND kelas=? AND ta=? AND semester=? AND user_id=? AND nama=?',
            [$mapel, $kelas, $ta, $sem, $uid, $nama]
        );
        if (!$ada) {
            insert_row('sumatif_jenis', [
                'mapel_id' => $mapel, 'ta' => $ta, 'semester' => $sem, 'kelas' => $kelas,
                'user_id' => $uid, 'tipe' => 'aes', 'urutan' => $urutan, 'nama' => $nama, 'tanggal' => '',
            ]);
        }
    }
}

function nilai_akhir(int $siswa, int $mapel, string $ta, int $sem, ?int $kelas = null): ?float
{
    $s = avg_sumatif($siswa, $mapel, $ta, $sem, $kelas ?? (int) ctx()['kelas']);
    return $s === null ? null : round((float) $s, 1);
}

function predikat(?float $n): string
{
    if ($n === null) return '-';
    if ($n >= 90) return 'A';
    if ($n >= 80) return 'B';
    if ($n >= 70) return 'C';
    return 'D';
}

function nilai_sebelumnya(int $siswa, int $mapel, string $ta, int $sem, ?int $kelas = null): ?float
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
    return nilai_akhir($siswa, $mapel, $pta, $psem, $kelas);
}

function cp_pilih(array $rows): ?array
{
    $uid = (int) (user()['id'] ?? 0);
    $bersama = null;
    foreach ($rows as $r) {
        $rid = (int) ($r['user_id'] ?? 0);
        if ($rid === $uid) {
            return $r;
        }
        if ($rid === 0 && $bersama === null) {
            $bersama = $r;
        }
    }
    return $bersama;
}

function cp_baca(int $mapel, string $fase, int $jilid): ?array
{
    return cp_pilih(all('SELECT * FROM cp WHERE mapel_id=? AND fase=? AND jilid=?', [$mapel, $fase, $jilid]));
}

function capaian_kompetensi(int $siswa, int $mapel, string $ta, int $sem, string $fase, string $nama = '', ?int $kelas = null): array
{
    $rows = all(
        'SELECT tp.id, tp.deskripsi, AVG(n.nilai) AS rata FROM nilai_formatif n
         JOIN tp ON tp.id = n.tp_id
         WHERE n.siswa_id=? AND n.mapel_id=? AND n.ta=? AND n.semester=?'
         . ' GROUP BY tp.id, tp.deskripsi ORDER BY rata ASC',
        [$siswa, $mapel, $ta, $sem]
    );
    if (!$rows) {
        $cp = null;
        if (is_tahsin(one('SELECT nama FROM mapel WHERE id=?', [$mapel]))) {
            $j = jilid_siswa($siswa, $mapel, $ta, $sem);
            $cp = cp_pilih(all("SELECT * FROM cp WHERE mapel_id=? AND fase IN ('',?) AND jilid IN (?,0)
                ORDER BY jilid DESC", [$mapel, $fase, $j]));
        }
        $cp ??= cp_pilih(all('SELECT * FROM cp WHERE mapel_id=? AND fase=? AND jilid=0', [$mapel, $fase]));
        $text = $cp ? $cp['deskripsi'] : 'Belum ada data penilaian formatif.';
        return [
            'tinggi' => 'Capaian pembelajaran: ' . $text,
            'rendah' => 'Penilaian formatif belum diisi, sehingga capaian terendah belum dapat ditentukan.',
        ];
    }
    $low = $rows[0];
    $high = $rows[count($rows) - 1];
    $awal = $nama !== '' ? (preg_match('/^ananda\b/i', $nama) ? $nama : 'Ananda ' . $nama) . ' ' : '';
    $t1 = $awal . sprintf('menunjukkan penguasaan dalam %s.', $high['deskripsi']);
    if (count($rows) === 1) {
        $t2 = $awal . sprintf('perlu dipertahankan dan diperdalam dalam %s.', $low['deskripsi']);
    } else {
        $t2 = $awal . sprintf('perlu bantuan dalam %s.', $low['deskripsi']);
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
        'SELECT * FROM mapel WHERE aktif=1 AND kkm IS NOT NULL AND kkm<>""'
        . ' AND ? BETWEEN kelas_min AND kelas_max'
        . ' ORDER BY muatan, urutan, nama',
        [$kelas]
    );
}

function mapel_kelas(int $kelas): array
{
    return all(
        'SELECT * FROM mapel WHERE aktif=1 AND ? BETWEEN kelas_min AND kelas_max'
        . ' ORDER BY muatan, urutan, nama',
        [$kelas]
    );
}

function is_tahsin(?array $mapel): bool
{
    return is_array($mapel)
        && str_contains(mb_strtolower((string) ($mapel['nama'] ?? '')), 'tahsin');
}

function jilid_siswa(int $siswa, int $mapel, string $ta, int $sem): int
{
    $v = scalar('SELECT jilid FROM siswa_jilid WHERE siswa_id=? AND mapel_id=? AND ta=? AND semester=?',
        [$siswa, $mapel, $ta, $sem]);
    return $v === null ? 0 : (int) $v;
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

function nama_kelas(int $k): string
{
    $v = scalar('SELECT nama FROM kelas_nama WHERE kelas=?', [$k]);
    return trim((string) ($v ?? ''));
}

function kalimat_ananda(string $nama, string $teks): string
{
    $nama = trim($nama);
    $teks = trim($teks);
    if ($nama === '') {
        return $teks;
    }
    if ($teks === '') {
        return 'Ananda ' . $nama;
    }
    if (preg_match('/^ananda\b[[:space:]]*/iu', $teks, $m)) {
        $sisa = trim(substr($teks, strlen($m[0])));
        return 'Ananda ' . $nama . ($sisa !== '' ? ' ' . $sisa : '');
    }
    return 'Ananda ' . $nama . ' ' . $teks;
}
