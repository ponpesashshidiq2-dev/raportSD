<?php
$c = ctx();
$kaKunci = kelas_angkatan();
$mapelList = mapel_kelas($c['kelas']);
$mapelId = (int) ($_GET['mapel'] ?? 0);
$mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
if (!$mapel && $mapelList) {
    $mapelId = (int) $mapelList[0]['id'];
    $mapel = one('SELECT * FROM mapel WHERE id=?', [$mapelId]);
} elseif (!$mapel) {
    $mapelId = 0;
}
$uidDomain = (int) (user()['id'] ?? 0);
$siswas = all("SELECT id, nama, nisn, kelas FROM siswa WHERE " . scope_status((int) $c['kelas']) . scope_user() . " ORDER BY kelas, nama");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk menginput nilai.');
        redirect('nilai_sumatif');
    }
    $aksi = $_POST['aksi'] ?? '';
    $mapelId = (int) ($_POST['mapel_id'] ?? $mapelId);
    $mapel = $mapelId > 0 ? one('SELECT * FROM mapel WHERE id=?', [$mapelId]) : null;
    if (!$mapel) {
        flash_set('error', 'Mata pelajaran tidak valid.');
        redirect('nilai_sumatif');
    }
    $siswas = all("SELECT id, nama, nisn, kelas FROM siswa WHERE " . scope_status((int) $c['kelas']) . scope_user() . " ORDER BY kelas, nama");

    if ($aksi === 'jumlah_terapkan') {
        $jumlah = (int) ($_POST['jumlah'] ?? -1);
        if ($mapelId <= 0) {
            flash_set('error', 'Pilih mata pelajaran terlebih dahulu.');
        } elseif ($jumlah < 0 || $jumlah > 50) {
            flash_set('error', 'Jumlah kolom harus antara 0 dan 50.');
        } else {
            db()->beginTransaction();
            try {
                sumatif_aes_pastikan($mapelId, (int) $c['kelas'], $c['ta'], $c['semester'], $uidDomain);
                $ada = all(
                    "SELECT id, urutan, nama FROM sumatif_jenis
                     WHERE mapel_id=? AND kelas=? AND ta=? AND semester=? AND user_id=? AND tipe='lingkup'
                     ORDER BY urutan, id",
                    [$mapelId, $c['kelas'], $c['ta'], $c['semester'], $uidDomain]
                );
                $n = count($ada);
                if ($n > $jumlah) {
                    foreach (array_slice($ada, $jumlah) as $r) {
                        run('DELETE FROM nilai_sumatif WHERE jenis_id=?', [$r['id']]);
                        run('DELETE FROM sumatif_jenis WHERE id=?', [$r['id']]);
                    }
                } elseif ($n < $jumlah) {
                    $urut = $ada ? (int) max(array_column($ada, 'urutan')) : 0;
                    for ($k = $n + 1; $k <= $jumlah; $k++) {
                        $urut++;
                        $suffix = '';
                        $x = 1;
                        while (one(
                            'SELECT id FROM sumatif_jenis WHERE mapel_id=? AND kelas=? AND ta=? AND semester=? AND user_id=? AND nama=?',
                            [$mapelId, $c['kelas'], $c['ta'], $c['semester'], $uidDomain, 'Lingkup Materi ' . $urut . $suffix]
                        )) {
                            $x++;
                            $suffix = ' (' . $x . ')';
                        }
                        insert_row('sumatif_jenis', [
                            'mapel_id' => $mapelId, 'ta' => $c['ta'], 'semester' => $c['semester'],
                            'kelas' => (int) $c['kelas'], 'user_id' => $uidDomain, 'tipe' => 'lingkup',
                            'urutan' => $urut, 'nama' => 'Lingkup Materi ' . $urut . $suffix, 'tanggal' => '',
                        ]);
                    }
                }
                db()->commit();
                flash_set('success', "Jumlah kolom lingkup materi: $jumlah (Asesmen Akhir Semester Non-Tes & Tes tersedia otomatis).");
            } catch (Throwable $ex) {
                db()->rollBack();
                flash_set('error', 'Gagal menyimpan jumlah kolom: ' . $ex->getMessage());
            }
        }
        redirect('nilai_sumatif', ['mapel' => $mapelId]);
    }

    if ($aksi === 'simpan') {
        $jenis = sumatif_kolom_list($mapelId, (int) $c['kelas'], $c['ta'], $c['semester'], $uidDomain);
        $nilai = $_POST['nilai'] ?? [];
        $tersimpan = 0;
        db()->beginTransaction();
        try {
            foreach ($jenis as $j) {
                foreach ($siswas as $s) {
                    $key = $j['id'] . '_' . $s['id'];
                    $raw = trim((string) ($nilai[$key] ?? ''));
                    $ada = one('SELECT id FROM nilai_sumatif WHERE siswa_id=? AND jenis_id=? AND ta=? AND semester=?',
                        [$s['id'], $j['id'], $c['ta'], $c['semester']]);
                    if ($raw === '') {
                        if ($ada) {
                            run('DELETE FROM nilai_sumatif WHERE id=?', [$ada['id']]);
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
                        update_row('nilai_sumatif', $row, 'id=?', [$ada['id']]);
                    } else {
                        insert_row('nilai_sumatif', $row + [
                            'siswa_id' => $s['id'], 'mapel_id' => $mapelId, 'jenis_id' => $j['id'],
                            'ta' => $c['ta'], 'semester' => $c['semester'],
                        ]);
                    }
                    $tersimpan++;
                }
            }
            db()->commit();
            flash_set('success', "Nilai sumatif tersimpan: $tersimpan sel.");
        } catch (Throwable $ex) {
            db()->rollBack();
            flash_set('error', 'Gagal menyimpan: ' . $ex->getMessage());
        }
        redirect('nilai_sumatif', ['mapel' => $mapelId]);
    }
}

if ($mapelId > 0 && can_edit()) {
    sumatif_aes_pastikan($mapelId, (int) $c['kelas'], $c['ta'], $c['semester'], $uidDomain);
}
$jenisList = $mapelId > 0 ? sumatif_kolom_list($mapelId, (int) $c['kelas'], $c['ta'], $c['semester'], $uidDomain) : [];
$lingkupList = array_values(array_filter($jenisList, fn($j) => $j['tipe'] === 'lingkup'));
$aesList = array_values(array_filter($jenisList, fn($j) => $j['tipe'] === 'aes'));

page_head('Input Nilai Sumatif', 'Atur jumlah kolom Lingkup Materi lalu isi nilainya; Asesmen Akhir Semester (Non-Tes & Tes) tersedia otomatis.', mode_switch());
echo context_bar();
?>
<div class="mapel-bar">
  <span class="mapel-bar-label">Mata Pelajaran</span>
  <div class="mapel-btns">
    <?php foreach ($mapelList as $m): ?>
      <a class="mapel-btn<?= $mapelId === (int) $m['id'] ? ' on' : '' ?>" href="index.php?page=nilai_sumatif&mapel=<?= (int) $m['id'] ?>"><?= e($m['nama']) ?></a>
    <?php endforeach; ?>
    <?php if (!$mapelList): ?><span class="muted" style="font-size:13px">Belum ada mata pelajaran untuk kelas ini.</span><?php endif; ?>
  </div>
  <span class="badge badge-blue">Kelas <?= $c['kelas'] ?> &middot; Semester <?= $c['semester'] ?></span>
  <span class="badge badge-gray"><?= count($lingkupList) ?> lingkup + <?= count($aesList) ?> AES</span>
</div>

<?php if (!$mapel): ?>
  <div class="card empty">Belum ada mata pelajaran untuk kelas <?= $c['kelas'] ?>.</div>
<?php else: ?>
<?php if (can_edit()): ?>
<div class="card">
  <h3>Jumlah Kolom Lingkup Materi</h3>
  <form method="post" action="index.php?page=nilai_sumatif">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="jumlah_terapkan">
    <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
    <div class="form-row">
      <div class="field">
        <label>Jumlah Kolom</label>
        <input type="number" name="jumlah" min="0" max="50" value="<?= count($lingkupList) ?>" required>
      </div>
    </div>
    <button class="btn" type="submit">Terapkan Jumlah Kolom</button>
    <span class="hint">Kolom baru berlabel "Lingkup Materi 1..N". Kolom "Asesmen Akhir Semester Non-Tes" dan "Tes" selalu tersedia otomatis. Jumlah tersimpan per pengguna, mapel, kelas, dan semester.</span>
  </form>
</div>
<?php endif; ?>

<?php if (!$jenisList): ?>
  <div class="card empty">Belum ada kolom penilaian untuk mapel ini pada <?= e($c['ta']) ?> Semester <?= $c['semester'] ?>. Atur jumlah kolom lingkup materi terlebih dahulu.</div>
<?php elseif (!$siswas): ?>
  <div class="card empty"><?= teks_kosong_siswa($kaKunci, (int) $c['kelas']) ?></div>
<?php else:
  $existing = [];
  foreach (all('SELECT jenis_id, siswa_id, nilai FROM nilai_sumatif WHERE mapel_id=? AND ta=? AND semester=?',
      [$mapelId, $c['ta'], $c['semester']]) as $r) {
      $existing[$r['jenis_id'] . '_' . $r['siswa_id']] = $r['nilai'];
  }
  ?>
  <form method="post" action="index.php?page=nilai_sumatif">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="mapel_id" value="<?= $mapelId ?>">
    <div class="save-bar">
      <?php if (can_edit()): ?><button class="btn" type="submit">Simpan Nilai Sumatif</button><?php endif; ?>
      <span class="grid-status" id="saveStatus" hidden>Belum tersimpan</span>
      <span class="hint">Tombol menempel di atas — simpan kapan saja tanpa menggulir ke bawah.</span>
    </div>
    <div class="table-wrap">
      <table class="tbl" data-grid>
        <caption><?= e($mapel['nama']) ?> - Kelas <?= $c['kelas'] ?> - Semester <?= $c['semester'] ?> - <?= e($c['ta']) ?></caption>
        <thead>
          <tr>
            <th class="center" style="width:40px" data-frozen="no">No</th>
            <th data-frozen="nama">Nama Siswa</th>
            <?php $hci = -1; foreach ($jenisList as $j): $hci++; ?>
              <th class="center" data-col="<?= $hci ?>"><?= e($j['nama']) ?></th>
            <?php endforeach; ?>
            <th class="center">Rata Sumatif</th>
            <th class="center">Nilai Akhir</th>
          </tr>
        </thead>
        <tbody>
        <?php $no = 0; foreach ($siswas as $s): $no++;
            $sl = 0; $cl = 0; $sa = 0; $ca = 0; ?>
          <tr>
            <td class="center" data-frozen="no" data-row-head="<?= $no - 1 ?>"><?= $no ?></td>
            <td class="name-cell" data-frozen="nama"><?= e($s['nama']) ?><div class="hint">Kelas <?= (int) $s['kelas'] ?> &middot; NISN <?= e($s['nisn']) ?></div></td>
            <?php $ci = -1; foreach ($jenisList as $j): $ci++;
                $k = $j['id'] . '_' . $s['id'];
                $v = $existing[$k] ?? null;
                if ($v !== null) {
                    if ($j['tipe'] === 'aes') { $sa += (float) $v; $ca++; } else { $sl += (float) $v; $cl++; }
                } ?>
              <td class="center cell" data-r="<?= $no - 1 ?>" data-c="<?= $ci ?>" data-group="<?= e($j['tipe']) ?>">
                <input type="text" inputmode="decimal" autocomplete="off" name="nilai[<?= $k ?>]" value="<?= $v !== null ? e(format_nilai((float) $v)) : '' ?>" placeholder="-">
              </td>
            <?php endforeach; ?>
            <?php
            $rata = null;
            if ($cl && $ca) {
                $rata = ($sl / $cl + $sa / $ca) / 2;
            } elseif ($cl) {
                $rata = $sl / $cl;
            } elseif ($ca) {
                $rata = $sa / $ca;
            }
            $na = nilai_akhir((int) $s['id'], $mapelId, $c['ta'], $c['semester']); ?>
            <td class="center" data-avg><b><?= $rata !== null ? format_nilai($rata) : '-' ?></b></td>
            <td class="center"><b class="<?= $na !== null && $na < (kkm_mapel($mapelId) ?? 0) ? 'nilai-red' : '' ?>"><?= $na !== null ? format_nilai($na) : '-' ?></b></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="hint mt">Rata Sumatif = rata-rata Lingkup Materi dan rata-rata Asesmen Akhir Semester. Nilai Akhir raport = rata-rata nilai sumatif (terupdate setelah simpan). Mode Spreadsheet: panah/Tab pindah sel, klik header untuk pilih kolom/baris, salin-tempel dari Excel didukung.</div>
  </form>
<?php endif; ?>
<?php endif; ?>
