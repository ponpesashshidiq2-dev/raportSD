<?php
$c = ctx();
$kaKunci = kelas_angkatan();
$kelasSiswa = $kaKunci ?? (int) $c['kelas'];
$siswas = all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=? ORDER BY nama", [$kelasSiswa]);
$ekstraList = all('SELECT * FROM ekstra ORDER BY urutan, nama');
$predikatOps = ['' => '-', 'A' => 'A (Sangat Baik)', 'B' => 'B (Baik)', 'C' => 'C (Cukup)', 'D' => 'D (Kurang)'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk menginput nilai.');
        redirect('nilai_ekstra');
    }
    $nilai = $_POST['nilai'] ?? [];
    $ekdesk = $_POST['ekdesk'] ?? [];
    $tersimpan = 0;
    db()->beginTransaction();
    try {
        foreach ($ekstraList as $ek) {
            $teks = trim((string) ($ekdesk[$ek['id']] ?? ''));
            $where = 'ekstra_id=? AND ta=? AND semester=? AND kelas=?';
            $par = [$ek['id'], $c['ta'], $c['semester'], $c['kelas']];
            $adaD = one("SELECT ekstra_id FROM ekstra_deskripsi WHERE $where", $par);
            if ($teks === '') {
                if ($adaD) {
                    run("DELETE FROM ekstra_deskripsi WHERE $where", $par);
                }
                run("UPDATE nilai_ekstra SET catatan='' WHERE ekstra_id=? AND ta=? AND semester=? AND kelas=?", $par);
            } elseif ($adaD) {
                update_row('ekstra_deskripsi', ['deskripsi' => $teks], $where, $par);
            } else {
                insert_row('ekstra_deskripsi', ['ekstra_id' => $ek['id'], 'ta' => $c['ta'],
                    'semester' => $c['semester'], 'kelas' => $c['kelas'], 'deskripsi' => $teks]);
            }
        }
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
        flash_set('success', "Nilai ekstrakurikuler tersimpan: $tersimpan sel. Deskripsi per kegiatan tersimpan sekali untuk semua siswa.");
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
foreach ($ekstraList as $ek) {
    if (($ekDesk[(int) $ek['id']] ?? '') === '' && isset($legacy[(int) $ek['id']])) {
        $ekDesk[(int) $ek['id']] = $legacy[(int) $ek['id']];
    }
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
  <div class="card">
    <h3>Deskripsi Ekstrakurikuler — diisi sekali per kegiatan</h3>
    <p class="hint">Diisi sebagai lanjutan kalimat pada raport, mis. <i>"Ananda Budi Santoso sangat baik dalam keterampilan ..."</i> —
      predikat A = <b>sangat baik</b>, B = <b>Baik</b>, C = <b>Cukup</b>, D = <b>Kurang</b>; teks yang sama tampil untuk semua siswa
      (kolom predikat tidak dicetak, hanya nama kegiatan + keterangan). Kosongkan untuk tidak menampilkan keterangan.</p>
    <?php foreach ($ekstraList as $ek): ?>
      <div class="field" style="margin-top:8px">
        <label><?= e($ek['nama']) ?></label>
        <input type="text" name="ekdesk[<?= (int) $ek['id'] ?>]" value="<?= e($ekDesk[(int) $ek['id']] ?? '') ?>"
          maxlength="300" placeholder="Deskripsi kegiatan (opsional)">
      </div>
    <?php endforeach; ?>
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
  <div class="toolbar mt">
    <button class="btn" type="submit">Simpan Nilai Ekstrakurikuler</button>
    <span class="hint">Pilih <b>-</b> untuk menghapus nilai. Deskripsi diisi <b>sekali per kegiatan</b> (kartu di atas) dan tampil sama untuk semua siswa pada raport.</span>
  </div>
</form>
<?php endif; ?>
