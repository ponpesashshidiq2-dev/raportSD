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
  deskripsi TEXT NOT NULL,
  UNIQUE (mapel_id, fase)
);
CREATE TABLE tp (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  mapel_id INTEGER NOT NULL,
  fase TEXT NOT NULL,
  kode TEXT NOT NULL DEFAULT '',
  deskripsi TEXT NOT NULL,
  urutan INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE siswa (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nis TEXT NOT NULL DEFAULT '',
  nisn TEXT NOT NULL DEFAULT '',
  nama TEXT NOT NULL,
  jk TEXT NOT NULL DEFAULT 'L',
  tempat_lahir TEXT NOT NULL DEFAULT '',
  tanggal_lahir TEXT NOT NULL DEFAULT '',
  alamat TEXT NOT NULL DEFAULT '',
  ortu TEXT NOT NULL DEFAULT '',
  telp TEXT NOT NULL DEFAULT '',
  kelas INTEGER NOT NULL DEFAULT 1,
  rombel TEXT NOT NULL DEFAULT '',
  status TEXT NOT NULL DEFAULT 'aktif',
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
  nama TEXT NOT NULL,
  tanggal TEXT NOT NULL DEFAULT '',
  UNIQUE (mapel_id, ta, semester, nama)
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
  predikat TEXT NOT NULL,
  catatan TEXT NOT NULL DEFAULT '',
  UNIQUE (siswa_id, ekstra_id, ta, semester)
);
CREATE TABLE kehadiran (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  sakit INTEGER NOT NULL DEFAULT 0,
  izin INTEGER NOT NULL DEFAULT 0,
  tka INTEGER NOT NULL DEFAULT 0,
  UNIQUE (siswa_id, ta, semester)
);
CREATE TABLE catatan_wali (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  teks TEXT NOT NULL DEFAULT '',
  UNIQUE (siswa_id, ta, semester)
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
  keterangan TEXT NOT NULL DEFAULT '',
  UNIQUE (siswa_id, kegiatan_id, ta, semester)
);
CREATE TABLE kokurikuler_capaian (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  siswa_id INTEGER NOT NULL,
  ta TEXT NOT NULL,
  semester INTEGER NOT NULL,
  predikat TEXT NOT NULL DEFAULT '',
  deskripsi TEXT NOT NULL DEFAULT '',
  UNIQUE (siswa_id, ta, semester)
);
CREATE INDEX idx_nf_mapel ON nilai_formatif (mapel_id, ta, semester);
CREATE INDEX idx_ns_mapel ON nilai_sumatif (mapel_id, ta, semester);
CREATE INDEX idx_siswa_kelas ON siswa (kelas, status);
CREATE INDEX idx_tp_mapel ON tp (mapel_id, fase);
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
    $stmt = $pdo->prepare('INSERT INTO tp (mapel_id, fase, kode, deskripsi, urutan) VALUES (?,?,?,?,?)');
    foreach ($tp as $nama => $fases) {
        foreach ($fases as $fase => $list) {
            $i = 0;
            foreach ($list as $desc) {
                $stmt->execute([$mapelId[$nama], $fase, 'TP.' . $fase . '.' . (++$i), $desc, $i]);
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

function migrate_catatan_wali(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS catatan_wali (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      siswa_id INTEGER NOT NULL,
      ta TEXT NOT NULL,
      semester INTEGER NOT NULL,
      teks TEXT NOT NULL DEFAULT '',
      UNIQUE (siswa_id, ta, semester)
    )");
}

function migrate_kokurikuler(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS kokurikuler_kegiatan (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nama TEXT NOT NULL,
      tujuan TEXT NOT NULL DEFAULT '',
      urutan INTEGER NOT NULL DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS kokurikuler_nilai (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      siswa_id INTEGER NOT NULL,
      kegiatan_id INTEGER NOT NULL,
      ta TEXT NOT NULL,
      semester INTEGER NOT NULL,
      keterangan TEXT NOT NULL DEFAULT '',
      UNIQUE (siswa_id, kegiatan_id, ta, semester)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS kokurikuler_capaian (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      siswa_id INTEGER NOT NULL,
      ta TEXT NOT NULL,
      semester INTEGER NOT NULL,
      predikat TEXT NOT NULL DEFAULT '',
      deskripsi TEXT NOT NULL DEFAULT '',
      UNIQUE (siswa_id, ta, semester)
    )");
    if ((int) $pdo->query('SELECT COUNT(*) FROM kokurikuler_kegiatan')->fetchColumn() === 0) {
        require_once APP_DIR . '/app/kurikulum.php';
        $stmt = $pdo->prepare('INSERT INTO kokurikuler_kegiatan (nama, tujuan, urutan) VALUES (?,?,?)');
        foreach (daftar_kokurikuler() as $kk) {
            $stmt->execute([$kk['nama'], $kk['tujuan'], $kk['urutan']]);
        }
    }
}
