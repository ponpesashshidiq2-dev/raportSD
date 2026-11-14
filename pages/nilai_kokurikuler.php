<?php
$c = ctx();
$siswas = all("SELECT id, nama FROM siswa WHERE status='aktif' AND kelas=? ORDER BY nama", [$c['kelas']]);
$dimensi = dimensi_kokurikuler();
$ops = predikat_kokurikuler_ops();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk menginput nilai kokurikuler.');
        redirect('nilai_kokurikuler');
    }
    $ketDimInput = $_POST['ket_dim'] ?? [];
    $predInput = $_POST['predikat'] ?? [];
    $deskInput = $_POST['desk'] ?? [];
    db()->beginTransaction();
    try {
        foreach (array_keys($dimensi) as $i => $label) {
            $t = trim((string) ($ketDimInput[$i] ?? ''));
            if (one('SELECT idx FROM kokurikuler_dimensi_ket WHERE idx=?', [$i])) {
                update_row('kokurikuler_dimensi_ket', ['teks' => $t], 'idx=?', [$i]);
            } else {
                insert_row('kokurikuler_dimensi_ket', ['idx' => $i, 'teks' => $t]);
            }
        }
        foreach ($siswas as $s) {
            $sid = (int) $s['id'];

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
                $namaT = 'Ananda';
                foreach ($siswas as $ss) {
                    if ((int) $ss['id'] === $tid) {
                        $namaT = $ss['nama'];
                    }
                }
                $text = generate_deskripsi_kokurikuler($arr, $namaT);
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
    redirect('nilai_kokurikuler');
}

$ketDimMap = dimensi_keterangan();
$capMap = [];
foreach (all('SELECT siswa_id, predikat, deskripsi FROM kokurikuler_capaian WHERE ta=? AND semester=?',
    [$c['ta'], $c['semester']]) as $r) {
    $capMap[(int) $r['siswa_id']] = [
        'predikat' => json_decode($r['predikat'] ?: '[]', true) ?: [],
        'deskripsi' => $r['deskripsi'],
    ];
}

page_head('Input Nilai Kokurikuler',
    'Keterangan per dimensi, capaian 8 Dimensi Profil Lulusan (Berkembang/Cakap/Mahir), dan deskripsi kokurikuler yang tercetak pada raport.');
echo context_bar();
if (!$siswas): ?>
  <div class="card empty">Belum ada siswa pada kelas <?= $c['kelas'] ?>. <a href="index.php?page=siswa">Tambah data siswa</a>.</div>
<?php else: ?>
<form method="post" action="index.php?page=nilai_kokurikuler">
  <?= csrf_field() ?>

  <div class="card">
    <h3>Keterangan per Dimensi Profil Lulusan</h3>
    <p class="hint">Diisi manual per dimensi (sekali, berlaku untuk semua siswa). Keterangan ini menjadi bagian
      <i>"yang terlihat dari kemampuan ..."</i> pada deskripsi otomatis rapor untuk siswa yang dimensinya diberi predikat.</p>
    <div class="form-row">
      <?php foreach (array_keys($dimensi) as $i => $label): ?>
        <div class="field">
          <label><?= e($label) ?></label>
          <input type="text" name="ket_dim[<?= $i ?>]" value="<?= e($ketDimMap[$i] ?? '') ?>" placeholder="mis. berinisiatif, bekerja sama secara efektif dalam kelompok">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <h3>Capaian 8 Dimensi Profil Lulusan &amp; Deskripsi Kokurikuler</h3>
    <p class="hint">Predikat <b>Berkembang / Cakap / Mahir</b> (Permendikdasmen No. 13 Tahun 2025) menjadi dasar deskripsi otomatis dengan format
      <i>"{Nama siswa} menunjukkan capaian yang sangat baik/baik/cukup baik dalam [dimensi yang terisi], yang terlihat dari kemampuan [keterangan per dimensi]."</i>
      Jika lebih dari satu dimensi terisi, kalimat disambung. Yang tercetak pada raport adalah kolom <b>Deskripsi</b> — bukan predikat.</p>
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
