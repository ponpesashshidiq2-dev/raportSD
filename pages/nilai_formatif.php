<?php
$c = ctx();
$mapelList = mapel_kelas($c['kelas']);
$mapelId = (int) ($_GET['mapel'] ?? 0);
if ($mapelId <= 0 && $mapelList) {
    $mapelId = (int) $mapelList[0]['id'];
}
$mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
$allTps = $mapelId > 0 ? all('SELECT * FROM tp WHERE mapel_id=? AND fase=? ORDER BY urutan, id', [$mapelId, $c['fase']]) : [];
$tpSel = (int) ($_GET['tp'] ?? 0);
if ($tpSel && !array_filter($allTps, fn($t) => (int) $t['id'] === $tpSel)) {
    $tpSel = 0;
}
$tps = $tpSel ? array_values(array_filter($allTps, fn($t) => (int) $t['id'] === $tpSel)) : $allTps;
$siswas = all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=? ORDER BY nama", [$c['kelas']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk menginput nilai.');
        redirect('nilai_formatif');
    }
    $mapelId = (int) ($_POST['mapel_id'] ?? 0);
    $mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
    $tpFilter = (int) ($_POST['tp_filter'] ?? 0);
    if (!$mapel) {
        flash_set('error', 'Mata pelajaran tidak valid.');
        redirect('nilai_formatif');
    }
    $tpList = array_column(all('SELECT * FROM tp WHERE mapel_id=? AND fase=?', [$mapelId, $c['fase']]), null, 'id');
    $siswaList = array_column($siswas, null, 'id');
    $tersimpan = 0;
    db()->beginTransaction();
    try {
        foreach (($_POST['nilai'] ?? []) as $key => $val) {
            if (!preg_match('/^(\d+)_(\d+)$/', (string) $key, $mm)) {
                continue;
            }
            $tpId = (int) $mm[1];
            $sId = (int) $mm[2];
            if (!isset($tpList[$tpId]) || !isset($siswaList[$sId])) {
                continue;
            }
            $raw = trim((string) $val);
            $ada = one('SELECT id FROM nilai_formatif WHERE siswa_id=? AND tp_id=? AND ta=? AND semester=?',
                [$sId, $tpId, $c['ta'], $c['semester']]);
            if ($raw === '') {
                if ($ada) {
                    run('DELETE FROM nilai_formatif WHERE id=?', [$ada['id']]);
                }
                continue;
            }
            $v = (float) str_replace(',', '.', $raw);
            if ($v < 0) $v = 0;
            if ($v > 100) $v = 100;
            $row = ['nilai' => $v, 'kelas' => $c['kelas']];
            if ($ada) {
                update_row('nilai_formatif', $row, 'id=?', [$ada['id']]);
            } else {
                insert_row('nilai_formatif', $row + [
                    'siswa_id' => $sId, 'tp_id' => $tpId, 'mapel_id' => $mapelId,
                    'ta' => $c['ta'], 'semester' => $c['semester'],
                ]);
            }
            $tersimpan++;
        }
        db()->commit();
        flash_set('success', "Nilai formatif tersimpan: $tersimpan sel. Kolom yang dikosongkan akan dihapus.");
    } catch (Throwable $ex) {
        db()->rollBack();
        flash_set('error', 'Gagal menyimpan: ' . $ex->getMessage());
    }
    redirect('nilai_formatif', array_filter(['mapel' => $mapelId, 'tp' => $tpFilter]));
}

page_head('Input Nilai Formatif', 'Nilai setiap Tujuan Pembelajaran (TP) per siswa pada konteks aktif.');
echo context_bar();
?>
<form class="toolbar" method="get" action="index.php">
  <input type="hidden" name="page" value="nilai_formatif">
  <label style="margin:0">Mata Pelajaran</label>
  <select name="mapel" onchange="this.form.submit()">
    <?php foreach ($mapelList as $m): ?>
      <option value="<?= (int) $m['id'] ?>"<?= $mapelId === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['nama']) ?></option>
    <?php endforeach; ?>
  </select>
  <label style="margin:0">Tujuan Pembelajaran</label>
  <select name="tp" onchange="this.form.submit()">
    <option value="0">Semua TP (<?= count($allTps) ?>)</option>
    <?php foreach ($allTps as $t): ?>
      <option value="<?= (int) $t['id'] ?>"<?= $tpSel === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['kode']) ?> - <?= e(mb_strimwidth($t['deskripsi'], 0, 46, '…')) ?></option>
    <?php endforeach; ?>
  </select>
  <span class="badge badge-blue">Fase <?= e($c['fase']) ?></span>
  <span class="badge badge-gray"><?= count($tps) ?> TP ditampilkan</span>
  <span class="hint">Pilihan TP otomatis mengikuti mapel yang dipilih.</span>
</form>

<?php if (!$mapel): ?>
  <div class="card empty">Belum ada mata pelajaran untuk kelas <?= $c['kelas'] ?>. Tambahkan pada menu <a href="index.php?page=mapel">Mata Pelajaran</a>.</div>
<?php elseif (!$allTps): ?>
  <div class="alert alert-warning">Belum ada Tujuan Pembelajaran untuk mapel <b><?= e($mapel['nama']) ?></b> pada <?= e(fase_label($c['fase'])) ?>. Isi terlebih dahulu pada menu <a href="index.php?page=tp&mapel=<?= $mapelId ?>">Tujuan Pembelajaran</a>.</div>
<?php elseif (!$siswas): ?>
  <div class="card empty">Belum ada siswa pada kelas <?= $c['kelas'] ?>. <a href="index.php?page=siswa">Tambah data siswa</a>.</div>
<?php else:
  $existing = [];
  foreach (all('SELECT tp_id, siswa_id, nilai FROM nilai_formatif WHERE mapel_id=? AND ta=? AND semester=?',
      [$mapelId, $c['ta'], $c['semester']]) as $r) {
      $existing[$r['tp_id'] . '_' . $r['siswa_id']] = $r['nilai'];
  }
  ?>
  <?php if ($tpSel):
      $tpNow = null;
      foreach ($allTps as $t) {
          if ((int) $t['id'] === $tpSel) {
              $tpNow = $t;
              break;
          }
      } ?>
    <?php if ($tpNow): ?>
    <div class="alert alert-info">Menampilkan TP terpilih: <b><?= e($tpNow['kode']) ?></b> - <?= e($tpNow['deskripsi']) ?>. Pilih "Semua TP" untuk menampilkan seluruh TP mapel ini.</div>
    <?php endif; ?>
  <?php endif; ?>
  <form method="post" action="index.php?page=nilai_formatif">
    <?= csrf_field() ?>
    <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
    <input type="hidden" name="tp_filter" value="<?= $tpSel ?>">
    <div class="table-wrap">
      <table class="tbl">
        <caption><?= e($mapel['nama']) ?> - Kelas <?= $c['kelas'] ?> - Semester <?= $c['semester'] ?> - <?= e($c['ta']) ?></caption>
        <thead>
          <tr>
            <th class="center" style="width:40px">No</th>
            <th>Nama Siswa</th>
            <?php foreach ($tps as $t): ?>
              <th class="center" title="<?= e($t['deskripsi']) ?>"><?= e($t['kode']) ?><div class="hint" style="font-weight:400;text-transform:none;letter-spacing:0"><?= e(mb_strimwidth($t['deskripsi'], 0, 42, '…')) ?></div></th>
            <?php endforeach; ?>
            <th class="center">Rata Formatif<?= $tpSel ? ' (TP ini)' : '' ?></th>
          </tr>
        </thead>
        <tbody>
        <?php $no = 0; foreach ($siswas as $s): $no++;
            $sum = 0; $cnt = 0; ?>
          <tr>
            <td class="center"><?= $no ?></td>
            <td class="name-cell"><?= e($s['nama']) ?><div class="hint">NISN <?= e($s['nisn']) ?></div></td>
            <?php foreach ($tps as $t):
                $k = $t['id'] . '_' . $s['id'];
                $v = $existing[$k] ?? null;
                if ($v !== null) { $sum += (float) $v; $cnt++; } ?>
              <td class="center">
                <input type="number" name="nilai[<?= $k ?>]" min="0" max="100" step="0.5" value="<?= $v !== null ? e(format_nilai((float) $v)) : '' ?>" placeholder="-">
              </td>
            <?php endforeach; ?>
            <td class="center"><b><?= $cnt ? format_nilai($sum / $cnt) : '-' ?></b></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="toolbar mt">
      <button class="btn" type="submit">Simpan Nilai Formatif</button>
      <span class="hint">Kosongkan sel untuk menghapus nilai. Rentang 0-100. Penyimpanan hanya memengaruhi TP yang ditampilkan.</span>
    </div>
  </form>
<?php endif; ?>
