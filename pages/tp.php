<?php
$c = ctx();
$muatanLabel = ['agama' => 'Muatan Agama', 'nasional' => 'Muatan Nasional', 'lokal' => 'Muatan Lokal'];
$mapelId = (int) ($_GET['mapel'] ?? 0);
$kelasSel = in_array($_GET['kelas'] ?? '', ['1', '2', '3', '4', '5', '6'], true) ? (int) $_GET['kelas'] : (int) $c['kelas'];
$semSel = in_array($_GET['sem'] ?? '', ['1', '2'], true) ? (int) $_GET['sem'] : (int) $c['semester'];
$faseSel = fase_of($kelasSel);
$semuaMapel = all('SELECT * FROM mapel ORDER BY muatan, urutan, nama');
if ($mapelId <= 0 && $semuaMapel) {
    $mapelId = (int) $semuaMapel[0]['id'];
}
$mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
$isTahsin = is_tahsin($mapel);
$jilidOpsi = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
$jilidSel = in_array($_GET['jilid'] ?? '', $jilidOpsi, true) ? (int) $_GET['jilid'] : 1;

function tp_ids_bawaan(): array
{
    $set = [];
    foreach (daftar_tp() as $fases) {
        foreach ($fases as $list) {
            foreach ($list as $d) {
                $set[$d] = true;
            }
        }
    }
    if (!$set) {
        return [];
    }
    $keys = array_keys($set);
    $in = implode(',', array_fill(0, count($keys), '?'));
    return array_column(all("SELECT id FROM tp WHERE deskripsi IN ($in)", $keys), 'id');
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
        'kelas' => $find(['kelas', 'rombel', 'tingkatkelas']),
        'semester' => $find(['semester', 'sem']),
        'kode' => $find(['kode', 'kodetp']),
        'deskripsi' => $find(['deskripsi', 'tujuanpembelajaran', 'deskripsitp']),
        'urutan' => $find(['urutan', 'urut', 'nomor', 'no']),
    ];
}

function tp_import_rows(array $rows, string $mode, int $defaultKelas = 1, int $defaultSem = 1, bool $semuaTarget = false, int $defaultJilid = 1): array
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
    $mapelOwner = [];
    $tahsinIds = [];
    foreach (all('SELECT id, nama, user_id FROM mapel') as $m) {
        $namaLower = mb_strtolower($m['nama']);
        $mapelLower[$namaLower] = (int) $m['id'];
        $mapelOwner[(int) $m['id']] = (int) $m['user_id'];
        if (str_contains($namaLower, 'tahsin')) {
            $tahsinIds[(int) $m['id']] = true;
        }
    }
    db()->beginTransaction();
    try {
        foreach (array_slice($rows, 1) as $r) {
            $get = fn($k) => $map[$k] !== null && isset($r[$map[$k]]) ? trim((string) $r[$map[$k]]) : '';
            $namaMapel = $get('mapel');
            $deskripsi = $get('deskripsi');
            $kTeks = $get('kelas');
            $sTeks = $get('semester');
            if ($namaMapel === '' && $deskripsi === '' && $kTeks === '' && $sTeks === '') {
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
            $isT = isset($tahsinIds[$mid]);
            if ($isT) {
                if ($kTeks !== '') {
                    $jilidList = [(int) $kTeks];
                    if ($jilidList[0] < 1 || $jilidList[0] > 9) {
                        $stats['lewati']++;
                        $stats['pesan'][] = 'Jilid pada baris "' . $namaMapel . '" tidak valid (harus 1-9).';
                        continue;
                    }
                } else {
                    $jilidList = $semuaTarget ? range(1, 9) : [$defaultJilid];
                }
                $targets = array_map(fn($j) => ['kelas' => 0, 'jilid' => $j], $jilidList);
            } else {
                $kelasList = $kTeks !== '' ? [(int) $kTeks] : ($semuaTarget ? [1, 2, 3, 4, 5, 6] : [$defaultKelas]);
                if ($kTeks !== '' && ($kelasList[0] < 1 || $kelasList[0] > 6)) {
                    $stats['lewati']++;
                    $stats['pesan'][] = 'Kelas pada baris "' . $namaMapel . '" tidak valid (harus 1-6).';
                    continue;
                }
                $targets = array_map(fn($k) => ['kelas' => $k, 'jilid' => 0], $kelasList);
            }
            $semList = $sTeks !== '' ? [(int) $sTeks] : ($semuaTarget ? [1, 2] : [$defaultSem]);
            if ($sTeks !== '' && !in_array($semList[0], [1, 2], true)) {
                $stats['lewati']++;
                $stats['pesan'][] = 'Semester pada baris "' . $namaMapel . '" tidak valid (harus 1 atau 2).';
                continue;
            }
            if ($deskripsi === '') {
                $stats['lewati']++;
                $stats['pesan'][] = 'Baris ' . $namaMapel . ' tanpa deskripsi.';
                continue;
            }
            $kode = $get('kode');
            $urutan = (int) $get('urutan');
            foreach ($targets as $tgt) {
                $kelas = $tgt['kelas'];
                $jilid = $tgt['jilid'];
                foreach ($semList as $sem) {
                    $fase = $isT ? fase_of($defaultKelas) : fase_of($kelas);
                    $existing = null;
                    if ($kode !== '') {
                        $existing = one('SELECT id FROM tp WHERE mapel_id=? AND kelas=? AND jilid=? AND semester=? AND kode=?', [$mid, $kelas, $jilid, $sem, $kode]);
                    }
                    if (!$existing) {
                        $existing = one('SELECT id FROM tp WHERE mapel_id=? AND kelas=? AND jilid=? AND semester=? AND deskripsi=?', [$mid, $kelas, $jilid, $sem, $deskripsi]);
                    }
                    if ($existing) {
                        if ($mode === 'skip') {
                            $stats['lewati']++;
                            continue;
                        }
                        $data = ['mapel_id' => $mid, 'fase' => $fase, 'kelas' => $kelas, 'jilid' => $jilid, 'semester' => $sem, 'deskripsi' => $deskripsi];
                        if ($kode !== '') {
                            $data['kode'] = $kode;
                        }
                        if ($urutan > 0) {
                            $data['urutan'] = $urutan;
                        }
                        update_row('tp', $data, 'id=?', [$existing['id']]);
                        $stats['ubah']++;
                    } else {
                        $n = (int) scalar('SELECT COUNT(*) FROM tp WHERE mapel_id=? AND kelas=? AND jilid=? AND semester=?', [$mid, $kelas, $jilid, $sem]) + 1;
                        if ($kode === '') {
                            $auto = $isT ? 'TP.J' . $jilid . '.' . $n : 'TP.' . $fase . '.' . $n;
                            while (one('SELECT id FROM tp WHERE mapel_id=? AND kelas=? AND jilid=? AND semester=? AND kode=?', [$mid, $kelas, $jilid, $sem, $auto])) {
                                $n++;
                                $auto = $isT ? 'TP.J' . $jilid . '.' . $n : 'TP.' . $fase . '.' . $n;
                            }
                            $kode = $auto;
                        }
                        insert_row('tp', [
                            'mapel_id' => $mid,
                            'fase' => $fase,
                            'kelas' => $kelas,
                            'semester' => $sem,
                            'kode' => $kode,
                            'deskripsi' => $deskripsi,
                            'urutan' => $urutan > 0 ? $urutan : $n,
                            'jilid' => $jilid,
                            'user_id' => $mapelOwner[$mid] ?? 0,
                        ]);
                        $stats['baru']++;
                        $kode = $get('kode');
                    }
                }
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
    $kelasSel = in_array($_POST['kelas'] ?? '', ['1', '2', '3', '4', '5', '6'], true) ? (int) $_POST['kelas'] : $kelasSel;
    $semSel = in_array($_POST['sem'] ?? '', ['1', '2'], true) ? (int) $_POST['sem'] : $semSel;
    $faseSel = fase_of($kelasSel);
    $mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
    $isTahsin = is_tahsin($mapel);
    if ($isTahsin && in_array($_POST['jilid'] ?? '', $jilidOpsi, true)) {
        $jilidSel = (int) $_POST['jilid'];
    }
    $back = ['mapel' => $mapelId, 'kelas' => $kelasSel, 'sem' => $semSel];
    if ($isTahsin) {
        $back['jilid'] = $jilidSel;
    }

    if ($aksi === 'cp_simpan') {
        if (!is_admin()) {
            flash_set('error', 'Hanya administrator yang dapat mengubah CP.');
        } else {
            $text = trim($_POST['deskripsi'] ?? '');
            if ($text !== '' && $mapelId > 0) {
                if ($isTahsin) {
                    $ada = one('SELECT id FROM cp WHERE mapel_id=? AND fase=? AND jilid=?', [$mapelId, '', $jilidSel]);
                    if ($ada) {
                        update_row('cp', ['deskripsi' => $text], 'id=?', [$ada['id']]);
                    } else {
                        insert_row('cp', ['mapel_id' => $mapelId, 'fase' => '', 'jilid' => $jilidSel, 'deskripsi' => $text]);
                    }
                } else {
                    $ada = one('SELECT id FROM cp WHERE mapel_id=? AND fase=? AND jilid=0', [$mapelId, $faseSel]);
                    if ($ada) {
                        update_row('cp', ['deskripsi' => $text], 'id=?', [$ada['id']]);
                    } else {
                        insert_row('cp', ['mapel_id' => $mapelId, 'fase' => $faseSel, 'jilid' => 0, 'deskripsi' => $text]);
                    }
                }
                flash_set('success', 'Capaian Pembelajaran tersimpan.');
            }
        }
        redirect('tp', $back);
    }

    if ($aksi === 'tp_simpan') {
        if (!is_admin()) {
            flash_set('error', 'Hanya administrator yang dapat mengubah TP.');
            redirect('tp');
        }
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'mapel_id' => $mapelId,
            'fase' => $faseSel,
            'kelas' => $isTahsin ? 0 : $kelasSel,
            'jilid' => $isTahsin ? $jilidSel : 0,
            'semester' => $semSel,
            'deskripsi' => trim($_POST['deskripsi'] ?? ''),
            'urutan' => (int) ($_POST['urutan'] ?? 0),
        ];
        if ($data['deskripsi'] === '' || $mapelId <= 0) {
            flash_set('error', 'Deskripsi TP wajib diisi atau pilih mata pelajaran.');
            redirect('tp', $back);
        }
        if ($id > 0) {
            if (!one('SELECT t.id FROM tp t JOIN mapel m ON m.id = t.mapel_id WHERE t.id=?', [$id])) {
                flash_set('error', 'TP tidak ditemukan.');
                redirect('tp', $back);
            }
            update_row('tp', $data, 'id=?', [$id]);
            flash_set('success', 'Tujuan pembelajaran diperbarui.');
        } else {
            $n = $isTahsin
                ? (int) scalar('SELECT COUNT(*) FROM tp WHERE mapel_id=? AND kelas=0 AND jilid=? AND semester=?', [$mapelId, $jilidSel, $semSel]) + 1
                : (int) scalar('SELECT COUNT(*) FROM tp WHERE mapel_id=? AND kelas=? AND jilid=0 AND semester=?', [$mapelId, $kelasSel, $semSel]) + 1;
            $data['kode'] = $isTahsin ? 'TP.J' . $jilidSel . '.' . $n : 'TP.' . $faseSel . '.' . $n;
            $data['urutan'] = $data['urutan'] ?: $n;
            $data['user_id'] = (int) ($mapel['user_id'] ?? (int) (user()['id'] ?? 0));
            insert_row('tp', $data);
            flash_set('success', 'Tujuan pembelajaran ditambahkan.');
        }
        redirect('tp', $back);
    }

    if ($aksi === 'tp_hapus_lama') {
        if (!is_admin()) {
            flash_set('error', 'Hanya administrator yang dapat menghapus TP lama.');
            redirect('tp');
        }
        $ids = array_column(all('SELECT id FROM tp WHERE mapel_id=? AND jilid=0', [$mapelId]), 'id');
        $n = 0;
        db()->beginTransaction();
        try {
            foreach ($ids as $tid) {
                run('DELETE FROM nilai_formatif WHERE tp_id=?', [$tid]);
                $n += run('DELETE FROM tp WHERE id=?', [$tid]);
            }
            db()->commit();
            flash_set('success', "$n TP lama berbasis kelas dihapus beserta nilai formatif terkait.");
        } catch (Throwable $ex) {
            db()->rollBack();
            flash_set('error', 'Gagal menghapus TP lama: ' . $ex->getMessage());
        }
        redirect('tp', $back);
    }

    if ($aksi === 'tp_hapus') {
        if (!is_admin()) {
            flash_set('error', 'Hanya administrator yang dapat menghapus TP.');
            redirect('tp');
        }
        $tid = (int) ($_POST['id'] ?? 0);
        if (!one('SELECT t.id FROM tp t JOIN mapel m ON m.id = t.mapel_id WHERE t.id=?', [$tid])) {
            flash_set('error', 'TP tidak ditemukan.');
            redirect('tp', $back);
        }
        run('DELETE FROM nilai_formatif WHERE tp_id=?', [$tid]);
        run('DELETE FROM tp WHERE id=?', [$tid]);
        flash_set('success', 'TP dihapus beserta nilai formatif yang terkait.');
        redirect('tp', $back);
    }

    if ($aksi === 'tp_hapus_bawaan') {
        if (!is_admin()) {
            flash_set('error', 'Hanya administrator yang dapat menghapus TP bawaan.');
            redirect('tp');
        }
        $ids = tp_ids_bawaan();
        if (!$ids) {
            flash_set('success', 'Tidak ada TP bawaan tersisa.');
            redirect('tp', $back);
        }
        db()->beginTransaction();
        try {
            $n = 0;
            foreach (array_chunk($ids, 500) as $chunk) {
                $in = implode(',', array_fill(0, count($chunk), '?'));
                run("DELETE FROM nilai_formatif WHERE tp_id IN ($in)", $chunk);
                $n += run("DELETE FROM tp WHERE id IN ($in)", $chunk);
            }
            db()->commit();
            flash_set('success', "$n TP bawaan dihapus beserta nilai formatifnya. TP impor dan buatan sendiri tetap tersimpan.");
        } catch (Throwable $ex) {
            db()->rollBack();
            flash_set('error', 'Gagal menghapus TP bawaan: ' . $ex->getMessage());
        }
        redirect('tp', $back);
    }

    if ($aksi === 'tp_import') {
        if (!is_admin()) {
            flash_set('error', 'Hanya administrator yang dapat mengimpor TP. Pengguna lain tidak memiliki akses import.');
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
            $semuaTarget = ($_POST['target'] ?? 'all') !== 'ctx';
            $stats = tp_import_rows($rows, ($_POST['mode'] ?? 'update') === 'skip' ? 'skip' : 'update', $kelasSel, $semSel, $semuaTarget, $jilidSel);
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
        redirect('tp', ['mapel' => $mapelId, 'kelas' => $kelasSel, 'sem' => $semSel]);
    }
}

if (isset($_GET['act']) && $_GET['act'] === 'template' && user()) {
    xlsx_download('template-tp.xlsx', [
        ['Mapel', 'Kelas', 'Semester', 'Kode', 'Deskripsi', 'Urutan'],
        ['Bahasa Indonesia', 1, 1, 'TP.A.1', 'Mengidentifikasi ide pokok dan ide penjelas pada teks informatif sederhana', 1],
        ['Matematika', 2, 2, 'TP.A.1', 'Memecahkan masalah cerita dengan operasi hitungan campuran bertingkat', 2],
    ], [24, 8, 11, 12, 70, 9], [1 => '"1,2,3,4,5,6"', 2 => '"1,2"']);
}

$mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
if ($mapelId > 0 && $mapel === null) {
    $mapelId = 0;
}
$isTahsin = is_tahsin($mapel);
$cp = null;
if ($mapelId > 0) {
    $cp = $isTahsin
        ? one('SELECT * FROM cp WHERE mapel_id=? AND fase=? AND jilid=?', [$mapelId, '', $jilidSel])
        : one('SELECT * FROM cp WHERE mapel_id=? AND fase=? AND jilid=0', [$mapelId, $faseSel]);
}
$tps = $mapelId > 0 ? all('SELECT * FROM tp WHERE mapel_id=? AND kelas=? AND jilid=? AND semester=? ORDER BY urutan, id',
    [$mapelId, $isTahsin ? 0 : $kelasSel, $isTahsin ? $jilidSel : 0, $semSel]) : [];
$legacyTps = ($mapelId > 0 && $isTahsin)
    ? all('SELECT * FROM tp WHERE mapel_id=? AND jilid=0 AND semester=? ORDER BY kelas, urutan, id', [$mapelId, $semSel])
    : [];
$editTp = null;
if (!empty($_GET['edit'])) {
    $editTp = one('SELECT * FROM tp WHERE id=?', [(int) $_GET['edit']]);
}

$subtitle = 'TP tiap mapel per kelas & semester (Kurikulum Merdeka, deep learning); CP mengacu ketetapan Dinas Pendidikan per fase.';
if ($isTahsin) {
    $subtitle = 'TP & CP Tahsin disusun per Jilid (1-9) berlaku untuk semua kelas — diinput satu kali admin. Pemilih Jilid di bawah menentukan TP yang ditampilkan.';
}
page_head('Tujuan Pembelajaran', $subtitle, '<a class="btn secondary" href="index.php?page=tp&act=template">Unduh Template TP</a>');
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
  <?php if ($isTahsin): ?>
  <label style="margin:0">Jilid</label>
  <select name="jilid" onchange="this.form.submit()">
    <?php for ($j = 1; $j <= 9; $j++): ?>
      <option value="<?= $j ?>"<?= $jilidSel === $j ? ' selected' : '' ?>>Jilid <?= $j ?></option>
    <?php endfor; ?>
  </select>
  <?php else: ?>
  <label style="margin:0">Kelas</label>
  <select name="kelas" onchange="this.form.submit()">
    <?php for ($k = 1; $k <= 6; $k++): ?>
      <option value="<?= $k ?>"<?= $kelasSel === $k ? ' selected' : '' ?>>Kelas <?= $k ?></option>
    <?php endfor; ?>
  </select>
  <?php endif; ?>
  <label style="margin:0">Semester</label>
  <select name="sem" onchange="this.form.submit()">
    <?php for ($s = 1; $s <= 2; $s++): ?>
      <option value="<?= $s ?>"<?= $semSel === $s ? ' selected' : '' ?>>Semester <?= $s ?></option>
    <?php endfor; ?>
  </select>
  <?php if ($isTahsin): ?>
  <span class="badge badge-blue">Semester <?= $semSel ?> - Jilid <?= $jilidSel ?></span>
  <span class="badge badge-gray">Berlaku semua kelas</span>
  <?php else: ?>
  <span class="badge badge-blue">Fase <?= e($faseSel) ?></span>
  <?php endif; ?>
</form>

<?php if (is_admin()): ?>
<form class="card" method="post" action="index.php?page=tp" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="tp_import">
  <input type="hidden" name="kelas" value="<?= $kelasSel ?>">
  <input type="hidden" name="sem" value="<?= $semSel ?>">
  <input type="hidden" name="jilid" value="<?= $jilidSel ?>">
  <h3>Import Excel / CSV</h3>
  <div class="hint mb">Kolom wajib: <b>Mapel</b>, <b>Deskripsi</b>. Kolom opsional: <b>Kelas</b> (1-6), <b>Semester</b> (1/2), <b>Kode</b> (otomatis TP.fase.N bila kosong), <b>Urutan</b>.
    Pada <b>Target</b>: <b>Semua kelas &amp; semester</b> (bawaan) — baris tanpa Kelas/Semester langsung diterapkan sekaligus ke kelas 1-6 dan semester 1-2, satu kali impor untuk semua kelas;
    baris yang mencantumkan Kelas/Semester tetap mengikuti isinya. <b>Ikut pilihan di atas</b> — baris tanpa kelas/semester memakai Kelas/Semester yang dipilih.
    <?php if ($isTahsin): ?>Untuk <b>Tahsin</b>, kolom <b>Kelas</b> dibaca sebagai <b>Jilid (1-9)</b>; baris tanpa jilid mengikuti Jilid terpilih di atas (atau jilid 1-9 pada Target semua).<?php endif; ?>
    Nama mapel harus persis sama dengan daftar <a href="index.php?page=mapel">Mata Pelajaran</a>. Baris contoh pada template boleh dihapus atau diganti.</div>
  <div class="form-row">
    <div class="field"><label>File (.xlsx / .csv)</label><input type="file" name="file" accept=".xlsx,.csv,.txt" required></div>
    <div class="field">
      <label>Target Kelas/Semester</label>
      <select name="target">
        <option value="all">Semua kelas (1-6) &amp; semester (1-2)</option>
        <option value="ctx">Ikut pilihan Kelas/Semester di atas</option>
      </select>
    </div>
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

<?php
$tpBawaanCount = count(tp_ids_bawaan());
if (is_admin() && $tpBawaanCount > 0): ?>
<div class="alert alert-warning" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
  <span>Ada <b><?= $tpBawaanCount ?> TP bawaan</b> aplikasi (bukan impor) — hapus sekaligus agar tidak perlu menghapus satu per satu. TP impor dan buatan sendiri <b>tidak</b> ikut terhapus.</span>
  <form method="post" action="index.php?page=tp" data-confirm="Hapus semua <?= $tpBawaanCount ?> TP bawaan beserta nilai formatifnya? TP impor tidak ikut terhapus." style="display:inline">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="tp_hapus_bawaan">
    <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
    <input type="hidden" name="kelas" value="<?= $kelasSel ?>">
    <input type="hidden" name="sem" value="<?= $semSel ?>">
    <input type="hidden" name="jilid" value="<?= $jilidSel ?>">
    <button class="btn danger" type="submit">Hapus <?= $tpBawaanCount ?> TP Bawaan</button>
  </form>
</div>
<?php endif; ?>

<?php if (!$mapel): ?>
  <div class="card empty">Belum ada mata pelajaran. Tambahkan pada menu <a href="index.php?page=mapel">Mata Pelajaran</a>.</div>
<?php else: ?>

<div class="grid grid-2">
  <div class="card">
    <h3>Capaian Pembelajaran (CP) - <?= e($mapel['nama']) ?> - <?= $isTahsin ? 'Jilid ' . $jilidSel : 'Fase ' . e($faseSel) ?></h3>
    <div class="hint mb"><?= $isTahsin
        ? 'Untuk Tahsin, CP diinput per Jilid (1-9) — satu kali dan berlaku untuk semua kelas. CP menjadi dasar penyusunan TP dan deskripsi capaian kompetensi pada raport.'
        : 'Diisi sesuai ketetapan Dinas Pendidikan / kanonik CP Kurikulum Merdeka. CP menjadi dasar penyusunan TP dan deskripsi capaian kompetensi pada raport.' ?></div>
    <form method="post" action="index.php?page=tp">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="cp_simpan">
      <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
      <input type="hidden" name="kelas" value="<?= $kelasSel ?>">
      <input type="hidden" name="sem" value="<?= $semSel ?>">
      <input type="hidden" name="jilid" value="<?= $jilidSel ?>">
      <?php if (is_admin()): ?>
        <div class="save-bar save-bar-in"><button class="btn" type="submit">Simpan CP</button><span class="hint">Tombol menempel di atas — simpan tanpa menggulir.</span></div>
      <?php endif; ?>
      <textarea name="deskripsi" rows="5" <?= is_admin() ? '' : 'readonly' ?>><?= e($cp['deskripsi'] ?? '') ?></textarea>
      <?php if (!is_admin()): ?>
        <div class="hint mt">Tampilan hanya-baca untuk peran Anda.</div>
      <?php endif; ?>
    </form>
  </div>

  <div class="card">
    <h3><?= $editTp ? 'Ubah TP' : 'Tambah TP' ?> - <?= $isTahsin ? 'Jilid ' . $jilidSel : 'Kelas ' . $kelasSel ?> - Semester <?= $semSel ?></h3>
    <?php if (is_admin()): ?>
    <form method="post" action="index.php?page=tp">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="tp_simpan">
      <input type="hidden" name="id" value="<?= (int) ($editTp['id'] ?? 0) ?>">
      <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
      <input type="hidden" name="kelas" value="<?= $kelasSel ?>">
      <input type="hidden" name="sem" value="<?= $semSel ?>">
      <input type="hidden" name="jilid" value="<?= $jilidSel ?>">
      <div class="save-bar save-bar-in">
        <button class="btn" type="submit"><?= $editTp ? 'Simpan Perubahan' : 'Tambah TP' ?></button>
        <?php if ($editTp): ?><a class="btn secondary" href="index.php?page=tp&mapel=<?= $mapelId ?>&kelas=<?= $kelasSel ?>&sem=<?= $semSel ?>&jilid=<?= $jilidSel ?>">Batal</a><?php endif; ?>
        <span class="hint">Tombol menempel di atas — simpan tanpa menggulir.</span>
      </div>
      <div class="field">
        <label>Deskripsi Tujuan Pembelajaran</label>
        <textarea name="deskripsi" rows="3" placeholder="Contoh: Menganalisis struktur teks fiksi" required><?= e($editTp['deskripsi'] ?? '') ?></textarea>
        <div class="hint">Tulis spesifik dan terukur (kognitif, sosial-emosional, kinestetik) sesuai prinsip deep learning.</div>
      </div>
      <div class="form-row">
        <div class="field"><label>Urutan</label><input type="number" name="urutan" min="1" value="<?= (int) ($editTp['urutan'] ?? count($tps) + 1) ?>"></div>
      </div>
    </form>
    <?php else: ?>
      <div class="hint">TP diinput dan dikelola oleh administrator (hasil unggahan admin). Tampilan hanya-baca untuk peran Anda.</div>
    <?php endif; ?>
  </div>
</div>

<?php if ($isTahsin && $legacyTps && is_admin()): ?>
<div class="alert alert-warning">
  Ada <b><?= count($legacyTps) ?> TP Tahsin lama berbasis kelas</b> (semester ini) yang tidak dipakai di Nilai Formatif karena TP Tahsin kini per Jilid.
  Kosongkan dengan satu kali klik, atau hapus satu per satu pada tabel di bawah.
  <form method="post" action="index.php?page=tp" data-confirm="Hapus semua TP Tahsin lama beserta nilai formatifnya?" style="display:inline;margin-left:8px">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="tp_hapus_lama">
    <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
    <input type="hidden" name="kelas" value="<?= $kelasSel ?>">
    <input type="hidden" name="sem" value="<?= $semSel ?>">
    <input type="hidden" name="jilid" value="<?= $jilidSel ?>">
    <button class="btn sm danger" type="submit">Hapus <?= count($legacyTps) ?> TP Lama</button>
  </form>
</div>
<?php endif; ?>

<?php $tpsAll = array_merge($tps, array_map(fn($t) => $t + ['legacy' => 1], $legacyTps)); ?>
<div class="table-wrap">
  <table class="tbl">
    <thead><tr><th class="center" style="width:70px">Kode</th><th>Deskripsi Tujuan Pembelajaran</th><th class="center">Nilai Formatif</th><?php if (is_admin()): ?><th class="center">Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!$tpsAll): ?>
      <tr><td colspan="4" class="empty"><?= $isTahsin
          ? 'Belum ada TP untuk Jilid ' . $jilidSel . ' semester ini. Tambahkan pada formulir di atas.'
          : 'Belum ada TP untuk mapel, kelas, dan semester ini. Tambahkan pada formulir di atas.' ?></td></tr>
    <?php else: foreach ($tpsAll as $t):
        $isi = (int) scalar('SELECT COUNT(*) FROM nilai_formatif WHERE tp_id=? AND ta=? AND semester=?', [$t['id'], $c['ta'], (int) $t['semester']]); ?>
      <tr<?= !empty($t['legacy']) ? ' style="opacity:.6"' : '' ?>>
        <td class="center"><span class="badge badge-blue"><?= e($t['kode']) ?></span></td>
        <td><?= e($t['deskripsi']) ?><?php if (!empty($t['legacy'])): ?> <span class="badge badge-gray">Lama - Kelas <?= (int) $t['kelas'] ?> (tidak dipakai)</span><?php endif; ?></td>
        <td class="center"><?= $isi ?> siswa</td>
        <?php if (is_admin()): ?>
        <td class="center">
          <a class="btn sm secondary" href="index.php?page=tp&mapel=<?= $mapelId ?>&kelas=<?= $kelasSel ?>&sem=<?= $semSel ?>&jilid=<?= $jilidSel ?>&edit=<?= (int) $t['id'] ?>">Ubah</a>
          <form method="post" action="index.php?page=tp" data-confirm="Hapus TP beserta nilai formatifnya?">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="tp_hapus">
            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
            <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
            <input type="hidden" name="kelas" value="<?= $kelasSel ?>">
            <input type="hidden" name="sem" value="<?= $semSel ?>">
            <input type="hidden" name="jilid" value="<?= $jilidSel ?>">
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
