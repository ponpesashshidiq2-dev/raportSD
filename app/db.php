<?php

function schema_sql(): string
{
    return <<<'SQL'
CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  nama TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'wali',
  aktif INTEGER NOT NULL DEFAULT 1,
  angkatan INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);
CREATE TABLE profil (
  id INTEGER PRIMARY KEY CHECK (id=1),
  sekolah TEXT NOT NULL DEFAULT '',
  npsn TEXT NOT NULL DEFAULT '',
  alamat TEXT NOT NULL DEFAULT '',
  kota TEXT NOT NULL DEFAULT '',
  telp TEXT NOT NULL DEFAULT '',
  email TEXT NOT NULL DEFAULT '',
  website TEXT NOT NULL DEFAULT '',
  kepsek TEXT NOT NULL DEFAULT '',
  kepsek_nip TEXT NOT NULL DEFAULT '',
  logo TEXT NOT NULL DEFAULT '',
  tanggal_rapor TEXT NOT NULL DEFAULT '',
  tanggal_biodata TEXT NOT NULL DEFAULT '',
  ta TEXT NOT NULL DEFAULT '2025/2026',
  semester INTEGER NOT NULL DEFAULT 1,
  kelas_aktif INTEGER NOT NULL DEFAULT 1,
  fase TEXT NOT NULL DEFAULT 'A',
  bobot_formatif INTEGER NOT NULL DEFAULT 40,
  bobot_sumatif INTEGER NOT NULL DEFAULT 60
);
CREATE TABLE walikelas (
  kelas INTEGER NOT NULL,
  semester INTEGER NOT NULL DEFAULT 1,
  nama TEXT NOT NULL DEFAULT '',
  nip TEXT NOT NULL DEFAULT '',
  PRIMARY KEY (kelas, semester)
);
CREATE TABLE mapel (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nama TEXT NOT NULL,
  muatan TEXT NOT NULL DEFAULT 'nasional',
  kelas_min INTEGER NOT NULL DEFAULT 1,
  kelas_max INTEGER NOT NULL DEFAULT 6,
  kkm INTEGER,
  aktif INTEGER NOT NULL DEFAULT 1,
  urutan INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE cp (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  mapel_id INTEGER NOT NULL,
  fase TEXT NOT NULL,
  jilid INTEGER NOT NULL DEFAULT 0,
  deskripsi TEXT NOT NULL,
  UNIQUE (mapel_id, fase, jilid)
);
CREATE TABLE tp (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  mapel_id INTEGER NOT NULL,
  fase TEXT NOT NULL,
  kelas INTEGER NOT NULL DEFAULT 1,
  semester INTEGER NOT NULL DEFAULT 1,
  kode TEXT NOT NULL DEFAULT '',
  deskripsi TEXT NOT NULL,
  urutan INTEGER NOT NULL DEFAULT 0,
  jilid INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE siswa (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nis TEXT NOT NULL DEFAULT '',
  nisn TEXT NOT NULL DEFAULT '',
  nama TEXT NOT NULL,
  jk TEXT NOT NULL DEFAULT 'L',
  tempat_lahir TEXT NOT NULL DEFAULT '',
  tanggal_lahir TEXT NOT NULL DEFAULT '',
  tanggal_masuk TEXT NOT NULL DEFAULT '',
  alamat TEXT NOT NULL DEFAULT '',
  ortu TEXT NOT NULL DEFAULT '',
  telp TEXT NOT NULL DEFAULT '',
  kelas INTEGER NOT NULL DEFAULT 1,
  rombel TEXT NOT NULL DEFAULT '',
  status TEXT NOT NULL DEFAULT 'aktif',
  kode_agama TEXT NOT NULL DEFAULT '',
  agama TEXT NOT NULL DEFAULT '',
  pendidikan_sebelumnya TEXT NOT NULL DEFAULT '',
  ayah TEXT NOT NULL DEFAULT '',
  ibu TEXT NOT NULL DEFAULT '',
  kerja_ayah TEXT NOT NULL DEFAULT '',
  kerja_ibu TEXT NOT NULL DEFAULT '',
  ortu_jalan TEXT NOT NULL DEFAULT '',
  ortu_kelurahan TEXT NOT NULL DEFAULT '',
  ortu_kecamatan TEXT NOT NULL DEFAULT '',
  ortu_kota TEXT NOT NULL DEFAULT '',
  ortu_provinsi TEXT NOT NULL DEFAULT '',
  wali_nama TEXT NOT NULL DEFAULT '',
  wali_kerja TEXT NOT NULL DEFAULT '',
  wali_alamat TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);
CREATE TABLE nilai_formatif (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  tp_id INTEGER NOT NULL,
  mapel_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  kelas INTEGER NOT NULL DEFAULT 0,
  nilai REAL NOT NULL,
  UNIQUE (siswa_id, tp_id, ta, semester)
);
CREATE TABLE sumatif_jenis (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  mapel_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  kelas INTEGER NOT NULL DEFAULT 0,
  user_id INTEGER NOT NULL DEFAULT 0,
  tipe TEXT NOT NULL DEFAULT 'lingkup',
  urutan INTEGER NOT NULL DEFAULT 0,
  nama TEXT NOT NULL,
  tanggal TEXT NOT NULL DEFAULT '',
  UNIQUE (mapel_id, kelas, ta, semester, user_id, nama)
);
CREATE TABLE nilai_sumatif (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  mapel_id INTEGER NOT NULL,
  jenis_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  kelas INTEGER NOT NULL DEFAULT 0,
  nilai REAL NOT NULL,
  UNIQUE (siswa_id, jenis_id, ta, semester)
);
CREATE TABLE ekstra (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nama TEXT NOT NULL,
  urutan INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE nilai_ekstra (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  ekstra_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  kelas INTEGER NOT NULL DEFAULT 0,
  predikat TEXT NOT NULL,
  catatan TEXT NOT NULL DEFAULT '',
  UNIQUE (siswa_id, ekstra_id, ta, semester, kelas)
);
CREATE TABLE ekstra_deskripsi (
  ekstra_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  kelas INTEGER NOT NULL,
  deskripsi TEXT NOT NULL DEFAULT '',
  PRIMARY KEY (ekstra_id, ta, semester, kelas)
);
CREATE TABLE kehadiran (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  kelas INTEGER NOT NULL DEFAULT 0,
  sakit INTEGER NOT NULL DEFAULT 0,
  izin INTEGER NOT NULL DEFAULT 0,
  tka INTEGER NOT NULL DEFAULT 0,
  UNIQUE (siswa_id, ta, semester, kelas)
);
CREATE TABLE catatan_wali (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  kelas INTEGER NOT NULL DEFAULT 0,
  teks TEXT NOT NULL DEFAULT '',
  UNIQUE (siswa_id, ta, semester, kelas)
);
CREATE TABLE siswa_jilid (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  mapel_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  jilid INTEGER NOT NULL DEFAULT 0,
  UNIQUE (siswa_id, mapel_id, ta, semester)
);
CREATE TABLE kokurikuler_kegiatan (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nama TEXT NOT NULL,
  tujuan TEXT NOT NULL DEFAULT '',
  urutan INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE kokurikuler_nilai (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  kegiatan_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  kelas INTEGER NOT NULL DEFAULT 0,
  keterangan TEXT NOT NULL DEFAULT '',
  UNIQUE (siswa_id, kegiatan_id, ta, semester, kelas)
);
CREATE TABLE kokurikuler_capaian (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  kelas INTEGER NOT NULL DEFAULT 0,
  predikat TEXT NOT NULL DEFAULT '',
  deskripsi TEXT NOT NULL DEFAULT '',
  desk_manual INTEGER NOT NULL DEFAULT 0,
  UNIQUE (siswa_id, ta, semester, kelas)
);
CREATE INDEX idx_nf_mapel ON nilai_formatif (mapel_id, ta, semester);
CREATE INDEX idx_ns_mapel ON nilai_sumatif (mapel_id, ta, semester);
CREATE INDEX idx_siswa_kelas ON siswa (kelas, status);
CREATE INDEX idx_tp_mapel ON tp (mapel_id, fase);
CREATE INDEX idx_tp_ctx ON tp (mapel_id, kelas, semester);
CREATE INDEX idx_tp_jilid ON tp (mapel_id, semester, jilid);
SQL;
}

function install_db(PDO $pdo): void
{
    require_once APP_DIR . '/app/kurikulum.php';
    $pdo->exec(schema_sql());

    $stmt = $pdo->prepare('INSERT INTO users (username, password, nama, role) VALUES (?,?,?,?)');
    $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrator', 'admin']);
    $stmt->execute(['wali', password_hash('wali123', PASSWORD_DEFAULT), 'Wali Kelas', 'wali']);
    $stmt->execute(['kepsek', password_hash('kepsek123', PASSWORD_DEFAULT), 'Kepala Sekolah', 'kepsek']);

    $pdo->prepare('INSERT INTO profil (id, sekolah, npsn, alamat, kota, telp, email, kepsek, ta, semester, kelas_aktif, fase)
        VALUES (1,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([
            'SD Muhammadiyah Contoh',
            '20123456',
            'Jl. Pendidikan No. 1',
            'Yogyakarta',
            '0274-123456',
            'sekolah@contoh.sch.id',
            'Drs. Budi Santoso, M.Pd.',
            '2025/2026',
            1,
            1,
            'A',
        ]);

    for ($k = 1; $k <= 6; $k++) {
        for ($s = 1; $s <= 2; $s++) {
            $pdo->prepare('INSERT INTO walikelas (kelas, semester, nama, nip) VALUES (?,?,?,?)')->execute([$k, $s, '', '']);
        }
    }

    $mapelId = [];
    $urutan = 0;
    $stmt = $pdo->prepare('INSERT INTO mapel (nama, muatan, kelas_min, kelas_max, kkm, aktif, urutan) VALUES (?,?,?,?,?,?,?)');
    foreach (daftar_mapel() as $m) {
        $stmt->execute([$m['nama'], $m['muatan'], 1, 6, null, 1, ++$urutan]);
        $mapelId[$m['nama']] = (int) $pdo->lastInsertId();
    }

    $cp = daftar_cp();
    $stmt = $pdo->prepare('INSERT INTO cp (mapel_id, fase, deskripsi) VALUES (?,?,?)');
    foreach ($cp as $nama => $fases) {
        foreach ($fases as $fase => $text) {
            $stmt->execute([$mapelId[$nama], $fase, $text]);
        }
    }

    $tp = daftar_tp();
    $stmt = $pdo->prepare('INSERT INTO tp (mapel_id, fase, kelas, semester, kode, deskripsi, urutan) VALUES (?,?,?,?,?,?,?)');
    foreach ($tp as $nama => $fases) {
        foreach ($fases as $fase => $list) {
            foreach (kelas_of_fase($fase) as $k) {
                for ($s = 1; $s <= 2; $s++) {
                    $i = 0;
                    foreach ($list as $desc) {
                        $stmt->execute([$mapelId[$nama], $fase, $k, $s, 'TP.' . $fase . '.' . (++$i), $desc, $i]);
                    }
                }
            }
        }
    }

    $stmt = $pdo->prepare('INSERT INTO ekstra (nama, urutan) VALUES (?,?)');
    foreach (daftar_ekstra() as $ek) {
        $stmt->execute([$ek['nama'], $ek['urutan']]);
    }

    $stmt = $pdo->prepare('INSERT INTO kokurikuler_kegiatan (nama, tujuan, urutan) VALUES (?,?,?)');
    foreach (daftar_kokurikuler() as $kk) {
        $stmt->execute([$kk['nama'], $kk['tujuan'], $kk['urutan']]);
    }
}

function db_tables(): array
{
    return array_map(fn($r) => $r['name'], all("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"));
}

function migrate_walikelas(PDO $pdo): void
{
    $cols = $pdo->query('PRAGMA table_info(walikelas)')->fetchAll(PDO::FETCH_ASSOC);
    if (!$cols || array_filter($cols, fn($c) => ($c['name'] ?? '') === 'semester')) {
        return;
    }
    $rows = $pdo->query('SELECT kelas, nama, nip FROM walikelas')->fetchAll(PDO::FETCH_ASSOC);
    $pdo->exec('DROP TABLE walikelas');
    $pdo->exec("CREATE TABLE walikelas (
      kelas INTEGER NOT NULL,
      semester INTEGER NOT NULL DEFAULT 1,
      nama TEXT NOT NULL DEFAULT '',
      nip TEXT NOT NULL DEFAULT '',
      PRIMARY KEY (kelas, semester)
    )");
    $ins = $pdo->prepare('INSERT INTO walikelas (kelas, semester, nama, nip) VALUES (?,?,?,?)');
    foreach ($rows as $r) {
        for ($s = 1; $s <= 2; $s++) {
            $ins->execute([(int) $r['kelas'], $s, $r['nama'], $r['nip']]);
        }
    }
}

function migrate_users(PDO $pdo): void
{
    $cols = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);
    if ($cols && !array_filter($cols, fn($c) => ($c['name'] ?? '') === 'angkatan')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN angkatan INTEGER NOT NULL DEFAULT 0');
    }
}

function migrate_siswa_detail(PDO $pdo): void
{
    $cols = array_map(fn($c) => $c['name'], $pdo->query('PRAGMA table_info(siswa)')->fetchAll(PDO::FETCH_ASSOC));
    if (!$cols) {
        return;
    }
    $add = [
        'kode_agama', 'agama', 'pendidikan_sebelumnya', 'tanggal_masuk',
        'ayah', 'ibu', 'kerja_ayah', 'kerja_ibu',
        'ortu_jalan', 'ortu_kelurahan', 'ortu_kecamatan', 'ortu_kota', 'ortu_provinsi',
        'wali_nama', 'wali_kerja', 'wali_alamat',
    ];
    foreach ($add as $c) {
        if (!in_array($c, $cols, true)) {
            $pdo->exec("ALTER TABLE siswa ADD COLUMN $c TEXT NOT NULL DEFAULT ''");
        }
    }
}

function migrate_profil_tambahan(PDO $pdo): void
{
    $cols = array_map(fn($c) => $c['name'], $pdo->query('PRAGMA table_info(profil)')->fetchAll(PDO::FETCH_ASSOC));
    if (!$cols) {
        return;
    }
    foreach (['logo', 'tanggal_rapor', 'tanggal_biodata'] as $c) {
        if (!in_array($c, $cols, true)) {
            $pdo->exec("ALTER TABLE profil ADD COLUMN $c TEXT NOT NULL DEFAULT ''");
        }
    }
}

function migrate_catatan_wali(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS catatan_wali (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      siswa_id INTEGER NOT NULL,
      ta TEXT NOT NULL,
      semester INTEGER NOT NULL,
      kelas INTEGER NOT NULL DEFAULT 0,
      teks TEXT NOT NULL DEFAULT '',
      UNIQUE (siswa_id, ta, semester, kelas)
    )");
}

function migrate_ekstra_deskripsi(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS ekstra_deskripsi (
      ekstra_id INTEGER NOT NULL,
      ta TEXT NOT NULL,
      semester INTEGER NOT NULL,
      kelas INTEGER NOT NULL,
      deskripsi TEXT NOT NULL DEFAULT '',
      PRIMARY KEY (ekstra_id, ta, semester, kelas)
    )");
}

function migrate_kokurikuler(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS kokurikuler_kegiatan (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nama TEXT NOT NULL,
      tujuan TEXT NOT NULL DEFAULT '',
      keterangan TEXT NOT NULL DEFAULT '',
      urutan INTEGER NOT NULL DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS kokurikuler_nilai (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      siswa_id INTEGER NOT NULL,
      kegiatan_id INTEGER NOT NULL,
      ta TEXT NOT NULL,
      semester INTEGER NOT NULL,
      kelas INTEGER NOT NULL DEFAULT 0,
      keterangan TEXT NOT NULL DEFAULT '',
      UNIQUE (siswa_id, kegiatan_id, ta, semester, kelas)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS kokurikuler_capaian (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      siswa_id INTEGER NOT NULL,
      ta TEXT NOT NULL,
      semester INTEGER NOT NULL,
      kelas INTEGER NOT NULL DEFAULT 0,
      predikat TEXT NOT NULL DEFAULT '',
      deskripsi TEXT NOT NULL DEFAULT '',
      desk_manual INTEGER NOT NULL DEFAULT 0,
      UNIQUE (siswa_id, ta, semester, kelas)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS kokurikuler_dimensi_ket (
      idx INTEGER NOT NULL,
      kelas INTEGER NOT NULL,
      teks TEXT NOT NULL DEFAULT '',
      PRIMARY KEY (idx, kelas)
    )");
    if ((int) $pdo->query('SELECT COUNT(*) FROM kokurikuler_kegiatan')->fetchColumn() === 0) {
        require_once APP_DIR . '/app/kurikulum.php';
        $stmt = $pdo->prepare('INSERT INTO kokurikuler_kegiatan (nama, tujuan, urutan) VALUES (?,?,?)');
        foreach (daftar_kokurikuler() as $kk) {
            $stmt->execute([$kk['nama'], $kk['tujuan'], $kk['urutan']]);
        }
    }
    $colsKeg = $pdo->query('PRAGMA table_info(kokurikuler_kegiatan)')->fetchAll(PDO::FETCH_ASSOC);
    if (!array_filter($colsKeg, fn($c) => ($c['name'] ?? '') === 'keterangan')) {
        $pdo->exec("ALTER TABLE kokurikuler_kegiatan ADD COLUMN keterangan TEXT NOT NULL DEFAULT ''");
    }
    $colsCap = $pdo->query('PRAGMA table_info(kokurikuler_capaian)')->fetchAll(PDO::FETCH_ASSOC);
    if (!array_filter($colsCap, fn($c) => ($c['name'] ?? '') === 'desk_manual')) {
        $pdo->exec("ALTER TABLE kokurikuler_capaian ADD COLUMN desk_manual INTEGER NOT NULL DEFAULT 0");
    }
}

function migrate_tp_kelas(PDO $pdo): void
{
    $cols = $pdo->query('PRAGMA table_info(tp)')->fetchAll(PDO::FETCH_ASSOC);
    if (!$cols || array_filter($cols, fn($c) => ($c['name'] ?? '') === 'semester')) {
        return;
    }
    $pdo->exec('ALTER TABLE tp ADD COLUMN kelas INTEGER NOT NULL DEFAULT 0');
    $pdo->exec('ALTER TABLE tp ADD COLUMN semester INTEGER NOT NULL DEFAULT 1');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tp_ctx ON tp (mapel_id, kelas, semester)');

    $profil = $pdo->query('SELECT kelas_aktif FROM profil WHERE id=1')->fetch();
    $aktif = $profil ? (int) $profil['kelas_aktif'] : 1;

    $upd = $pdo->prepare('UPDATE tp SET kelas=?, semester=1 WHERE id=?');
    $ins = $pdo->prepare('INSERT INTO tp (mapel_id, fase, kelas, semester, kode, deskripsi, urutan) VALUES (?,?,?,?,?,?,?)');
    foreach ($pdo->query('SELECT * FROM tp WHERE kelas=0 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $fase = $r['fase'];
        $kelasAsal = fase_of($aktif) === $fase ? $aktif : kelas_of_fase($fase)[0];
        $upd->execute([$kelasAsal, $r['id']]);
        foreach (kelas_of_fase($fase) as $k) {
            for ($s = 1; $s <= 2; $s++) {
                if ($k === $kelasAsal && $s === 1) {
                    continue;
                }
                $ins->execute([$r['mapel_id'], $fase, $k, $s, $r['kode'], $r['deskripsi'], (int) $r['urutan']]);
            }
        }
    }

    $get = $pdo->prepare('SELECT mapel_id, fase, kelas, kode FROM tp WHERE id=?');
    $find = $pdo->prepare('SELECT id FROM tp WHERE mapel_id=? AND fase=? AND kelas=? AND semester=2 AND kode=?');
    $updNf = $pdo->prepare('UPDATE nilai_formatif SET tp_id=? WHERE tp_id=? AND semester=2');
    foreach ($pdo->query('SELECT DISTINCT tp_id FROM nilai_formatif WHERE semester=2')->fetchAll(PDO::FETCH_COLUMN) as $tpid) {
        $get->execute([(int) $tpid]);
        $src = $get->fetch();
        if (!$src) {
            continue;
        }
        $find->execute([$src['mapel_id'], $src['fase'], $src['kelas'], $src['kode']]);
        $new = $find->fetchColumn();
        if ($new && (int) $new !== (int) $tpid) {
            $updNf->execute([(int) $new, (int) $tpid]);
        }
    }
}

function kelas_tersedia(PDO $pdo): array
{
    $dariSiswa = array_map('intval', $pdo->query('SELECT DISTINCT kelas FROM siswa WHERE kelas > 0')->fetchAll(PDO::FETCH_COLUMN));
    return array_values(array_unique(array_merge([1, 2, 3, 4, 5, 6], $dariSiswa)));
}

function migrate_dimensi_ket(PDO $pdo): void
{
    $buat = "CREATE TABLE %s (
      idx INTEGER NOT NULL,
      kelas INTEGER NOT NULL,
      teks TEXT NOT NULL DEFAULT '',
      PRIMARY KEY (idx, kelas)
    )";
    if (!table_exists($pdo, 'kokurikuler_dimensi_ket')) {
        $pdo->exec(sprintf($buat, 'kokurikuler_dimensi_ket'));
        return;
    }
    $sqlLama = (string) $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='kokurikuler_dimensi_ket'")->fetchColumn();
    if (!preg_match('/PRIMARY KEY\s*\(\s*idx\s*,\s*kelas\s*\)/i', $sqlLama)) {
        $tmp = 'kokurikuler_dimensi_ket_mig';
        $pdo->beginTransaction();
        try {
            $pdo->exec('DROP TABLE IF EXISTS ' . $tmp);
            $pdo->exec(sprintf($buat, $tmp));
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO $tmp (idx, kelas, teks) VALUES (?,?,?)");
            foreach ($pdo->query('SELECT idx, teks FROM kokurikuler_dimensi_ket') as $r) {
                $teks = trim((string) $r['teks']);
                if ($teks === '') {
                    continue;
                }
                foreach (kelas_tersedia($pdo) as $k) {
                    $stmt->execute([(int) $r['idx'], $k, $teks]);
                }
            }
            $pdo->exec('DROP TABLE kokurikuler_dimensi_ket');
            $pdo->exec('ALTER TABLE ' . $tmp . ' RENAME TO kokurikuler_dimensi_ket');
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    backfill_dimensi_ket($pdo);
}

function backfill_dimensi_ket(PDO $pdo): void
{
    if (!table_exists($pdo, 'kokurikuler_dimensi_ket')) {
        return;
    }
    $cols = array_map(fn($c) => $c['name'], $pdo->query('PRAGMA table_info(kokurikuler_dimensi_ket)')->fetchAll(PDO::FETCH_ASSOC));
    if (!in_array('kelas', $cols, true)) {
        return;
    }
    $lama = $pdo->query('SELECT idx, teks FROM kokurikuler_dimensi_ket WHERE kelas=0')->fetchAll(PDO::FETCH_ASSOC);
    if (!$lama) {
        return;
    }
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO kokurikuler_dimensi_ket (idx, kelas, teks) VALUES (?,?,?)');
    foreach ($lama as $r) {
        $teks = trim((string) $r['teks']);
        if ($teks === '') {
            continue;
        }
        foreach (kelas_tersedia($pdo) as $k) {
            $stmt->execute([(int) $r['idx'], $k, $teks]);
        }
    }
    $pdo->exec('DELETE FROM kokurikuler_dimensi_ket WHERE kelas=0');
}

function backfill_kelas_siswa(PDO $pdo): void
{
    foreach (['kehadiran', 'catatan_wali', 'nilai_ekstra', 'kokurikuler_nilai', 'kokurikuler_capaian'] as $t) {
        if (!table_exists($pdo, $t)) {
            continue;
        }
        $cols = array_map(fn($c) => $c['name'], $pdo->query("PRAGMA table_info($t)")->fetchAll(PDO::FETCH_ASSOC));
        if (!in_array('kelas', $cols, true)) {
            continue;
        }
        $pdo->exec("UPDATE $t SET kelas = COALESCE((SELECT s.kelas FROM siswa s WHERE s.id = $t.siswa_id), 0) WHERE kelas = 0");
    }
}

function migrate_kelas_tabel(PDO $pdo): void
{
    $tabel = [
        'kehadiran' => "CREATE TABLE %s (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          siswa_id INTEGER NOT NULL,
          ta TEXT NOT NULL,
          semester INTEGER NOT NULL,
          kelas INTEGER NOT NULL DEFAULT 0,
          sakit INTEGER NOT NULL DEFAULT 0,
          izin INTEGER NOT NULL DEFAULT 0,
          tka INTEGER NOT NULL DEFAULT 0,
          UNIQUE (siswa_id, ta, semester, kelas)
        )",
        'catatan_wali' => "CREATE TABLE %s (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          siswa_id INTEGER NOT NULL,
          ta TEXT NOT NULL,
          semester INTEGER NOT NULL,
          kelas INTEGER NOT NULL DEFAULT 0,
          teks TEXT NOT NULL DEFAULT '',
          UNIQUE (siswa_id, ta, semester, kelas)
        )",
        'nilai_ekstra' => "CREATE TABLE %s (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          siswa_id INTEGER NOT NULL,
          ekstra_id INTEGER NOT NULL,
          ta TEXT NOT NULL,
          semester INTEGER NOT NULL,
          kelas INTEGER NOT NULL DEFAULT 0,
          predikat TEXT NOT NULL,
          catatan TEXT NOT NULL DEFAULT '',
          UNIQUE (siswa_id, ekstra_id, ta, semester, kelas)
        )",
        'kokurikuler_nilai' => "CREATE TABLE %s (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          siswa_id INTEGER NOT NULL,
          kegiatan_id INTEGER NOT NULL,
          ta TEXT NOT NULL,
          semester INTEGER NOT NULL,
          kelas INTEGER NOT NULL DEFAULT 0,
          keterangan TEXT NOT NULL DEFAULT '',
          UNIQUE (siswa_id, kegiatan_id, ta, semester, kelas)
        )",
        'kokurikuler_capaian' => "CREATE TABLE %s (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          siswa_id INTEGER NOT NULL,
          ta TEXT NOT NULL,
          semester INTEGER NOT NULL,
          kelas INTEGER NOT NULL DEFAULT 0,
          predikat TEXT NOT NULL DEFAULT '',
          deskripsi TEXT NOT NULL DEFAULT '',
          desk_manual INTEGER NOT NULL DEFAULT 0,
          UNIQUE (siswa_id, ta, semester, kelas)
        )",
    ];
    foreach ($tabel as $t => $sqlBaru) {
        if (!table_exists($pdo, $t)) {
            $pdo->exec(sprintf($sqlBaru, $t));
            continue;
        }
        $sqlLama = (string) $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($t))->fetchColumn();
        if (preg_match('/UNIQUE\s*\([^)]*\bkelas\b/i', $sqlLama)) {
            continue;
        }
        $tmp = $t . '_mig_kelas';
        $pdo->beginTransaction();
        try {
            $pdo->exec('DROP TABLE IF EXISTS ' . $tmp);
            $pdo->exec(sprintf($sqlBaru, $tmp));
            $kolomLama = array_map(fn($c) => $c['name'], $pdo->query("PRAGMA table_info($t)")->fetchAll(PDO::FETCH_ASSOC));
            $kolomBaru = array_map(fn($c) => $c['name'], $pdo->query("PRAGMA table_info($tmp)")->fetchAll(PDO::FETCH_ASSOC));
            $salin = array_values(array_intersect($kolomLama, $kolomBaru));
            $kolomSql = implode(', ', $salin);
            $pdo->exec("INSERT INTO $tmp ($kolomSql) SELECT $kolomSql FROM $t");
            $pdo->exec('DROP TABLE ' . $t);
            $pdo->exec('ALTER TABLE ' . $tmp . ' RENAME TO ' . $t);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    backfill_kelas_siswa($pdo);
}

function migrate_jilid(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS siswa_jilid (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      siswa_id INTEGER NOT NULL,
      mapel_id INTEGER NOT NULL,
      ta TEXT NOT NULL,
      semester INTEGER NOT NULL,
      jilid INTEGER NOT NULL DEFAULT 0,
      UNIQUE (siswa_id, mapel_id, ta, semester)
    )");

    $tpCols = array_map(fn($c) => $c['name'], $pdo->query('PRAGMA table_info(tp)')->fetchAll(PDO::FETCH_ASSOC));
    if ($tpCols && !in_array('jilid', $tpCols, true)) {
        $pdo->exec('ALTER TABLE tp ADD COLUMN jilid INTEGER NOT NULL DEFAULT 0');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tp_jilid ON tp (mapel_id, semester, jilid)');
    }

    $cpCols = array_map(fn($c) => $c['name'], $pdo->query('PRAGMA table_info(cp)')->fetchAll(PDO::FETCH_ASSOC));
    if ($cpCols && !in_array('jilid', $cpCols, true)) {
        $pdo->exec('CREATE TABLE cp_jilid_migrate (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          mapel_id INTEGER NOT NULL,
          fase TEXT NOT NULL,
          jilid INTEGER NOT NULL DEFAULT 0,
          deskripsi TEXT NOT NULL,
          UNIQUE (mapel_id, fase, jilid)
        )');
        $pdo->exec('INSERT INTO cp_jilid_migrate (id, mapel_id, fase, jilid, deskripsi)
            SELECT id, mapel_id, fase, 0, deskripsi FROM cp');
        $pdo->exec('DROP TABLE cp');
        $pdo->exec('ALTER TABLE cp_jilid_migrate RENAME TO cp');
    }
}

function migrate_sumatif_kolom(PDO $pdo): void
{
    if (!table_exists($pdo, 'sumatif_jenis')) {
        return;
    }
    $kolom = array_map(fn($c) => $c['name'], $pdo->query('PRAGMA table_info(sumatif_jenis)')->fetchAll(PDO::FETCH_ASSOC));
    if (in_array('tipe', $kolom, true)) {
        return;
    }
    $tmp = 'sumatif_jenis_mig';
    $pdo->beginTransaction();
    try {
        $pdo->exec('DROP TABLE IF EXISTS ' . $tmp);
        $pdo->exec("CREATE TABLE $tmp (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          mapel_id INTEGER NOT NULL,
          ta TEXT NOT NULL,
          semester INTEGER NOT NULL,
          kelas INTEGER NOT NULL DEFAULT 0,
          user_id INTEGER NOT NULL DEFAULT 0,
          tipe TEXT NOT NULL DEFAULT 'lingkup',
          urutan INTEGER NOT NULL DEFAULT 0,
          nama TEXT NOT NULL,
          tanggal TEXT NOT NULL DEFAULT '',
          UNIQUE (mapel_id, kelas, ta, semester, user_id, nama)
        )");
        $ins = $pdo->prepare("INSERT INTO $tmp (mapel_id, ta, semester, kelas, user_id, tipe, urutan, nama, tanggal)
            VALUES (?, ?, ?, ?, 0, 'lingkup', ?, ?, ?)");
        $upd = $pdo->prepare('UPDATE nilai_sumatif SET jenis_id=? WHERE jenis_id=? AND kelas=?');
        $urutPer = [];
        foreach ($pdo->query('SELECT * FROM sumatif_jenis ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) as $j) {
            $klsSt = $pdo->prepare('SELECT DISTINCT kelas FROM nilai_sumatif WHERE jenis_id=? AND kelas>0');
            $klsSt->execute([$j['id']]);
            $daftarKelas = array_map('intval', $klsSt->fetchAll(PDO::FETCH_COLUMN));
            if (!$daftarKelas) {
                continue;
            }
            $key = $j['mapel_id'] . '|' . $j['ta'] . '|' . $j['semester'];
            $urutPer[$key] = ($urutPer[$key] ?? 0) + 1;
            $urut = $urutPer[$key];
            $pertama = null;
            foreach ($daftarKelas as $k) {
                $ins->execute([$j['mapel_id'], $j['ta'], $j['semester'], $k, $urut, $j['nama'], $j['tanggal']]);
                $baru = (int) $pdo->lastInsertId();
                if ($pertama === null) {
                    $pertama = $baru;
                }
                $upd->execute([$baru, $j['id'], $k]);
            }
            if ($pertama !== null) {
                $upd->execute([$pertama, $j['id'], 0]);
            }
        }
        $pdo->exec('DROP TABLE sumatif_jenis');
        $pdo->exec("ALTER TABLE $tmp RENAME TO sumatif_jenis");
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
