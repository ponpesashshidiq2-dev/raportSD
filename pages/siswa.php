<?php
$c = ctx();

function import_header_map(array $header): array
{
    $norm = [];
    foreach ($header as $i => $h) {
        $norm[preg_replace('/[^a-z0-9]/', '', mb_strtolower((string) $h))] = $i;
    }
    $find = function (array $keys) use ($norm) {
        foreach ($keys as $k) {
            if (isset($norm[$k])) {
                return $norm[$k];
            }
        }
        return null;
    };
    return [
        'nis' => $find(['nis', 'nomorinduksiswa', 'nomorinduksiswa']),
        'nisn' => $find(['nisn', 'nomorinduksiswanasional', 'nomorpesertadidiknasional', 'nomorinduksiswanasional']),
        'nama' => $find(['nama', 'namasiswa', 'namapesertadidik', 'namalengkap']),
        'jk' => $find(['jk', 'jeniskelamin', 'kelamin', 'sex']),
        'tempat_lahir' => $find(['tempatlahir', 'tempat']),
        'tanggal_lahir' => $find(['tanggallahir', 'tglahir', 'lahir']),
        'alamat' => $find(['alamat', 'alamatlengkap']),
        'ortu' => $find(['namaortu', 'orangtuawali', 'namaorangtua', 'ortu', 'ayah']),
        'telp' => $find(['telp', 'telpon', 'telepon', 'nohp', 'nomorhp', 'handphone']),
        'kelas' => $find(['kelas', 'rombel', 'kelaswali']),
        'rombel' => $find(['rombel', 'kelompok']),
    ];
}

function import_tanggal($v): string
{
    $v = trim((string) $v);
    if ($v === '') {
        return '';
    }
    if (preg_match('#^(\d{4})-(\d{2})-(\d{2})#', $v, $m)) {
        return $m[0];
    }
    if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})#', $v, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (is_numeric($v) && $v > 20000 && $v < 80000) {
        $d = new DateTime('1899-12-30');
        $d->modify('+' . (int) $v . ' days');
        return $d->format('Y-m-d');
    }
    return '';
}

function siswa_import_rows(array $rows, string $mode): array
{
    $stats = ['total' => 0, 'baru' => 0, 'ubah' => 0, 'lewati' => 0, 'pesan' => []];
    if (count($rows) < 2) {
        $stats['pesan'][] = 'File tidak berisi data (minimal baris header + 1 baris data).';
        return $stats;
    }
    $map = import_header_map($rows[0]);
    if ($map['nama'] === null) {
        $stats['pesan'][] = 'Kolom "Nama" tidak ditemukan pada header file.';
        return $stats;
    }
    db()->beginTransaction();
    try {
        foreach (array_slice($rows, 1) as $r) {
            $get = fn($k) => $map[$k] !== null && isset($r[$map[$k]]) ? trim((string) $r[$map[$k]]) : '';
            $nama = $get('nama');
            if ($nama === '') {
                continue;
            }
            $stats['total']++;
            $nisn = $get('nisn');
            $nis = $get('nis');
            $kelas = (int) $get('kelas');
            if ($kelas < 1 || $kelas > 6) {
                $kelas = ctx()['kelas'];
            }
            $jk = strtoupper(substr($get('jk'), 0, 1));
            $data = [
                'nis' => $nis,
                'nisn' => $nisn,
                'nama' => $nama,
                'jk' => in_array($jk, ['P', 'W'], true) ? 'P' : 'L',
                'tempat_lahir' => $get('tempat_lahir'),
                'tanggal_lahir' => import_tanggal($get('tanggal_lahir')),
                'alamat' => $get('alamat'),
                'ortu' => $get('ortu'),
                'telp' => $get('telp'),
                'kelas' => $kelas,
                'rombel' => $get('rombel') ?: ('Kelas ' . $kelas),
                'status' => 'aktif',
            ];
            $existing = null;
            if ($nisn !== '') {
                $existing = one('SELECT id FROM siswa WHERE nisn=?', [$nisn]);
            }
            if (!$existing && $nis !== '') {
                $existing = one('SELECT id FROM siswa WHERE nis=?', [$nis]);
            }
            if (!$existing) {
                $existing = one('SELECT id FROM siswa WHERE LOWER(nama)=? AND kelas=?', [mb_strtolower($nama), $kelas]);
            }
            if ($existing) {
                if ($mode === 'skip') {
                    $stats['lewati']++;
                    continue;
                }
                update_row('siswa', $data, 'id=?', [$existing['id']]);
                $stats['ubah']++;
            } else {
                insert_row('siswa', $data);
                $stats['baru']++;
            }
        }
        db()->commit();
    } catch (Throwable $ex) {
        db()->rollBack();
        $stats['pesan'][] = 'Gagal mengimpor: ' . $ex->getMessage();
    }
    return $stats;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk mengubah data siswa.');
        redirect('siswa');
    }
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'nis' => trim($_POST['nis'] ?? ''),
            'nisn' => trim($_POST['nisn'] ?? ''),
            'nama' => trim($_POST['nama'] ?? ''),
            'jk' => ($_POST['jk'] ?? 'L') === 'P' ? 'P' : 'L',
            'tempat_lahir' => trim($_POST['tempat_lahir'] ?? ''),
            'tanggal_lahir' => trim($_POST['tanggal_lahir'] ?? ''),
            'alamat' => trim($_POST['alamat'] ?? ''),
            'ortu' => trim($_POST['ortu'] ?? ''),
            'telp' => trim($_POST['telp'] ?? ''),
            'kelas' => min(6, max(1, (int) ($_POST['kelas'] ?? 1))),
            'rombel' => trim($_POST['rombel'] ?? ''),
            'status' => in_array($_POST['status'] ?? 'aktif', ['aktif', 'lulus', 'pindah'], true) ? $_POST['status'] : 'aktif',
        ];
        if ($data['nama'] === '') {
            flash_set('error', 'Nama siswa wajib diisi.');
            redirect('siswa');
        }
        if ($data['rombel'] === '') {
            $data['rombel'] = 'Kelas ' . $data['kelas'];
        }
        if ($id > 0) {
            update_row('siswa', $data, 'id=?', [$id]);
            flash_set('success', 'Data siswa diperbarui.');
        } else {
            insert_row('siswa', $data);
            flash_set('success', 'Siswa baru ditambahkan.');
        }
        redirect('siswa');
    }

    if ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        run('DELETE FROM nilai_formatif WHERE siswa_id=?', [$id]);
        run('DELETE FROM nilai_sumatif WHERE siswa_id=?', [$id]);
        run('DELETE FROM nilai_ekstra WHERE siswa_id=?', [$id]);
        run('DELETE FROM kehadiran WHERE siswa_id=?', [$id]);
        run('DELETE FROM siswa WHERE id=?', [$id]);
        flash_set('success', 'Siswa beserta nilai yang terkait dihapus.');
        redirect('siswa');
    }

    if ($aksi === 'import') {
        if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'File tidak terunggah. Pilih file Excel (.xlsx) atau CSV.');
            redirect('siswa');
        }
        $tmp = $_FILES['file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            flash_set('error', 'Format file harus .xlsx atau .csv.');
            redirect('siswa');
        }
        try {
            $rows = xlsx_read($tmp, $_FILES['file']['name']);
            $stats = siswa_import_rows($rows, ($_POST['mode'] ?? 'update') === 'skip' ? 'skip' : 'update');
            if ($stats['pesan']) {
                flash_set('error', implode(' ', $stats['pesan']));
            } else {
                flash_set('success', sprintf(
                    'Impor selesai: %d baris diproses, %d baru, %d diperbarui, %d dilewati.',
                    $stats['total'], $stats['baru'], $stats['ubah'], $stats['lewati']
                ));
            }
        } catch (Throwable $ex) {
            flash_set('error', 'Gagal membaca file: ' . $ex->getMessage());
        }
        redirect('siswa');
    }
}

if (isset($_GET['act']) && $_GET['act'] === 'template' && user()) {
    xlsx_download('template-data-siswa.xlsx', [
        ['NIS', 'NISN', 'Nama', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'Nama Orang Tua', 'No HP', 'Kelas', 'Rombel'],
        ['2025001', '0091234567', 'Ahmad Fauzi', 'L', 'Yogyakarta', '12/08/2017', 'Jl. Melati No. 3', 'Budi Santoso', '081234567890', '1', 'Kelas 1'],
        ['2025002', '0091234568', 'Siti Aminah', 'P', 'Sleman', '03/09/2017', 'Jl. Kenanga No. 8', 'Herman', '081298765432', '1', 'Kelas 1'],
    ], [12, 16, 26, 14, 16, 14, 26, 22, 16, 8, 12]);
}

if (isset($_GET['act']) && $_GET['act'] === 'export' && user()) {
    $out = [['NIS', 'NISN', 'Nama', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'Nama Orang Tua', 'No HP', 'Kelas', 'Rombel', 'Status']];
    foreach (all('SELECT * FROM siswa ORDER BY kelas, nama') as $s) {
        $out[] = [
            $s['nis'], $s['nisn'], $s['nama'], $s['jk'], $s['tempat_lahir'], $s['tanggal_lahir'],
            $s['alamat'], $s['ortu'], $s['telp'], (int) $s['kelas'], $s['rombel'], $s['status'],
        ];
    }
    xlsx_download('data-siswa.xlsx', $out, [12, 16, 26, 14, 16, 14, 26, 22, 16, 8, 12, 10]);
}

$q = trim($_GET['q'] ?? '');
$filterKelas = (int) ($_GET['fk'] ?? 0);
$sql = 'SELECT * FROM siswa WHERE 1=1';
$args = [];
if ($q !== '') {
    $sql .= ' AND (nama LIKE ? OR nisn LIKE ? OR nis LIKE ?)';
    $like = '%' . $q . '%';
    array_push($args, $like, $like, $like);
}
if ($filterKelas >= 1 && $filterKelas <= 6) {
    $sql .= ' AND kelas=?';
    $args[] = $filterKelas;
} elseif ($q === '') {
    $sql .= ' AND kelas=?';
    $args[] = $c['kelas'];
}
$sql .= ' ORDER BY kelas, nama';
$siswas = all($sql, $args);
$edit = null;
if (!empty($_GET['edit'])) {
    $edit = one('SELECT * FROM siswa WHERE id=?', [(int) $_GET['edit']]);
}
$totalSemua = (int) scalar('SELECT COUNT(*) FROM siswa');

page_head('Data Siswa', 'Kelola data peserta didik kelas 1-6 beserta impor dari Excel.',
    '<a class="btn secondary" href="index.php?page=siswa&act=template">Unduh Template</a>'
    . '<a class="btn secondary" href="index.php?page=siswa&act=export">Export Excel</a>'
    . '<a class="btn" href="#form-siswa">+ Tambah Siswa</a>');
echo context_bar();
?>
<form class="card" id="form-siswa" method="post" action="index.php?page=siswa">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="simpan">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <h3><?= $edit ? 'Ubah Data Siswa' : 'Tambah Siswa' ?></h3>
  <div class="form-row">
    <div class="field"><label>Nama Lengkap</label><input type="text" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required></div>
    <div class="field"><label>NIS</label><input type="text" name="nis" value="<?= e($edit['nis'] ?? '') ?>"></div>
    <div class="field"><label>NISN</label><input type="text" name="nisn" value="<?= e($edit['nisn'] ?? '') ?>"></div>
    <div class="field">
      <label>Jenis Kelamin</label>
      <select name="jk">
        <option value="L"<?= ($edit['jk'] ?? 'L') === 'L' ? ' selected' : '' ?>>Laki-laki</option>
        <option value="P"<?= ($edit['jk'] ?? '') === 'P' ? ' selected' : '' ?>>Perempuan</option>
      </select>
    </div>
  </div>
  <div class="form-row">
    <div class="field"><label>Tempat Lahir</label><input type="text" name="tempat_lahir" value="<?= e($edit['tempat_lahir'] ?? '') ?>"></div>
    <div class="field"><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?= e($edit['tanggal_lahir'] ?? '') ?>"></div>
    <div class="field">
      <label>Kelas</label>
      <select name="kelas">
        <?php for ($k = 1; $k <= 6; $k++): ?>
          <option value="<?= $k ?>"<?= (int) ($edit['kelas'] ?? $c['kelas']) === $k ? ' selected' : '' ?>>Kelas <?= $k ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <?php foreach (['aktif' => 'Aktif', 'lulus' => 'Lulus', 'pindah' => 'Pindah'] as $v => $lbl): ?>
          <option value="<?= $v ?>"<?= ($edit['status'] ?? 'aktif') === $v ? ' selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="form-row">
    <div class="field"><label>Alamat</label><input type="text" name="alamat" value="<?= e($edit['alamat'] ?? '') ?>"></div>
    <div class="field"><label>Nama Orang Tua / Wali</label><input type="text" name="ortu" value="<?= e($edit['ortu'] ?? '') ?>"></div>
    <div class="field"><label>No. HP</label><input type="text" name="telp" value="<?= e($edit['telp'] ?? '') ?>"></div>
    <div class="field"><label>Rombel</label><input type="text" name="rombel" value="<?= e($edit['rombel'] ?? ('Kelas ' . ($edit['kelas'] ?? $c['kelas']))) ?>"></div>
  </div>
  <div class="page-actions">
    <button class="btn" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Siswa' ?></button>
    <?php if ($edit): ?><a class="btn secondary" href="index.php?page=siswa">Batal</a><?php endif; ?>
  </div>
</form>

<div class="grid grid-2">
  <form class="card" method="post" action="index.php?page=siswa" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="import">
    <h3>Import Excel / CSV</h3>
    <div class="hint mb">Kolom wajib: <b>Nama</b>. Kolom opsional: NIS, NISN, Jenis Kelamin, Tempat Lahir, Tanggal Lahir, Alamat, Nama Orang Tua, No HP, Kelas, Rombel. Data dikenali melalui NISN, NIS, atau nama+kelas.</div>
    <div class="form-row">
      <div class="field"><label>File (.xlsx / .csv)</label><input type="file" name="file" accept=".xlsx,.csv,.txt" required></div>
      <div class="field">
        <label>Jika data sudah ada</label>
        <select name="mode">
          <option value="update">Perbarui data lama</option>
          <option value="skip">Lewati data lama</option>
        </select>
      </div>
    </div>
    <button class="btn blue" type="submit">Impor Sekarang</button>
    <a class="btn secondary" href="index.php?page=siswa&act=template">Template Kosong</a>
  </form>

  <div class="card">
    <h3>Statistik</h3>
    <table class="tbl">
      <tbody>
        <tr><td>Total siswa terdaftar</td><td class="right"><b><?= $totalSemua ?></b></td></tr>
        <tr><td>Diimpor tampil pada kelas</td><td class="right"><b><?= count($siswas) ?></b></td></tr>
        <tr><td>Kelas konteks</td><td class="right"><b><?= $c['kelas'] ?> (<?= e($c['fase']) ?>)</b></td></tr>
      </tbody>
    </table>
    <div class="hint mt">Gunakan <b>Naik Kelas</b> pada menu Profil di akhir tahun ajaran.</div>
  </div>
</div>

<form class="toolbar" method="get" action="index.php">
  <input type="hidden" name="page" value="siswa">
  <input class="search" type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama / NISN / NIS...">
  <select name="fk">
    <option value="0">Konteks kelas <?= $c['kelas'] ?></option>
    <?php for ($k = 1; $k <= 6; $k++): ?>
      <option value="<?= $k ?>"<?= $filterKelas === $k ? ' selected' : '' ?>>Kelas <?= $k ?></option>
    <?php endfor; ?>
  </select>
  <button class="btn secondary" type="submit">Cari</button>
</form>

<div class="table-wrap">
  <table class="tbl">
    <thead>
      <tr>
        <th class="center" style="width:40px">No</th>
        <th>Nama</th><th>NIS</th><th>NISN</th><th class="center">L/P</th>
        <th class="center">Kelas</th><th>Orang Tua</th><th>Status</th><th class="center">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$siswas): ?>
      <tr><td colspan="9" class="empty">Belum ada data siswa pada kelas ini. Tambahkan manual atau impor Excel.</td></tr>
    <?php else: $no = 0; foreach ($siswas as $s): $no++; ?>
      <tr>
        <td class="center"><?= $no ?></td>
        <td class="name-cell"><?= e($s['nama']) ?><div class="hint"><?= e($s['rombel']) ?></div></td>
        <td><?= e($s['nis']) ?></td>
        <td><?= e($s['nisn']) ?></td>
        <td class="center"><?= e($s['jk']) ?></td>
        <td class="center"><?= (int) $s['kelas'] ?></td>
        <td><?= e($s['ortu']) ?></td>
        <td><span class="badge <?= $s['status'] === 'aktif' ? 'badge-green' : 'badge-gray' ?>"><?= e($s['status']) ?></span></td>
        <td class="center">
          <a class="btn sm secondary" href="index.php?page=siswa&edit=<?= (int) $s['id'] ?>">Ubah</a>
          <?php if (can_edit()): ?>
          <form method="post" action="index.php?page=siswa" data-confirm="Hapus siswa beserta seluruh nilainya?">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <button class="btn sm danger" type="submit">Hapus</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
