<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk mengubah kegiatan ekstrakurikuler.');
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
            if (!one('SELECT id FROM ekstra WHERE id=?' . scope_user(), [$id])) {
                flash_set('error', 'Kegiatan tidak ditemukan atau bukan milik Anda.');
                redirect('ekstra');
            }
            update_row('ekstra', $data, 'id=?', [$id]);
            flash_set('success', 'Kegiatan diperbarui.');
        } else {
            $data['urutan'] = $data['urutan'] ?: ((int) scalar('SELECT COALESCE(MAX(urutan),0) FROM ekstra WHERE 1=1' . scope_user()) + 1);
            insert_row('ekstra', $data + ['user_id' => (int) (user()['id'] ?? 0)]);
            flash_set('success', 'Kegiatan ditambahkan.');
        }
        redirect('ekstra');
    }
    if ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        if (!one('SELECT id FROM ekstra WHERE id=?' . scope_user(), [$id])) {
            flash_set('error', 'Kegiatan tidak ditemukan atau bukan milik Anda.');
            redirect('ekstra');
        }
        run('DELETE FROM nilai_ekstra WHERE ekstra_id=?', [$id]);
        run('DELETE FROM ekstra_deskripsi WHERE ekstra_id=?', [$id]);
        run('DELETE FROM ekstra WHERE id=?', [$id]);
        flash_set('success', 'Kegiatan beserta nilainya dihapus.');
        redirect('ekstra');
    }
    if ($aksi === 'deskripsi') {
        $c = ctx();
        $ekdesk = $_POST['ekdesk'] ?? [];
        $semuaId = all('SELECT id FROM ekstra WHERE 1=1' . scope_user());
        db()->beginTransaction();
        try {
            foreach ($semuaId as $row) {
                $eid = (int) $row['id'];
                $teks = trim((string) ($ekdesk[$eid] ?? ''));
                $where = 'ekstra_id=? AND ta=? AND semester=? AND kelas=?';
                $par = [$eid, $c['ta'], $c['semester'], $c['kelas']];
                $adaD = one("SELECT ekstra_id FROM ekstra_deskripsi WHERE $where", $par);
                if ($teks === '') {
                    if ($adaD) {
                        run("DELETE FROM ekstra_deskripsi WHERE $where", $par);
                    }
                    run("UPDATE nilai_ekstra SET catatan='' WHERE ekstra_id=? AND ta=? AND semester=? AND kelas=?", $par);
                } elseif ($adaD) {
                    update_row('ekstra_deskripsi', ['deskripsi' => $teks], $where, $par);
                } else {
                    insert_row('ekstra_deskripsi', ['ekstra_id' => $eid, 'ta' => $c['ta'],
                        'semester' => $c['semester'], 'kelas' => $c['kelas'], 'deskripsi' => $teks]);
                }
            }
            db()->commit();
            flash_set('success', 'Deskripsi ekstrakurikuler tersimpan untuk Kelas ' . $c['kelas'] . ' Semester ' . $c['semester'] . ' — tampil sama untuk semua siswa pada raport.');
        } catch (Throwable $ex) {
            db()->rollBack();
            flash_set('error', 'Gagal menyimpan deskripsi: ' . $ex->getMessage());
        }
        redirect('ekstra');
    }
}

$c = ctx();
$edit = null;
if (!empty($_GET['edit'])) {
    $edit = one('SELECT * FROM ekstra WHERE id=?' . scope_user(), [(int) $_GET['edit']]);
}
$daftar = all('SELECT e.*, (SELECT COUNT(*) FROM nilai_ekstra n WHERE n.ekstra_id=e.id AND n.kelas=?) AS jml FROM ekstra e WHERE 1=1' . scope_user() . ' ORDER BY e.urutan, e.nama',
    [(int) $c['kelas']]);

$ekDesk = [];
foreach (all('SELECT ekstra_id, deskripsi FROM ekstra_deskripsi WHERE ta=? AND semester=? AND kelas=?',
    [$c['ta'], $c['semester'], $c['kelas']]) as $r) {
    $ekDesk[(int) $r['ekstra_id']] = $r['deskripsi'];
}
$legacy = [];
foreach (all("SELECT n.ekstra_id AS eid, MIN(NULLIF(n.catatan, '')) AS t FROM nilai_ekstra n
    WHERE n.ta=? AND n.semester=? AND n.kelas=? GROUP BY n.ekstra_id",
    [$c['ta'], $c['semester'], $c['kelas']]) as $r) {
    if ($r['t'] !== null && $r['t'] !== '') {
        $legacy[(int) $r['eid']] = (string) $r['t'];
    }
}
foreach ($daftar as $ek) {
    if (($ekDesk[(int) $ek['id']] ?? '') === '' && isset($legacy[(int) $ek['id']])) {
        $ekDesk[(int) $ek['id']] = $legacy[(int) $ek['id']];
    }
}

page_head('Ekstrakurikuler', 'Daftar kegiatan dan deskripsi ekstrakurikuler — nilai yang tidak diinput tidak akan tampil pada raport.',
    '<a class="btn blue" href="index.php?page=nilai_ekstra">Input Nilai Ekstrakurikuler</a>');
echo context_bar();
?>
<?php if (can_edit()): ?>
<form class="card" method="post" action="index.php?page=ekstra">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="simpan">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <h3><?= $edit ? 'Ubah Kegiatan' : 'Tambah Kegiatan' ?></h3>
  <div class="save-bar save-bar-in">
    <button class="btn" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Kegiatan' ?></button>
    <?php if ($edit): ?><a class="btn secondary" href="index.php?page=ekstra">Batal</a><?php endif; ?>
  </div>
  <div class="form-row">
    <div class="field"><label>Nama Kegiatan</label><input type="text" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required></div>
    <div class="field"><label>Urutan</label><input type="number" name="urutan" value="<?= (int) ($edit['urutan'] ?? 0) ?>"></div>
  </div>
</form>
<?php elseif ($edit): ?>
  <div class="alert alert-info">Anda tidak memiliki akses untuk mengubah data ini.</div>
<?php endif; ?>

<?php if ($daftar): ?>
<form class="card desk-card<?= can_edit() ? '' : ' desk-readonly' ?>" method="post" action="index.php?page=ekstra">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="deskripsi">
  <div class="desk-head">
    <span class="desk-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="#fcd34d" stroke="#fde68a" stroke-width="1.4" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9z"/></svg>
    </span>
    <div class="desk-titles">
      <h3>Deskripsi Ekstrakurikuler</h3>
      <p>Diisi sekali per kegiatan — menjadi lanjutan kalimat raport untuk seluruh siswa pada konteks ini. Kosongkan untuk menyembunyikan keterangan.</p>
    </div>
    <span class="desk-ctx">Kelas <?= (int) $c['kelas'] ?> &middot; Semester <?= (int) $c['semester'] ?> &middot; <?= e($c['ta']) ?></span>
  </div>
  <div class="save-bar save-bar-in">
    <button class="btn" type="submit"<?= can_edit() ? '' : ' disabled' ?>>Simpan Deskripsi</button>
    <span class="hint">Predikat A = <b>sangat baik</b>, B = <b>Baik</b>, C = <b>Cukup</b>, D = <b>Kurang</b> — predikat tidak dicetak, hanya nama kegiatan + keterangan (mis. <i>"sangat baik dalam keterampilan ..."</i>).</span>
  </div>
  <div class="desk-body">
    <div class="desk-grid">
      <?php foreach ($daftar as $ek): ?>
        <div class="desk-item">
          <label><span class="desk-dot"></span><?= e($ek['nama']) ?></label>
          <input type="text" name="ekdesk[<?= (int) $ek['id'] ?>]" value="<?= e($ekDesk[(int) $ek['id']] ?? '') ?>"
            maxlength="300" placeholder="Deskripsi kegiatan (opsional)"<?= can_edit() ? '' : ' disabled' ?>>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</form>
<?php endif; ?>

<div class="table-wrap">
  <table class="tbl">
    <thead><tr><th class="center" style="width:40px">No</th><th>Kegiatan</th><th class="center">Siswa Dinilai</th><?php if (can_edit()): ?><th class="center">Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php if (!$daftar): ?>
      <tr><td colspan="4" class="empty">Belum ada kegiatan.</td></tr>
    <?php else: $no = 0; foreach ($daftar as $d): $no++; ?>
      <tr>
        <td class="center"><?= $no ?></td>
        <td class="name-cell"><?= e($d['nama']) ?></td>
        <td class="center"><span class="badge badge-blue"><?= (int) $d['jml'] ?> siswa</span></td>
        <?php if (can_edit()): ?>
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
