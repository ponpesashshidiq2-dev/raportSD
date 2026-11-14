<?php
$c = ctx();
$p = profil();
$kelas = $c['kelas'];

$semuaSiswa = all("SELECT id, nama, nisn, alamat, ortu FROM siswa WHERE status='aktif' AND kelas=? ORDER BY nama", [$kelas]);
$terpilih = [];
if (isset($_GET['s'])) {
    $ids = array_map('intval', (array) $_GET['s']);
    $terpilih = array_filter($semuaSiswa, fn($s) => in_array((int) $s['id'], $ids, true));
} else {
    $terpilih = $semuaSiswa;
}
$terpilih = array_values($terpilih);

$mapelList = mapel_raport($kelas);
$walikelas = walikelas($kelas, (int) $c['semester']);

page_head('Cetak Raport', 'Konteks: Kelas ' . $kelas . ' - Fase ' . $c['fase'] . ' - Semester ' . $c['semester'] . ' - TA ' . $c['ta'],
    '<button class="btn" id="printBtn" type="button">Cetak / Simpan PDF</button>');
?>
<div class="raport-tools no-print">
  <form method="get" action="index.php" class="card" style="margin:0;padding:12px 14px;width:100%">
    <input type="hidden" name="page" value="cetak">
    <div class="toolbar" style="margin:0">
      <label class="check"><input type="checkbox" data-select-all="s" checked> Pilih semua (<?= count($semuaSiswa) ?> siswa)</label>
      <span class="spacer"></span>
      <button class="btn secondary" type="submit">Tampilkan Terpilih</button>
      <button class="btn" type="button" id="printBtn">Cetak / Simpan PDF</button>
    </div>
    <div class="toolbar" style="margin:8px 0 0">
      <?php foreach ($semuaSiswa as $s):
          $checked = in_array((int) $s['id'], array_map(fn($x) => (int) $x['id'], $terpilih), true) || !isset($_GET['s']); ?>
        <label class="check" style="background:#f8fafc;border:1px solid var(--line);border-radius:8px;padding:4px 9px">
          <input type="checkbox" name="s[]" value="<?= (int) $s['id'] ?>"<?= $checked ? ' checked' : '' ?>> <?= e($s['nama']) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </form>
</div>

<?php if (!$mapelList): ?>
  <div class="card alert alert-warning">Belum ada mata pelajaran dengan KKM untuk kelas <?= $kelas ?>. Isi KKM pada menu <a href="index.php?page=mapel">Mata Pelajaran</a>.</div>
<?php endif; ?>
<?php if (!$terpilih): ?>
  <div class="card empty">Tidak ada siswa dipilih.</div>
<?php endif; ?>

<?php foreach ($terpilih as $s):
    $sid = (int) $s['id'];
    $romawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
    $angka = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh'];
    $capaianKok = one('SELECT predikat, deskripsi FROM kokurikuler_capaian WHERE siswa_id=? AND ta=? AND semester=?', [$sid, $c['ta'], $c['semester']]) ?: [];
    $deskKok = trim($capaianKok['deskripsi'] ?? '');
    if ($deskKok === '') {
        $arrPred = json_decode($capaianKok['predikat'] ?? '[]', true) ?: [];
        $deskKok = generate_deskripsi_kokurikuler($arrPred, $s['nama']);
    }
    $nilaiEkstra = all('SELECT e.nama, n.predikat, n.catatan FROM nilai_ekstra n JOIN ekstra e ON e.id=n.ekstra_id
        WHERE n.siswa_id=? AND n.ta=? AND n.semester=? ORDER BY e.urutan, e.nama', [$sid, $c['ta'], $c['semester']]);
    $hadir = one('SELECT * FROM kehadiran WHERE siswa_id=? AND ta=? AND semester=?', [$sid, $c['ta'], $c['semester']]);
    $catatanTeks = (one('SELECT teks FROM catatan_wali WHERE siswa_id=? AND ta=? AND semester=?', [$sid, $c['ta'], $c['semester']])['teks'] ?? '');

    $tuntas = $mapelList !== [];
    foreach ($mapelList as $m) {
        $naCek = nilai_akhir($sid, (int) $m['id'], $c['ta'], $c['semester']);
        $kkmCek = kkm_mapel((int) $m['id']);
        if ($naCek === null || $kkmCek === null || $naCek < $kkmCek) {
            $tuntas = false;
            break;
        }
    }
    $totalAbsen = (int) ($hadir['sakit'] ?? 0) + (int) ($hadir['izin'] ?? 0) + (int) ($hadir['tka'] ?? 0);
    $hadirOk = $totalAbsen <= 6;
    $perkembanganOk = $deskKok !== '' || trim($catatanTeks) !== '';
    if ($tuntas) {
        $keputusanTeks = $kelas >= 6
            ? 'Lulus Sekolah Dasar'
            : 'Naik Kelas ' . ($romawi[$kelas + 1] ?? ($kelas + 1)) . ' (' . ($angka[$kelas + 1] ?? ($kelas + 1)) . ')';
    } else {
        $keputusanTeks = $kelas >= 6 ? 'Tidak Lulus' : 'Tidak Naik Kelas';
    }
    $no = 0; ?>
<div class="raport-sheet">
  <div class="rp-title">Kartu Hasil Belajar Peserta Didik</div>
  <div class="rp-sub">Semester <?= $c['semester'] ?> &middot; Tahun Ajaran <?= e($c['ta']) ?></div>

  <table class="rp-ident">
    <tr>
      <td class="lbl">Nama Murid</td><td class="dd">:</td><td class="val"><b><?= e($s['nama']) ?></b></td>
      <td class="lbl lbl2">Kelas</td><td class="dd">:</td><td class="val"><?= e($romawi[$kelas] ?? $kelas) ?></td>
    </tr>
    <tr>
      <td class="lbl">NISN</td><td class="dd">:</td><td class="val"><?= e($s['nisn']) ?></td>
      <td class="lbl lbl2">Fase</td><td class="dd">:</td><td class="val"><?= e($c['fase']) ?></td>
    </tr>
    <tr>
      <td class="lbl">Sekolah</td><td class="dd">:</td><td class="val"><?= e($p['sekolah']) ?></td>
      <td class="lbl lbl2">Semester</td><td class="dd">:</td><td class="val"><?= $c['semester'] ?> (<?= $c['semester'] === 1 ? 'Ganjil' : 'Genap' ?>)</td>
    </tr>
    <tr>
      <td class="lbl">Alamat</td><td class="dd">:</td><td class="val"><?= e($s['alamat'] ?: $p['alamat']) ?></td>
      <td class="lbl lbl2">Tahun Ajaran</td><td class="dd">:</td><td class="val"><?= e($c['ta']) ?></td>
    </tr>
  </table>

  <table class="rp-tbl">
    <thead>
      <tr>
        <th style="width:42px">No</th>
        <th style="width:190px">Mata Pelajaran</th>
        <th style="width:80px">Nilai Akhir</th>
        <th>Capaian Kompetensi</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($mapelList as $m): $no++;
        $na = nilai_akhir($sid, (int) $m['id'], $c['ta'], $c['semester']);
        $cap = capaian_kompetensi($sid, (int) $m['id'], $c['ta'], $c['semester'], $c['fase']); ?>
      <tr>
        <td class="no"><?= $no ?></td>
        <td class="mapel"><?= e($m['nama']) ?><?php if ($na !== null): ?><div style="font-size:11px;font-weight:400">Predikat: <?= e(predikat($na)) ?></div><?php endif; ?></td>
        <td class="nilai"><?= $na !== null ? e(format_nilai($na)) : '-' ?></td>
        <td class="capai"><?= e($cap['tinggi']) ?><br><?= e($cap['rendah']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="rp-box rp-kok-desk">
    <div class="rp-box-title">Deskripsi Kokurikuler</div>
    <div class="rp-box-body"><?= $deskKok !== '' ? nl2br(e($deskKok)) : '' ?></div>
  </div>

  <?php if ($nilaiEkstra): ?>
  <div class="rp-section-title">Ekstrakurikuler</div>
  <table class="rp-tbl" style="max-width:100%">
    <thead><tr><th style="width:42px">No</th><th style="width:34%">Kegiatan</th><th style="width:110px">Predikat</th><th>Deskripsi</th></tr></thead>
    <tbody>
      <?php $ne = 0; foreach ($nilaiEkstra as $ek): $ne++; ?>
        <tr><td class="no"><?= $ne ?></td><td><?= e($ek['nama']) ?></td><td class="nilai"><?= e($ek['predikat']) ?></td><td><?= e($ek['catatan']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div class="rp-dua">
    <div class="rp-box">
      <div class="rp-box-title">Ketidakhadiran</div>
      <table class="rp-hadir">
        <tr><td>Sakit</td><td class="val"><?= (int) ($hadir['sakit'] ?? 0) ?> hari</td></tr>
        <tr><td>Izin</td><td class="val"><?= (int) ($hadir['izin'] ?? 0) ?> hari</td></tr>
        <tr><td>Tanpa Keterangan</td><td class="val"><?= (int) ($hadir['tka'] ?? 0) ?> hari</td></tr>
      </table>
    </div>
    <div class="rp-box">
      <div class="rp-box-title">Catatan Wali Kelas</div>
      <div class="rp-box-body"><?= $catatanTeks !== '' ? nl2br(e($catatanTeks)) : '' ?></div>
    </div>
  </div>

  <div class="rp-box rp-tanggapan">
    <div class="rp-box-title">Tanggapan Orang Tua/ Wali Murid</div>
    <div class="rp-box-body"></div>
  </div>

  <?php if ((int) $c['semester'] === 2): ?>
  <div class="rp-keputusan">
    <div class="rp-keputusan-title">KEPUTUSAN :</div>
    <div class="rp-keputusan-box">
      <p><b>Berdasarkan evaluasi pencapaian pembelajaran peserta didik telah dinyatakan :</b></p>
      <div class="rk-row"><span class="rk-label">1). Ketuntasan Tujuan Pembelajaran</span><span>:</span><b><?= $tuntas ? 'Tuntas' : 'Belum Tuntas' ?></b></div>
      <div class="rk-row"><span class="rk-label">2). Pemenuhan Kehadiran</span><span>:</span><b><?= $hadirOk ? 'Terpenuhi' : 'Tidak Terpenuhi' ?></b></div>
      <div class="rk-row"><span class="rk-label">3). Perkembangan, Psikologis &amp; Kognitif</span><span>:</span><b><?= $perkembanganOk ? 'Terpenuhi Baik' : 'Perlu Perhatian' ?></b></div>
      <p class="rk-maka"><b>Maka peserta didik dinyatakan :</b></p>
      <div class="rk-naik"><b><?= e($keputusanTeks) ?></b></div>
    </div>
  </div>
  <?php endif; ?>

  <div class="rp-place"><?= e($p['kota'] ?: '........................') ?>, <?= e(tgl_indo()) ?></div>
  <div class="rp-ttd">
    <div class="pos">
      <div class="role">Wali Kelas</div>
      <div class="who"><?= e($walikelas['nama'] ?: '( .......................... )') ?></div>
      <div class="nip">NIP. <?= e($walikelas['nip'] ?: '..........................') ?></div>
    </div>
    <div class="pos">
      <div class="role">Orang Tua / Wali</div>
      <div class="who">( <?= e($s['ortu'] ?? '') ?: '..........................' ?> )</div>
      <div class="nip"></div>
    </div>
    <div class="pos">
      <div class="role">Kepala Sekolah</div>
      <div class="who"><?= e($p['kepsek'] ?: '( .......................... )') ?></div>
      <div class="nip">NIP. <?= e($p['kepsek_nip'] ?: '..........................') ?></div>
    </div>
  </div>
</div>
<?php endforeach; ?>
