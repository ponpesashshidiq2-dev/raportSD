<?php
$c = ctx();
$muatanLabel = ['agama' => 'Muatan Agama', 'nasional' => 'Muatan Nasional', 'lokal' => 'Muatan Lokal'];
$mapelId = (int) ($_GET['mapel'] ?? 0);
$faseSel = in_array($_GET['fase'] ?? '', ['A', 'B', 'C'], true) ? $_GET['fase'] : $c['fase'];
$semuaMapel = all('SELECT * FROM mapel ORDER BY muatan, urutan, nama');
if ($mapelId <= 0 && $semuaMapel) {
    $mapelId = (int) $semuaMapel[0]['id'];
}

function tp_header_map(array $header): array
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
        'mapel' => $find(['mapel', 'matapelajaran', 'bidangstudi']),
        'fase' => $find(['fase', 'tingkat']),
        'kode' => $find(['kode', 'kodetp']),
        'deskripsi' => $find(['deskripsi', 'tujuanpembelajaran', 'deskripsitp']),
        'urutan' => $find(['urutan', 'urut', 'nomor', 'no']),
    ];
}

function tp_import_rows(array $rows, string $mode, string $defaultFase = 'A'): array
{
    $stats = ['total' => 0, 'baru' => 0, 'ubah' => 0, 'lewati' => 0, 'pesan' => []];
    if (count($rows) < 2) {
        $stats['pesan'][] = 'File tidak berisi data (minimal baris header + 1 baris data).';
        return $stats;
    }
    $map = tp_header_map($rows[0]);
    if ($map['mapel'] === null || $map['deskripsi'] === null) {
        $stats['pesan'][] = 'Kolom "Mapel" dan "Deskripsi" wajib ada pada header file.';
        return $stats;
    }
    $mapelLower = [];
    foreach (all('SELECT id, nama FROM mapel') as $m) {
        $mapelLower[mb_strtolower($m['nama'])] = (int) $m['id'];
    }
    db()->beginTransaction();
    try {
        foreach (array_slice($rows, 1) as $r) {
            $get = fn($k) => $map[$k] !== null && isset($r[$map[$k]]) ? trim((string) $r[$map[$k]]) : '';
            $namaMapel = $get('mapel');
            $deskripsi = $get('deskripsi');
            $fase = $get('fase') !== '' ? strtoupper($get('fase')) : $defaultFase;
            if ($namaMapel === '' && $deskripsi === '' && $fase === '') {
                continue;
            }
            $stats['total']++;
            if ($namaMapel === '') {
                $stats['lewati']++;
                $stats['pesan'][] = 'Ada baris tanpa nama mata pelajaran.';
                continue;
            }
            $mid = $mapelLower[mb_strtolower($namaMapel)] ?? 0;
            if ($mid <= 0) {
                $stats['lewati']++;
                $stats['pesan'][] = 'Mapel "' . $namaMapel . '" tidak ditemukan.';
                continue;
            }
            if (!in_array($fase, ['A', 'B', 'C'], true)) {
                $stats['lewati']++;
                $stats['pesan'][] = 'Fase pada baris "' . $namaMapel . '" tidak valid (harus A/B/C).';
                continue;
            }
            if ($deskripsi === '') {
                $stats['lewati']++;
                $stats['pesan'][] = 'Baris ' . $namaMapel . ' fase ' . $fase . ' tanpa deskripsi.';
                continue;
            }
            $kode = $get('kode');
            $urutan = (int) $get('urutan');
            $existing = null;
            if ($kode !== '') {
                $existing = one('SELECT id FROM tp WHERE mapel_id=? AND fase=? AND kode=?', [$mid, $fase, $kode]);
            }
            if (!$existing) {
                $existing = one('SELECT id FROM tp WHERE mapel_id=? AND fase=? AND deskripsi=?', [$mid, $fase, $deskripsi]);
            }
            if ($existing) {
                if ($mode === 'skip') {
                    $stats['lewati']++;
                    continue;
                }
                $data = ['mapel_id' => $mid, 'fase' => $fase, 'deskripsi' => $deskripsi];
                if ($kode !== '') {
                    $data['kode'] = $kode;
                }
                if ($urutan > 0) {
                    $data['urutan'] = $urutan;
                }
                update_row('tp', $data, 'id=?', [$existing['id']]);
                $stats['ubah']++;
            } else {
                $n = (int) scalar('SELECT COUNT(*) FROM tp WHERE mapel_id=? AND fase=?', [$mid, $fase]) + 1;
                if ($kode === '') {
                    while (one('SELECT id FROM tp WHERE mapel_id=? AND fase=? AND kode=?', [$mid, $fase, 'TP.' . $fase . '.' . $n])) {
                        $n++;
                    }
                    $kode = 'TP.' . $fase . '.' . $n;
                }
                insert_row('tp', [
                    'mapel_id' => $mid,
                    'fase' => $fase,
                    'kode' => $kode,
                    'deskripsi' => $deskripsi,
                    'urutan' => $urutan > 0 ? $urutan : $n,
                ]);
                $stats['baru']++;
            }
        }
        db()->commit();
    } catch (Throwable $ex) {
        db()->rollBack();
        $stats['pesan'][] = 'Gagal mengimpor: ' . $ex->getMessage();
    }
    $stats['pesan'] = array_slice(array_values(array_unique($stats['pesan'])), 0, 8);
    return $stats;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $mapelId = (int) ($_POST['mapel_id'] ?? $mapelId);
    $faseSel = in_array($_POST['fase'] ?? '', ['A', 'B', 'C'], true) ? $_POST['fase'] : $faseSel;

    if ($aksi === 'cp_simpan') {
        if (!is_admin()) {
            flash_set('error', 'Hanya administrator yang dapat mengubah CP.');
        } else {
            $text = trim($_POST['deskripsi'] ?? '');
            if ($text !== '' && $mapelId > 0) {
                $ada = one('SELECT id FROM cp WHERE mapel_id=? AND fase=?', [$mapelId, $faseSel]);
                if ($ada) {
                    update_row('cp', ['deskripsi' => $text], 'id=?', [$ada['id']]);
                } else {
                    insert_row('cp', ['mapel_id' => $mapelId, 'fase' => $faseSel, 'deskripsi' => $text]);
                }
                flash_set('success', 'Capaian Pembelajaran tersimpan.');
            }
        }
        redirect('tp', ['mapel' => $mapelId, 'fase' => $faseSel]);
    }

    if ($aksi === 'tp_simpan') {
        if (!can_edit()) {
            flash_set('error', 'Anda tidak memiliki akses untuk mengubah TP.');
            redirect('tp');
        }
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'mapel_id' => $mapelId,
            'fase' => $faseSel,
            'deskripsi' => trim($_POST['deskripsi'] ?? ''),
            'urutan' => (int) ($_POST['urutan'] ?? 0),
        ];
        if ($data['deskripsi'] === '' || $mapelId <= 0) {
            flash_set('error', 'Deskripsi TP wajib diisi.');
            redirect('tp', ['mapel' => $mapelId, 'fase' => $faseSel]);
        }
        if ($id > 0) {
            update_row('tp', $data, 'id=?', [$id]);
            flash_set('success', 'Tujuan pembelajaran diperbarui.');
        } else {
            $n = (int) scalar('SELECT COUNT(*) FROM tp WHERE mapel_id=? AND fase=?', [$mapelId, $faseSel]) + 1;
            $data['kode'] = 'TP.' . $faseSel . '.' . $n;
            $data['urutan'] = $data['urutan'] ?: $n;
            insert_row('tp', $data);
            flash_set('success', 'Tujuan pembelajaran ditambahkan.');
        }
        redirect('tp', ['mapel' => $mapelId, 'fase' => $faseSel]);
    }

    if ($aksi === 'tp_hapus') {
        if (!can_edit()) {
            flash_set('error', 'Anda tidak memiliki akses.');
            redirect('tp');
        }
        run('DELETE FROM nilai_formatif WHERE tp_id=?', [(int) ($_POST['id'] ?? 0)]);
        run('DELETE FROM tp WHERE id=?', [(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'TP dihapus beserta nilai formatif yang terkait.');
        redirect('tp', ['mapel' => $mapelId, 'fase' => $faseSel]);
    }

    if ($aksi === 'tp_import') {
        if (!can_edit()) {
            flash_set('error', 'Anda tidak memiliki akses untuk mengimpor TP.');
            redirect('tp');
        }
        if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'File tidak terunggah. Pilih file Excel (.xlsx) atau CSV.');
            redirect('tp');
        }
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            flash_set('error', 'Format file harus .xlsx atau .csv.');
            redirect('tp');
        }
        try {
            $rows = xlsx_read($_FILES['file']['tmp_name'], $_FILES['file']['name']);
            $stats = tp_import_rows($rows, ($_POST['mode'] ?? 'update') === 'skip' ? 'skip' : 'update', $faseSel);
            $ringkas = sprintf(
                'Impor selesai: %d baris diproses, %d baru, %d diperbarui, %d dilewati.',
                $stats['total'], $stats['baru'], $stats['ubah'], $stats['lewati']
            );
            if ($stats['pesan']) {
                flash_set('error', $ringkas . ' ' . implode(' ', $stats['pesan']));
            } else {
                flash_set('success', $ringkas);
            }
        } catch (Throwable $ex) {
            flash_set('error', 'Gagal membaca file: ' . $ex->getMessage());
        }
        redirect('tp');
    }
}

if (isset($_GET['act']) && $_GET['act'] === 'template' && user()) {
    xlsx_download('template-tp.xlsx', [
        ['Mapel', 'Fase', 'Kode', 'Deskripsi', 'Urutan'],
        ['Bahasa Indonesia', 'A', 'TP.A.1', 'Mengidentifikasi ide pokok dan ide penjelas pada teks informatif sederhana', 1],
        ['Matematika', 'A', 'TP.A.1', 'Memecahkan masalah cerita dengan operasi hitungan campuran bertingkat', 2],
    ], [24, 8, 12, 70, 9]);
}

$mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
$cp = $mapelId > 0 ? one('SELECT * FROM cp WHERE mapel_id=? AND fase=?', [$mapelId, $faseSel]) : null;
$tps = $mapelId > 0 ? all('SELECT * FROM tp WHERE mapel_id=? AND fase=? ORDER BY urutan, id', [$mapelId, $faseSel]) : [];
$editTp = null;
if (!empty($_GET['edit'])) {
    $editTp = one('SELECT * FROM tp WHERE id=?', [(int) $_GET['edit']]);
}

page_head('Tujuan Pembelajaran', 'TP tiap mapel mengikuti Kurikulum Merdeka (deep learning) per fase; CP mengacu ketetapan Dinas Pendidikan.', '<a class="btn secondary" href="index.php?page=tp&act=template">Unduh Template TP</a>');
echo context_bar();
?>
<form class="toolbar" method="get" action="index.php">
  <input type="hidden" name="page" value="tp">
  <label style="margin:0">Mata Pelajaran</label>
  <select name="mapel" onchange="this.form.submit()">
    <?php foreach ($semuaMapel as $m): ?>
      <option value="<?= (int) $m['id'] ?>"<?= $mapelId === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['nama']) ?> (<?= e($muatanLabel[$m['muatan']] ?? $m['muatan']) ?>)</option>
    <?php endforeach; ?>
  </select>
  <label style="margin:0">Fase</label>
  <select name="fase" onchange="this.form.submit()">
    <?php foreach (['A', 'B', 'C'] as $f): ?>
      <option value="<?= $f ?>"<?= $faseSel === $f ? ' selected' : '' ?>><?= e(fase_label($f)) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<?php if (can_edit()): ?>
<form class="card" method="post" action="index.php?page=tp" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="tp_import">
  <h3>Import Excel / CSV</h3>
  <div class="hint mb">Kolom wajib: <b>Mapel</b>, <b>Deskripsi</b>. Kolom opsional: <b>Fase</b> (A/B/C, mengikuti fase aktif bila kosong), <b>Kode</b> (otomatis TP.fase.N bila kosong), <b>Urutan</b>. Nama mapel harus persis sama dengan daftar <a href="index.php?page=mapel">Mata Pelajaran</a>. Baris contoh pada template boleh dihapus atau diganti.</div>
  <div class="form-row">
    <div class="field"><label>File (.xlsx / .csv)</label><input type="file" name="file" accept=".xlsx,.csv,.txt" required></div>
    <div class="field">
      <label>Jika TP sudah ada</label>
      <select name="mode">
        <option value="update">Perbarui data lama</option>
        <option value="skip">Lewati data lama</option>
      </select>
    </div>
  </div>
  <div class="page-actions">
    <button class="btn blue" type="submit">Impor Sekarang</button>
    <a class="btn secondary" href="index.php?page=tp&act=template">Template Kosong</a>
  </div>
</form>
<?php endif; ?>

<?php if (!$mapel): ?>
  <div class="card empty">Belum ada mata pelajaran. Tambahkan pada menu <a href="index.php?page=mapel">Mata Pelajaran</a>.</div>
<?php else: ?>

<div class="grid grid-2">
  <div class="card">
    <h3>Capaian Pembelajaran (CP) - <?= e($mapel['nama']) ?> - Fase <?= $faseSel ?></h3>
    <div class="hint mb">Diisi sesuai ketetapan Dinas Pendidikan / kanonik CP Kurikulum Merdeka. CP menjadi dasar penyusunan TP dan deskripsi capaian kompetensi pada raport.</div>
    <form method="post" action="index.php?page=tp">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="cp_simpan">
      <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
      <input type="hidden" name="fase" value="<?= e($faseSel) ?>">
      <textarea name="deskripsi" rows="5" <?= is_admin() ? '' : 'readonly' ?>><?= e($cp['deskripsi'] ?? '') ?></textarea>
      <?php if (is_admin()): ?>
        <div class="mt"><button class="btn" type="submit">Simpan CP</button></div>
      <?php else: ?>
        <div class="hint mt">Tampilan hanya-baca untuk peran Anda.</div>
      <?php endif; ?>
    </form>
  </div>

  <div class="card">
    <h3><?= $editTp ? 'Ubah TP' : 'Tambah TP' ?> - Fase <?= $faseSel ?></h3>
    <form method="post" action="index.php?page=tp">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="tp_simpan">
      <input type="hidden" name="id" value="<?= (int) ($editTp['id'] ?? 0) ?>">
      <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
      <input type="hidden" name="fase" value="<?= e($faseSel) ?>">
      <div class="field">
        <label>Deskripsi Tujuan Pembelajaran</label>
        <textarea name="deskripsi" rows="3" placeholder="Contoh: Menganalisis struktur teks fiksi" required><?= e($editTp['deskripsi'] ?? '') ?></textarea>
        <div class="hint">Tulis spesifik dan terukur (kognitif, sosial-emosional, kinestetik) sesuai prinsip deep learning.</div>
      </div>
      <div class="form-row">
        <div class="field"><label>Urutan</label><input type="number" name="urutan" min="1" value="<?= (int) ($editTp['urutan'] ?? count($tps) + 1) ?>"></div>
      </div>
      <div class="page-actions">
        <button class="btn" type="submit"><?= $editTp ? 'Simpan Perubahan' : 'Tambah TP' ?></button>
        <?php if ($editTp): ?><a class="btn secondary" href="index.php?page=tp&mapel=<?= $mapelId ?>&fase=<?= e($faseSel) ?>">Batal</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="table-wrap">
  <table class="tbl">
    <thead><tr><th class="center" style="width:70px">Kode</th><th>Deskripsi Tujuan Pembelajaran</th><th class="center">Nilai Formatif</th><?php if (can_edit()): ?><th class="center">Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!$tps): ?>
      <tr><td colspan="4" class="empty">Belum ada TP untuk mapel dan fase ini. Tambahkan pada formulir di atas.</td></tr>
    <?php else: foreach ($tps as $t):
        $isi = (int) scalar('SELECT COUNT(*) FROM nilai_formatif WHERE tp_id=? AND ta=? AND semester=?', [$t['id'], $c['ta'], $c['semester']]); ?>
      <tr>
        <td class="center"><span class="badge badge-blue"><?= e($t['kode']) ?></span></td>
        <td><?= e($t['deskripsi']) ?></td>
        <td class="center"><?= $isi ?> siswa</td>
        <?php if (can_edit()): ?>
        <td class="center">
          <a class="btn sm secondary" href="index.php?page=tp&mapel=<?= $mapelId ?>&fase=<?= e($faseSel) ?>&edit=<?= (int) $t['id'] ?>">Ubah</a>
          <form method="post" action="index.php?page=tp" data-confirm="Hapus TP beserta nilai formatifnya?">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="tp_hapus">
            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
            <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
            <input type="hidden" name="fase" value="<?= e($faseSel) ?>">
            <button class="btn sm danger" type="submit">Hapus</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
