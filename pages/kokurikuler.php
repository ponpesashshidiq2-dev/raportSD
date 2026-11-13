<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_admin()) {
        flash_set('error', 'Hanya administrator yang dapat mengubah kegiatan kokurikuler.');
        redirect('kokurikuler');
    }
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'nama' => trim($_POST['nama'] ?? ''),
            'tujuan' => trim($_POST['tujuan'] ?? ''),
            'urutan' => (int) ($_POST['urutan'] ?? 0),
        ];
        if ($data['nama'] === '') {
            flash_set('error', 'Nama kegiatan kokurikuler wajib diisi.');
            redirect('kokurikuler');
        }
        if ($id > 0) {
            update_row('kokurikuler_kegiatan', $data, 'id=?', [$id]);
            flash_set('success', 'Kegiatan kokurikuler diperbarui.');
        } else {
            $data['urutan'] = $data['urutan'] ?: ((int) scalar('SELECT COALESCE(MAX(urutan),0) FROM kokurikuler_kegiatan') + 1);
            insert_row('kokurikuler_kegiatan', $data);
            flash_set('success', 'Kegiatan kokurikuler ditambahkan.');
        }
        redirect('kokurikuler');
    }
    if ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        run('DELETE FROM kokurikuler_nilai WHERE kegiatan_id=?', [$id]);
        run('DELETE FROM kokurikuler_kegiatan WHERE id=?', [$id]);
        flash_set('success', 'Kegiatan kokurikuler beserta keterangannya dihapus.');
        redirect('kokurikuler');
    }
}

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = one('SELECT * FROM kokurikuler_kegiatan WHERE id=?', [(int) $_GET['edit']]);
}
$daftar = all('SELECT k.*, (SELECT COUNT(*) FROM kokurikuler_nilai n WHERE n.kegiatan_id=k.id) AS jml
    FROM kokurikuler_kegiatan k ORDER BY k.urutan, k.nama');

page_head('Kokurikuler', 'Daftar kegiatan kokurikuler sesuai Permendikdasmen No. 13 Tahun 2025. Keterangan & deskripsi capaian 8 dimensi profil lulusan diinput pada menu Input Nilai Kokurikuler.',
    '<a class="btn blue" href="index.php?page=nilai_kokurikuler">Input Nilai Kokurikuler</a>');
echo context_bar();
?>
<?php if (is_admin()): ?>
<form class="card" method="post" action="index.php?page=kokurikuler">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="simpan">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <h3><?= $edit ? 'Ubah Kegiatan Kokurikuler' : 'Tambah Kegiatan Kokurikuler' ?></h3>
  <div class="form-row">
    <div class="field"><label>Nama Kegiatan</label><input type="text" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required></div>
    <div class="field"><label>Urutan</label><input type="number" name="urutan" value="<?= (int) ($edit['urutan'] ?? 0) ?>"></div>
  </div>
  <div class="form-row">
    <div class="field" style="grid-column:1/-1">
      <label>Tujuan Akhir Kegiatan (dasar penyusunan deskripsi capaian profil lulusan)</label>
      <textarea name="tujuan" rows="2" placeholder="tulis dengan huruf kecil, memuat kompetensi dan konten target akhir kokurikuler..."><?= e($edit['tujuan'] ?? '') ?></textarea>
    </div>
  </div>
  <div class="page-actions">
    <button class="btn" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah' ?></button>
    <?php if ($edit): ?><a class="btn secondary" href="index.php?page=kokurikuler">Batal</a><?php endif; ?>
  </div>
</form>
<?php elseif ($edit): ?>
  <div class="alert alert-info">Hanya administrator yang dapat mengubah data.</div>
<?php endif; ?>

<div class="table-wrap">
  <table class="tbl">
    <thead><tr><th class="center" style="width:40px">No</th><th>Kegiatan Kokurikuler</th><th>Tujuan Akhir</th><th class="center">Siswa Dinilai</th><?php if (is_admin()): ?><th class="center">Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!$daftar): ?>
      <tr><td colspan="5" class="empty">Belum ada kegiatan kokurikuler.</td></tr>
    <?php else: $no = 0; foreach ($daftar as $d): $no++; ?>
      <tr>
        <td class="center"><?= $no ?></td>
        <td class="name-cell"><?= e($d['nama']) ?></td>
        <td class="hint" style="font-size:12.5px"><?= e($d['tujuan']) ?></td>
        <td class="center"><span class="badge badge-blue"><?= (int) $d['jml'] ?> siswa</span></td>
        <?php if (is_admin()): ?>
        <td class="center">
          <a class="btn sm secondary" href="index.php?page=kokurikuler&edit=<?= (int) $d['id'] ?>">Ubah</a>
          <form method="post" action="index.php?page=kokurikuler" data-confirm="Hapus kegiatan kokurikuler beserta keterangannya?">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
            <button class="btn sm danger" type="submit">Hapus</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
