<?php
$c = ctx();
$siswas = all("SELECT id, nama FROM siswa WHERE status='aktif' AND kelas=? ORDER BY nama", [$c['kelas']]);
$kegiatanList = all('SELECT * FROM kokurikuler_kegiatan ORDER BY urutan, nama');
$dimensi = dimensi_kokurikuler();
$ops = predikat_kokurikuler_ops();

$kegAktif = (int) ($_GET['keg'] ?? 0);
$adaKegiatanAktif = false;
foreach ($kegiatanList as $kk) {
    if ((int) $kk['id'] === $kegAktif) {
        $adaKegiatanAktif = true;
    }
}
if (!$adaKegiatanAktif) {
    $kegAktif = $kegiatanList ? (int) $kegiatanList[0]['id'] : 0;
}
$kegNama = '';
foreach ($kegiatanList as $kk) {
    if ((int) $kk['id'] === $kegAktif) {
        $kegNama = $kk['nama'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk menginput nilai kokurikuler.');
        redirect('nilai_kokurikuler');
    }
    $keg = (int) ($_POST['keg'] ?? 0);
    if (!one('SELECT id FROM kokurikuler_kegiatan WHERE id=?', [$keg])) {
        $keg = $kegAktif;
    }
    $ketInput = $_POST['ket'] ?? [];
    $predInput = $_POST['predikat'] ?? [];
    $deskInput = $_POST['desk'] ?? [];
    db()->beginTransaction();
    try {
        foreach ($siswas as $s) {
            $sid = (int) $s['id'];

            if ($keg > 0) {
                $t = trim((string) ($ketInput[$sid] ?? ''));
                $ada = one('SELECT id FROM kokurikuler_nilai WHERE siswa_id=? AND kegiatan_id=? AND ta=? AND semester=?',
                    [$sid, $keg, $c['ta'], $c['semester']]);
                if ($t === '') {
                    if ($ada) {
                        run('DELETE FROM kokurikuler_nilai WHERE id=?', [$ada['id']]);
                    }
                } elseif ($ada) {
                    update_row('kokurikuler_nilai', ['keterangan' => $t], 'id=?', [$ada['id']]);
                } else {
                    insert_row('kokurikuler_nilai', [
                        'siswa_id' => $sid, 'kegiatan_id' => $keg,
                        'ta' => $c['ta'], 'semester' => $c['semester'], 'keterangan' => $t,
                    ]);
                }
            }

            $arr = [];
            foreach (array_keys($dimensi) as $i => $label) {
                $v = trim((string) ($predInput[$sid][$i] ?? ''));
                if (in_array($v, ['Berkembang', 'Cakap', 'Mahir'], true)) {
                    $arr[$i] = $v;
                }
            }
            $d = trim((string) ($deskInput[$sid] ?? ''));
            $adaC = one('SELECT id FROM kokurikuler_capaian WHERE siswa_id=? AND ta=? AND semester=?',
                [$sid, $c['ta'], $c['semester']]);
            if (!$arr && $d === '') {
                if ($adaC) {
                    run('DELETE FROM kokurikuler_capaian WHERE id=?', [$adaC['id']]);
                }
            } else {
                $row = ['predikat' => json_encode($arr), 'deskripsi' => $d];
                if ($adaC) {
                    update_row('kokurikuler_capaian', $row, 'id=?', [$adaC['id']]);
                } else {
                    insert_row('kokurikuler_capaian', $row + [
                        'siswa_id' => $sid, 'ta' => $c['ta'], 'semester' => $c['semester'],
                    ]);
                }
            }
        }

        $gen = (string) ($_POST['generate'] ?? '');
        $tergenerate = 0;
        if ($gen !== '') {
            $targets = $gen === 'all'
                ? array_map(fn($s) => (int) $s['id'], $siswas)
                : [(int) $gen];
            foreach ($targets as $tid) {
                $row = one('SELECT id, predikat FROM kokurikuler_capaian WHERE siswa_id=? AND ta=? AND semester=?',
                    [$tid, $c['ta'], $c['semester']]);
                if (!$row) {
                    continue;
                }
                $arr = json_decode($row['predikat'] ?: '[]', true) ?: [];
                $text = generate_deskripsi_kokurikuler($arr);
                if ($text !== '') {
                    update_row('kokurikuler_capaian', ['deskripsi' => $text], 'id=?', [$row['id']]);
                    $tergenerate++;
                }
            }
        }
        db()->commit();
        $pesan = 'Nilai kokurikuler tersimpan.';
        if ($gen !== '') {
            $pesan .= " Deskripsi digenerate untuk $tergenerate siswa.";
        }
        flash_set('success', $pesan);
    } catch (Throwable $ex) {
        db()->rollBack();
        flash_set('error', 'Gagal menyimpan: ' . $ex->getMessage());
    }
    redirect('nilai_kokurikuler', ['keg' => $keg]);
}

$ketMap = [];
foreach (all('SELECT siswa_id, keterangan FROM kokurikuler_nilai WHERE kegiatan_id=? AND ta=? AND semester=?',
    [$kegAktif, $c['ta'], $c['semester']]) as $r) {
    $ketMap[(int) $r['siswa_id']] = $r['keterangan'];
}
$capMap = [];
foreach (all('SELECT siswa_id, predikat, deskripsi FROM kokurikuler_capaian WHERE ta=? AND semester=?',
    [$c['ta'], $c['semester']]) as $r) {
    $capMap[(int) $r['siswa_id']] = [
        'predikat' => json_decode($r['predikat'] ?: '[]', true) ?: [],
        'deskripsi' => $r['deskripsi'],
    ];
}

page_head('Input Nilai Kokurikuler',
    'Keterangan per kegiatan, capaian 8 Dimensi Profil Lulusan (Berkembang/Cakap/Mahir), dan deskripsi kokurikuler yang tercetak pada raport.');
echo context_bar();
if (!$kegiatanList): ?>
  <div class="alert alert-warning">Belum ada kegiatan kokurikuler. Tambahkan pada menu <a href="index.php?page=kokurikuler">Kokurikuler</a>.</div>
<?php elseif (!$siswas): ?>
  <div class="card empty">Belum ada siswa pada kelas <?= $c['kelas'] ?>. <a href="index.php?page=siswa">Tambah data siswa</a>.</div>
<?php else: ?>
<div class="toolbar">
  <form method="get" action="index.php" style="display:flex;gap:10px;align-items:center;margin:0">
    <input type="hidden" name="page" value="nilai_kokurikuler">
    <label><b>Pilih Kegiatan</b></label>
    <select name="keg" onchange="this.form.submit()">
      <?php foreach ($kegiatanList as $kk): ?>
        <option value="<?= (int) $kk['id'] ?>"<?= (int) $kk['id'] === $kegAktif ? ' selected' : '' ?>><?= e($kk['nama']) ?></option>
      <?php endforeach; ?>
    </select>
    <span class="hint">Kegiatan dikelola pada menu <a href="index.php?page=kokurikuler">Kokurikuler</a></span>
  </form>
</div>

<form method="post" action="index.php?page=nilai_kokurikuler">
  <?= csrf_field() ?>
  <input type="hidden" name="keg" value="<?= $kegAktif ?>">

  <div class="card">
    <h3>Keterangan Kegiatan &middot; <?= e($kegNama) ?></h3>
    <p class="hint">Kolom Keterangan pada tabel Kokurikuler raport (baris tampil hanya jika diisi).</p>
    <div class="table-wrap">
      <table class="tbl">
        <caption>Kelas <?= $c['kelas'] ?> - Semester <?= $c['semester'] ?> - <?= e($c['ta']) ?></caption>
        <thead><tr><th class="center" style="width:40px">No</th><th>Nama Siswa</th><th>Keterangan</th></tr></thead>
        <tbody>
        <?php $no = 0; foreach ($siswas as $s): $no++; $sid = (int) $s['id']; ?>
          <tr>
            <td class="center"><?= $no ?></td>
            <td class="name-cell"><?= e($s['nama']) ?></td>
            <td><input type="text" name="ket[<?= $sid ?>]" value="<?= e($ketMap[$sid] ?? '') ?>" placeholder="mis. Ikut serta dengan tertib dan aktif" style="min-width:340px"></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h3>Capaian 8 Dimensi Profil Lulusan &amp; Deskripsi Kokurikuler</h3>
    <p class="hint">Predikat <b>Berkembang / Cakap / Mahir</b> (Permendikdasmen No. 13 Tahun 2025) menjadi dasar generate deskripsi. Yang tercetak pada raport adalah kolom <b>Deskripsi</b> — bukan predikat, sesuai Panduan Pembelajaran dan Asesmen 2025.</p>
    <div class="table-wrap">
      <table class="tbl">
        <caption>Kelas <?= $c['kelas'] ?> - Semester <?= $c['semester'] ?> - <?= e($c['ta']) ?></caption>
        <thead>
          <tr>
            <th class="center" style="width:40px">No</th>
            <th>Nama Siswa</th>
            <?php foreach ($dimensi as $label => $teks): ?>
              <th class="center" title="<?= e($teks) ?>" style="min-width:118px"><?= e($label) ?></th>
            <?php endforeach; ?>
            <th style="min-width:300px">Deskripsi Kokurikuler</th>
          </tr>
        </thead>
        <tbody>
        <?php $no = 0; foreach ($siswas as $s): $no++; $sid = (int) $s['id'];
            $cap = $capMap[$sid] ?? ['predikat' => [], 'deskripsi' => '']; ?>
          <tr>
            <td class="center"><?= $no ?></td>
            <td class="name-cell"><?= e($s['nama']) ?></td>
            <?php foreach (array_keys($dimensi) as $i => $label): ?>
              <td class="center">
                <select name="predikat[<?= $sid ?>][<?= $i ?>]" style="min-width:110px">
                  <?php foreach ($ops as $val => $lbl): ?>
                    <option value="<?= e($val) ?>"<?= (string) (($cap['predikat'][$i] ?? '') ?: '') === $val ? ' selected' : '' ?>><?= e($lbl) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
            <?php endforeach; ?>
            <td>
              <textarea name="desk[<?= $sid ?>]" rows="3" style="width:100%;min-width:280px" placeholder="deskripsi capaian profil lulusan — tekan tombol Generate untuk mengisi otomatis dari predikat"><?= e($cap['deskripsi']) ?></textarea>
              <button class="btn sm secondary" type="submit" name="generate" value="<?= $sid ?>" title="Generate deskripsi siswa ini dari predikat" style="margin-top:4px">Generate</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="toolbar mt">
    <button class="btn" type="submit">Simpan Semua</button>
    <button class="btn secondary" type="submit" name="generate" value="all">Generate Semua Deskripsi dari Predikat</button>
    <span class="hint">Pilih <b>-</b> untuk menghapus predikat. Deskripsi hasil generate tetap dapat disunting manual sebelum/sesudah generate.</span>
  </div>
</form>
<?php endif; ?>
