<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_admin()) {
        flash_set('error', 'Hanya administrator yang dapat mengubah kegiatan ekstrakurikuler.');
        redirect('ekstra');
    }
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'nama' => trim($_POST['nama'] ?? ''),
            'urutan' => (int) ($_POST['urutan'] ?? 0),
        ];
        if ($data['nama'] === '') {
            flash_set('error', 'Nama kegiatan wajib diisi.');
            redirect('ekstra');
        }
        if ($id > 0) {
            update_row('ekstra', $data, 'id=?', [$id]);
            flash_set('success', 'Kegiatan diperbarui.');
        } else {
            $data['urutan'] = $data['urutan'] ?: ((int) scalar('SELECT COALESCE(MAX(urutan),0) FROM ekstra') + 1);
            insert_row('ekstra', $data);
            flash_set('success', 'Kegiatan ditambahkan.');
        }
        redirect('ekstra');
    }
    if ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        run('DELETE FROM nilai_ekstra WHERE ekstra_id=?', [$id]);
        run('DELETE FROM ekstra_deskripsi WHERE ekstra_id=?', [$id]);
        run('DELETE FROM ekstra WHERE id=?', [$id]);
        flash_set('success', 'Kegiatan beserta nilainya dihapus.');
        redirect('ekstra');
    }
}

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = one('SELECT * FROM ekstra WHERE id=?', [(int) $_GET['edit']]);
}
$daftar = all('SELECT e.*, (SELECT COUNT(*) FROM nilai_ekstra n WHERE n.ekstra_id=e.id AND n.kelas=?) AS jml FROM ekstra e ORDER BY e.urutan, e.nama',
    [(int) ctx()['kelas']]);

page_head('Ekstrakurikuler', 'Daftar kegiatan ekstrakurikuler. Nilai yang tidak diinput tidak akan tampil pada raport.',
    '<a class="btn blue" href="index.php?page=nilai_ekstra">Input Nilai Ekstrakurikuler</a>');
echo context_bar();
?>
<?php if (is_admin()): ?>
<form class="card" method="post" action="index.php?page=ekstra">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="simpan">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <h3><?= $edit ? 'Ubah Kegiatan' : 'Tambah Kegiatan' ?></h3>
  <div class="form-row">
    <div class="field"><label>Nama Kegiatan</label><input type="text" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required></div>
    <div class="field"><label>Urutan</label><input type="number" name="urutan" value="<?= (int) ($edit['urutan'] ?? 0) ?>"></div>
  </div>
  <div class="page-actions">
    <button class="btn" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah' ?></button>
    <?php if ($edit): ?><a class="btn secondary" href="index.php?page=ekstra">Batal</a><?php endif; ?>
  </div>
</form>
<?php elseif ($edit): ?>
  <div class="alert alert-info">Hanya administrator yang dapat mengubah data.</div>
<?php endif; ?>

<div class="table-wrap">
  <table class="tbl">
    <thead><tr><th class="center" style="width:40px">No</th><th>Kegiatan</th><th class="center">Siswa Dinilai</th><?php if (is_admin()): ?><th class="center">Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!$daftar): ?>
      <tr><td colspan="4" class="empty">Belum ada kegiatan.</td></tr>
    <?php else: $no = 0; foreach ($daftar as $d): $no++; ?>
      <tr>
        <td class="center"><?= $no ?></td>
        <td class="name-cell"><?= e($d['nama']) ?></td>
        <td class="center"><span class="badge badge-blue"><?= (int) $d['jml'] ?> siswa</span></td>
        <?php if (is_admin()): ?>
        <td class="center">
          <a class="btn sm secondary" href="index.php?page=ekstra&edit=<?= (int) $d['id'] ?>">Ubah</a>
          <form method="post" action="index.php?page=ekstra" data-confirm="Hapus kegiatan beserta nilainya?">
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
