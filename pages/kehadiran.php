<?php
$c = ctx();
$siswas = all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=? ORDER BY nama", [$c['kelas']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk menginput kehadiran.');
        redirect('kehadiran');
    }
    $sakit = $_POST['sakit'] ?? [];
    $izin = $_POST['izin'] ?? [];
    $tka = $_POST['tka'] ?? [];
    $tersimpan = 0;
    db()->beginTransaction();
    try {
        foreach ($siswas as $s) {
            $data = [
                'sakit' => max(0, (int) ($sakit[$s['id']] ?? 0)),
                'izin' => max(0, (int) ($izin[$s['id']] ?? 0)),
                'tka' => max(0, (int) ($tka[$s['id']] ?? 0)),
            ];
            $ada = one('SELECT id FROM kehadiran WHERE siswa_id=? AND ta=? AND semester=?',
                [$s['id'], $c['ta'], $c['semester']]);
            if ($ada) {
                update_row('kehadiran', $data, 'id=?', [$ada['id']]);
            } else {
                insert_row('kehadiran', $data + [
                    'siswa_id' => $s['id'], 'ta' => $c['ta'], 'semester' => $c['semester'],
                ]);
            }
            $tersimpan++;
        }
        db()->commit();
        flash_set('success', "Data kehadiran tersimpan untuk $tersimpan siswa.");
    } catch (Throwable $ex) {
        db()->rollBack();
        flash_set('error', 'Gagal menyimpan: ' . $ex->getMessage());
    }
    redirect('kehadiran');
}

$existing = [];
foreach (all('SELECT * FROM kehadiran WHERE ta=? AND semester=?', [$c['ta'], $c['semester']]) as $r) {
    $existing[$r['siswa_id']] = $r;
}

page_head('Kehadiran Siswa', 'Rekap ketidakhadiran semester berjalan: Sakit, Izin, dan Tanpa Keterangan.');
echo context_bar();
?>
<?php if (!$siswas): ?>
  <div class="card empty">Belum ada siswa pada kelas <?= $c['kelas'] ?>. <a href="index.php?page=siswa">Tambah data siswa</a>.</div>
<?php else: ?>
<form method="post" action="index.php?page=kehadiran">
  <?= csrf_field() ?>
  <div class="table-wrap">
    <table class="tbl">
      <caption>Kelas <?= $c['kelas'] ?> - Semester <?= $c['semester'] ?> - <?= e($c['ta']) ?></caption>
      <thead>
        <tr>
          <th class="center" style="width:40px">No</th>
          <th>Nama Siswa</th>
          <th class="center" style="width:130px">Sakit (hari)</th>
          <th class="center" style="width:130px">Izin (hari)</th>
          <th class="center" style="width:170px">Tanpa Keterangan (hari)</th>
          <th class="center">Total</th>
        </tr>
      </thead>
      <tbody>
      <?php $no = 0; foreach ($siswas as $s): $no++;
          $k = $existing[$s['id']] ?? null;
          $sa = (int) ($k['sakit'] ?? 0);
          $iz = (int) ($k['izin'] ?? 0);
          $tk = (int) ($k['tka'] ?? 0); ?>
        <tr>
          <td class="center"><?= $no ?></td>
          <td class="name-cell"><?= e($s['nama']) ?><div class="hint">NISN <?= e($s['nisn']) ?></div></td>
          <td class="center"><input type="number" name="sakit[<?= (int) $s['id'] ?>]" min="0" max="180" value="<?= $sa ?>"></td>
          <td class="center"><input type="number" name="izin[<?= (int) $s['id'] ?>]" min="0" max="180" value="<?= $iz ?>"></td>
          <td class="center"><input type="number" name="tka[<?= (int) $s['id'] ?>]" min="0" max="180" value="<?= $tk ?>" <?= $tk > 0 ? 'class="nilai-red"' : '' ?>></td>
          <td class="center"><b><?= $sa + $iz + $tk ?></b></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="toolbar mt">
    <button class="btn" type="submit">Simpan Kehadiran</button>
    <span class="hint">Rekap jangka satu semester, ditampilkan pada tabel kehadiran raport.</span>
  </div>
</form>
<?php endif; ?>
