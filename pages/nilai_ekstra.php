<?php
$c = ctx();
$kaKunci = kelas_angkatan();
$kelasSiswa = $kaKunci ?? (int) $c['kelas'];
$siswas = all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=?" . scope_user() . " ORDER BY nama", [$kelasSiswa]);
$ekstraList = all('SELECT * FROM ekstra WHERE 1=1' . scope_user() . ' ORDER BY urutan, nama');
$predikatOps = ['' => '-', 'A' => 'A (Sangat Baik)', 'B' => 'B (Baik)', 'C' => 'C (Cukup)', 'D' => 'D (Kurang)'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk menginput nilai.');
        redirect('nilai_ekstra');
    }
    $nilai = $_POST['nilai'] ?? [];
    $tersimpan = 0;
    db()->beginTransaction();
    try {
        foreach ($ekstraList as $ek) {
            foreach ($siswas as $s) {
                $key = $ek['id'] . '_' . $s['id'];
                $p = trim((string) ($nilai[$key] ?? ''));
                $ada = one('SELECT id FROM nilai_ekstra WHERE siswa_id=? AND ekstra_id=? AND ta=? AND semester=? AND kelas=?',
                    [$s['id'], $ek['id'], $c['ta'], $c['semester'], $c['kelas']]);
                if ($p === '') {
                    if ($ada) {
                        run('DELETE FROM nilai_ekstra WHERE id=?', [$ada['id']]);
                    }
                    continue;
                }
                $row = ['predikat' => $p, 'kelas' => $c['kelas']];
                if ($ada) {
                    update_row('nilai_ekstra', $row, 'id=?', [$ada['id']]);
                } else {
                    insert_row('nilai_ekstra', $row + [
                        'siswa_id' => $s['id'], 'ekstra_id' => $ek['id'],
                        'ta' => $c['ta'], 'semester' => $c['semester'],
                    ]);
                }
                $tersimpan++;
            }
        }
        db()->commit();
        flash_set('success', "Nilai ekstrakurikuler tersimpan: $tersimpan sel.");
    } catch (Throwable $ex) {
        db()->rollBack();
        flash_set('error', 'Gagal menyimpan: ' . $ex->getMessage());
    }
    redirect('nilai_ekstra');
}

$existing = [];
foreach (all('SELECT ekstra_id, siswa_id, predikat FROM nilai_ekstra WHERE ta=? AND semester=? AND kelas=?',
    [$c['ta'], $c['semester'], $c['kelas']]) as $r) {
    $existing[$r['ekstra_id'] . '_' . $r['siswa_id']] = $r['predikat'];
}

page_head('Input Nilai Ekstrakurikuler', 'Siswa hanya menampilkan kegiatan yang nilainya diisi pada raport.');
echo context_bar();
echo angkatan_lock_alert();
if (!$ekstraList): ?>
  <div class="alert alert-warning">Belum ada kegiatan. Tambahkan pada menu <a href="index.php?page=ekstra">Ekstrakurikuler</a>.</div>
<?php elseif (!$siswas): ?>
  <div class="card empty"><?= teks_kosong_siswa($kaKunci, (int) $c['kelas']) ?></div>
<?php else: ?>
<form method="post" action="index.php?page=nilai_ekstra">
  <?= csrf_field() ?>
  <div class="save-bar">
    <button class="btn" type="submit">Simpan Nilai Ekstrakurikuler</button>
    <span class="hint">Pilih <b>-</b> untuk menghapus nilai. Baris menempel di atas saat digulir — tidak perlu scroll ke bawah untuk menyimpan.</span>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <caption>Kelas <?= $c['kelas'] ?> - Semester <?= $c['semester'] ?> - <?= e($c['ta']) ?></caption>
      <thead>
        <tr>
          <th class="center" style="width:40px">No</th>
          <th>Nama Siswa</th>
          <?php foreach ($ekstraList as $ek): ?>
            <th class="center" title="<?= e($ek['nama']) ?>"><?= e(mb_strimwidth($ek['nama'], 0, 18, '…')) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php $no = 0; foreach ($siswas as $s): $no++; ?>
        <tr>
          <td class="center"><?= $no ?></td>
          <td class="name-cell"><?= e($s['nama']) ?></td>
          <?php foreach ($ekstraList as $ek):
              $k = $ek['id'] . '_' . $s['id']; ?>
            <td class="center" style="min-width:120px">
              <select name="nilai[<?= $k ?>]" style="min-width:64px">
                <?php foreach ($predikatOps as $val => $lbl): ?>
                  <option value="<?= $val ?>"<?= ($existing[$k] ?? '') === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="hint mt">Pilih <b>-</b> untuk menghapus nilai. Deskripsi per kegiatan diisi pada menu
    <a href="index.php?page=ekstra">Ekstrakurikuler</a> (kartu "Deskripsi Ekstrakurikuler") — teks yang sama tampil untuk semua siswa pada raport.</div>
</form>
<?php endif; ?>
