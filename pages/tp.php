<?php
$c = ctx();
$muatanLabel = ['agama' => 'Muatan Agama', 'nasional' => 'Muatan Nasional', 'lokal' => 'Muatan Lokal'];
$mapelId = (int) ($_GET['mapel'] ?? 0);
$faseSel = in_array($_GET['fase'] ?? '', ['A', 'B', 'C'], true) ? $_GET['fase'] : $c['fase'];
$semuaMapel = all('SELECT * FROM mapel ORDER BY muatan, urutan, nama');
if ($mapelId <= 0 && $semuaMapel) {
    $mapelId = (int) $semuaMapel[0]['id'];
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
}

$mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
$cp = $mapelId > 0 ? one('SELECT * FROM cp WHERE mapel_id=? AND fase=?', [$mapelId, $faseSel]) : null;
$tps = $mapelId > 0 ? all('SELECT * FROM tp WHERE mapel_id=? AND fase=? ORDER BY urutan, id', [$mapelId, $faseSel]) : [];
$editTp = null;
if (!empty($_GET['edit'])) {
    $editTp = one('SELECT * FROM tp WHERE id=?', [(int) $_GET['edit']]);
}

page_head('Tujuan Pembelajaran', 'TP tiap mapel mengikuti Kurikulum Merdeka (deep learning) per fase; CP mengacu ketetapan Dinas Pendidikan.');
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
