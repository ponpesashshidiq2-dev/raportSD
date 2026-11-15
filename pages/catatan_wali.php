<?php
$c = ctx();
$kaKunci = kelas_angkatan();
$kelasSiswa = $kaKunci ?? (int) $c['kelas'];
$siswas = all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=? ORDER BY nama", [$kelasSiswa]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk mengubah catatan wali kelas.');
        redirect('catatan_wali');
    }
    $input = $_POST['catatan'] ?? [];
    $tersimpan = 0;
    db()->beginTransaction();
    try {
        foreach ($siswas as $s) {
            $sid = (int) $s['id'];
            $t = trim((string) ($input[$sid] ?? ''));
            $ada = one('SELECT id FROM catatan_wali WHERE siswa_id=? AND ta=? AND semester=? AND kelas=?',
                [$sid, $c['ta'], $c['semester'], $c['kelas']]);
            if ($t === '') {
                if ($ada) {
                    run('DELETE FROM catatan_wali WHERE id=?', [$ada['id']]);
                }
                continue;
            }
            if ($ada) {
                update_row('catatan_wali', ['teks' => $t], 'id=?', [$ada['id']]);
            } else {
                insert_row('catatan_wali', ['siswa_id' => $sid, 'ta' => $c['ta'],
                    'semester' => $c['semester'], 'kelas' => $c['kelas'], 'teks' => $t]);
            }
            $tersimpan++;
        }
        db()->commit();
        flash_set('success', "Catatan wali kelas tersimpan untuk $tersimpan siswa. Catatan yang dikosongkan otomatis dihapus.");
    } catch (Throwable $ex) {
        db()->rollBack();
        flash_set('error', 'Gagal menyimpan: ' . $ex->getMessage());
    }
    redirect('catatan_wali');
}

$existing = [];
foreach (all('SELECT siswa_id, teks FROM catatan_wali WHERE ta=? AND semester=? AND kelas=?', [$c['ta'], $c['semester'], $c['kelas']]) as $r) {
    $existing[(int) $r['siswa_id']] = $r['teks'];
}

page_head('Catatan Wali Kelas',
    'Catatan perkembangan per siswa semester ini — tampil pada kotak "Catatan Wali Kelas" di raport cetak dan menjadi dasar penilaian Perkembangan pada KEPUTUSAN semester 2.');
echo context_bar();
echo angkatan_lock_alert();
if (!$siswas): ?>
  <div class="card empty"><?= teks_kosong_siswa($kaKunci, (int) $c['kelas']) ?></div>
<?php else: ?>
<form method="post" action="index.php?page=catatan_wali">
  <?= csrf_field() ?>
  <div class="table-wrap">
    <table class="tbl">
      <caption>Kelas <?= $c['kelas'] ?> - Semester <?= $c['semester'] ?> - <?= e($c['ta']) ?></caption>
      <thead>
        <tr>
          <th class="center" style="width:40px">No</th>
          <th style="width:230px">Nama Siswa</th>
          <th>Catatan Wali Kelas</th>
        </tr>
      </thead>
      <tbody>
      <?php $no = 0; foreach ($siswas as $s): $no++; $sid = (int) $s['id']; ?>
        <tr>
          <td class="center"><?= $no ?></td>
          <td class="name-cell"><?= e($s['nama']) ?><div class="hint">NISN <?= e($s['nisn']) ?></div></td>
          <td><textarea name="catatan[<?= $sid ?>]" rows="2" style="width:100%"
            placeholder="mis. Ananda menunjukkan perkembangan akademik yang baik dan aktif membantu teman."><?= e($existing[$sid] ?? '') ?></textarea></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="toolbar mt">
    <button class="btn" type="submit">Simpan Catatan</button>
    <span class="hint">Kosongkan teks untuk menghapus catatan. Teks tampil apa adanya (boleh beberapa baris) pada kotak raport;
      catatan yang terisi juga membuat penilaian <b>Perkembangan</b> pada KEPUTUSAN semester 2 menjadi <b>Terpenuhi Baik</b>.</span>
  </div>
</form>
<?php endif; ?>
