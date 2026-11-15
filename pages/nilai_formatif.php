<?php
$c = ctx();
$kaKunci = kelas_angkatan();
$kelasSiswa = $kaKunci ?? (int) $c['kelas'];
$mapelList = mapel_kelas($c['kelas']);
$mapelId = (int) ($_GET['mapel'] ?? 0);
$mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
if (!$mapel && $mapelList) {
    $mapelId = (int) $mapelList[0]['id'];
    $mapel = one('SELECT * FROM mapel WHERE id=?', [$mapelId]);
} elseif (!$mapel) {
    $mapelId = 0;
}
$isTahsin = is_tahsin($mapel);
$jilidOpsi = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
$jilidSel = in_array($_GET['jilid'] ?? '', $jilidOpsi, true) ? (int) $_GET['jilid'] : 1;
$allTps = $mapelId > 0 ? all('SELECT * FROM tp WHERE mapel_id=? AND kelas=? AND jilid=? AND semester=? ORDER BY urutan, id',
    [$mapelId, $isTahsin ? 0 : $c['kelas'], $isTahsin ? $jilidSel : 0, $c['semester']]) : [];
$tpSel = (int) ($_GET['tp'] ?? 0);
if ($tpSel && !array_filter($allTps, fn($t) => (int) $t['id'] === $tpSel)) {
    $tpSel = 0;
}
$tps = $tpSel ? array_values(array_filter($allTps, fn($t) => (int) $t['id'] === $tpSel)) : $allTps;
$siswas = all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=?" . scope_user() . " ORDER BY nama", [$kelasSiswa]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk menginput nilai.');
        redirect('nilai_formatif');
    }
    $mapelId = (int) ($_POST['mapel_id'] ?? 0);
    $mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
    $isTahsin = is_tahsin($mapel);
    $tpFilter = (int) ($_POST['tp_filter'] ?? 0);
    $jilidFilter = ($isTahsin && in_array($_POST['jilid_filter'] ?? '', $jilidOpsi, true)) ? (int) $_POST['jilid_filter'] : 1;
    if (!$mapel) {
        flash_set('error', 'Mata pelajaran tidak valid.');
        redirect('nilai_formatif');
    }
    $siswas = all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=?" . scope_user() . " ORDER BY nama", [$kelasSiswa]);
    $tpList = array_column(all('SELECT * FROM tp WHERE mapel_id=? AND kelas=? AND jilid=? AND semester=?',
        [$mapelId, $isTahsin ? 0 : $c['kelas'], $isTahsin ? $jilidFilter : 0, $c['semester']]), null, 'id');
    $siswaList = array_column($siswas, null, 'id');
    $tersimpan = 0;
    $jilidTersimpan = 0;
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
            $num = str_replace(',', '.', $raw);
            if (!is_numeric($num)) {
                continue;
            }
            $v = (float) $num;
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
        if ($isTahsin) {
            foreach (($_POST['jilid'] ?? []) as $sidRaw => $jvRaw) {
                $sIdJ = (int) $sidRaw;
                $jv = (int) $jvRaw;
                if (!isset($siswaList[$sIdJ]) || $jv < 1 || $jv > 9) {
                    continue;
                }
                $adaJ = one('SELECT id FROM siswa_jilid WHERE siswa_id=? AND mapel_id=? AND ta=? AND semester=?',
                    [$sIdJ, $mapelId, $c['ta'], $c['semester']]);
                if ($adaJ) {
                    update_row('siswa_jilid', ['jilid' => $jv], 'id=?', [$adaJ['id']]);
                } else {
                    insert_row('siswa_jilid', ['siswa_id' => $sIdJ, 'mapel_id' => $mapelId,
                        'ta' => $c['ta'], 'semester' => $c['semester'], 'jilid' => $jv]);
                }
                $jilidTersimpan++;
            }
        }
        db()->commit();
        $pesan = "Nilai formatif tersimpan: $tersimpan sel. Kolom yang dikosongkan akan dihapus.";
        if ($isTahsin) {
            $pesan .= " Jilid $jilidTersimpan siswa tersimpan.";
        }
        flash_set('success', $pesan);
    } catch (Throwable $ex) {
        db()->rollBack();
        flash_set('error', 'Gagal menyimpan: ' . $ex->getMessage());
    }
    $qs = array_filter(['mapel' => $mapelId, 'tp' => $tpFilter] + ($isTahsin ? ['jilid' => $jilidFilter] : []));
    redirect('nilai_formatif', $qs);
}

page_head('Input Nilai Formatif', 'Nilai setiap Tujuan Pembelajaran (TP) per siswa pada konteks aktif.', mode_switch());
echo context_bar();
echo angkatan_lock_alert();
?>
<form class="toolbar" method="get" action="index.php">
  <input type="hidden" name="page" value="nilai_formatif">
  <label style="margin:0">Mata Pelajaran</label>
  <select name="mapel" onchange="this.form.submit()">
    <?php foreach ($mapelList as $m): ?>
      <option value="<?= (int) $m['id'] ?>"<?= $mapelId === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['nama']) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($isTahsin): ?>
  <label style="margin:0">Jilid</label>
  <select name="jilid" onchange="this.form.submit()">
    <?php for ($j = 1; $j <= 9; $j++): ?>
      <option value="<?= $j ?>"<?= $jilidSel === $j ? ' selected' : '' ?>>Jilid <?= $j ?></option>
    <?php endfor; ?>
  </select>
  <?php endif; ?>
  <label style="margin:0">Tujuan Pembelajaran</label>
  <select name="tp" onchange="this.form.submit()">
    <option value="0">Semua TP (<?= count($allTps) ?>)</option>
    <?php foreach ($allTps as $t): ?>
      <option value="<?= (int) $t['id'] ?>"<?= $tpSel === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['kode']) ?> - <?= e(mb_strimwidth($t['deskripsi'], 0, 46, '…')) ?></option>
    <?php endforeach; ?>
  </select>
  <span class="badge badge-blue">Kelas <?= $c['kelas'] ?> - Semester <?= $c['semester'] ?><?= $isTahsin ? ' - Jilid ' . $jilidSel : '' ?></span>
  <span class="badge badge-gray"><?= count($tps) ?> TP ditampilkan</span>
  <span class="hint"><?= $isTahsin
      ? 'Pemilih Jilid menentukan TP yang tampil (TP Tahsin mengikuti Jilid, bukan Kelas). Isi kolom Jilid tiap siswa pada tabel.'
      : 'Pilihan TP otomatis mengikuti mapel yang dipilih.' ?></span>
</form>

<?php if (!$mapel): ?>
  <div class="card empty">Belum ada mata pelajaran untuk kelas <?= $c['kelas'] ?>. Tambahkan pada menu <a href="index.php?page=mapel">Mata Pelajaran</a>.</div>
<?php elseif (!$allTps): ?>
  <div class="alert alert-warning">Belum ada Tujuan Pembelajaran untuk mapel <b><?= e($mapel['nama']) ?></b> <?= $isTahsin
      ? 'pada Jilid ' . $jilidSel . ' Semester ' . $c['semester']
      : 'pada Kelas ' . $c['kelas'] . ' Semester ' . $c['semester'] ?>. Isi terlebih dahulu pada menu <a href="index.php?page=tp&mapel=<?= $mapelId ?>&kelas=<?= $c['kelas'] ?>&sem=<?= $c['semester'] ?>&jilid=<?= $jilidSel ?>">Tujuan Pembelajaran</a>.</div>
<?php elseif (!$siswas): ?>
  <div class="card empty"><?= teks_kosong_siswa($kaKunci, (int) $c['kelas']) ?></div>
<?php else:
  $existing = [];
  foreach (all('SELECT tp_id, siswa_id, nilai FROM nilai_formatif WHERE mapel_id=? AND ta=? AND semester=?',
      [$mapelId, $c['ta'], $c['semester']]) as $r) {
      $existing[$r['tp_id'] . '_' . $r['siswa_id']] = $r['nilai'];
  }
  $jilidMap = [];
  if ($isTahsin) {
      foreach (all('SELECT siswa_id, jilid FROM siswa_jilid WHERE mapel_id=? AND ta=? AND semester=?',
          [$mapelId, $c['ta'], $c['semester']]) as $r) {
          $jilidMap[(int) $r['siswa_id']] = (int) $r['jilid'];
      }
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
    <input type="hidden" name="jilid_filter" value="<?= $jilidSel ?>">
    <div class="save-bar">
      <button class="btn" type="submit">Simpan Nilai Formatif</button>
      <span class="grid-status" id="saveStatus" hidden>Belum tersimpan</span>
      <span class="hint">Tombol menempel di atas — simpan kapan saja tanpa menggulir ke bawah.</span>
    </div>
    <div class="table-wrap">
      <table class="tbl" data-grid>
        <caption><?= e($mapel['nama']) ?><?= $isTahsin ? ' - Jilid ' . $jilidSel : '' ?> - Kelas <?= $c['kelas'] ?> - Semester <?= $c['semester'] ?> - <?= e($c['ta']) ?></caption>
        <thead>
          <tr>
            <th class="center" style="width:40px" data-frozen="no">No</th>
            <th data-frozen="nama">Nama Siswa</th>
            <?php if ($isTahsin): ?>
              <th class="center" style="width:90px">Jilid</th>
            <?php endif; ?>
            <?php $hci = -1; foreach ($tps as $t): $hci++; ?>
              <th class="center" data-col="<?= $hci ?>" title="<?= e($t['deskripsi']) ?>"><?= e($t['kode']) ?><div class="hint" style="font-weight:400;text-transform:none;letter-spacing:0"><?= e(mb_strimwidth($t['deskripsi'], 0, 42, '…')) ?></div></th>
            <?php endforeach; ?>
            <th class="center">Rata Formatif<?= $tpSel ? ' (TP ini)' : '' ?></th>
          </tr>
        </thead>
        <tbody>
        <?php $no = 0; foreach ($siswas as $s): $no++;
            $sum = 0; $cnt = 0; ?>
          <tr>
            <td class="center" data-frozen="no" data-row-head="<?= $no - 1 ?>"><?= $no ?></td>
            <td class="name-cell" data-frozen="nama"><?= e($s['nama']) ?><div class="hint">NISN <?= e($s['nisn']) ?></div></td>
            <?php if ($isTahsin): $jNow = $jilidMap[(int) $s['id']] ?? 1; ?>
              <td class="center">
                <select name="jilid[<?= (int) $s['id'] ?>]">
                  <?php for ($j = 1; $j <= 9; $j++): ?>
                    <option value="<?= $j ?>"<?= $jNow === $j ? ' selected' : '' ?>><?= $j ?></option>
                  <?php endfor; ?>
                </select>
              </td>
            <?php endif; ?>
            <?php $ci = -1; foreach ($tps as $t): $ci++;
                $k = $t['id'] . '_' . $s['id'];
                $v = $existing[$k] ?? null;
                if ($v !== null) { $sum += (float) $v; $cnt++; } ?>
              <td class="center cell" data-r="<?= $no - 1 ?>" data-c="<?= $ci ?>">
                <input type="text" inputmode="decimal" autocomplete="off" name="nilai[<?= $k ?>]" value="<?= $v !== null ? e(format_nilai((float) $v)) : '' ?>" placeholder="-">
              </td>
            <?php endforeach; ?>
            <td class="center" data-avg><b><?= $cnt ? format_nilai($sum / $cnt) : '-' ?></b></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="hint mt"><?= $isTahsin
        ? 'Kosongkan sel untuk menghapus nilai. Kolom Jilid menyimpan jilid tiap siswa (1-9) — TP yang dinilai mengikuti Jilid terpilih di atas.'
        : 'Kosongkan sel untuk menghapus nilai. Rentang 0-100. Penyimpanan hanya memengaruhi TP yang ditampilkan.' ?>
      Mode Spreadsheet: panah/Tab pindah sel, klik header untuk pilih kolom/baris, salin-tempel dari Excel didukung, Enter = simpan &amp; lanjut, Esc = batalkan.</div>
  </form>
<?php endif; ?>
