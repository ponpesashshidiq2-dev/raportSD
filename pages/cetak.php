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
    $nilaiEkstra = all('SELECT e.nama, n.predikat FROM nilai_ekstra n JOIN ekstra e ON e.id=n.ekstra_id
        WHERE n.siswa_id=? AND n.ta=? AND n.semester=? ORDER BY e.urutan, e.nama', [$sid, $c['ta'], $c['semester']]);
    $hadir = one('SELECT * FROM kehadiran WHERE siswa_id=? AND ta=? AND semester=?', [$sid, $c['ta'], $c['semester']]);
    $no = 0; ?>
<div class="raport-sheet">
  <div class="rp-head">
    <div class="rp-school"><?= e($p['sekolah']) ?></div>
    <div class="rp-addr"><?= e($p['alamat']) ?></div>
    <div class="rp-addr">
      <?php $info = [];
      if ($p['npsn']) $info[] = 'NPSN: ' . $p['npsn'];
      if ($p['telp']) $info[] = 'Telp: ' . $p['telp'];
      if ($p['email']) $info[] = 'Email: ' . $p['email'];
      echo e(implode(' | ', $info)); ?>
    </div>
  </div>

  <div class="rp-title">Kartu Hasil Belajar Peserta Didik</div>
  <div class="rp-sub">Semester <?= $c['semester'] ?> &middot; Tahun Ajaran <?= e($c['ta']) ?></div>

  <table class="rp-ident">
    <tr><td class="lbl">Nama Peserta Didik</td><td class="dd">:</td><td class="val"><b><?= e($s['nama']) ?></b></td></tr>
    <tr><td class="lbl">NISN</td><td class="dd">:</td><td class="val"><?= e($s['nisn']) ?></td></tr>
    <tr><td class="lbl">Sekolah</td><td class="dd">:</td><td class="val"><?= e($p['sekolah']) ?></td></tr>
    <tr><td class="lbl">Alamat</td><td class="dd">:</td><td class="val"><?= e($s['alamat'] ?: $p['alamat']) ?></td></tr>
    <tr><td class="lbl">Kelas</td><td class="dd">:</td><td class="val">Kelas <?= $kelas ?></td></tr>
    <tr><td class="lbl">Fase</td><td class="dd">:</td><td class="val"><?= e($c['fase']) ?></td></tr>
    <tr><td class="lbl">Semester</td><td class="dd">:</td><td class="val"><?= $c['semester'] ?> (<?= $c['semester'] === 1 ? 'Ganjil' : 'Genap' ?>)</td></tr>
    <tr><td class="lbl">Tahun Ajaran</td><td class="dd">:</td><td class="val"><?= e($c['ta']) ?></td></tr>
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
        $cap = capaian_kompetensi($sid, (int) $m['id'], $c['ta'], $c['semester'], $c['fase']);
        $kkm = $m['kkm'] !== null && $m['kkm'] !== '' ? (int) $m['kkm'] : null; ?>
      <tr>
        <td class="no"><?= $no ?></td>
        <td class="mapel"><?= e($m['nama']) ?><?php if ($kkm !== null): ?><div style="font-size:11px;font-weight:400">KKM <?= $kkm ?> &middot; <?= e(predikat($na)) ?></div><?php endif; ?></td>
        <td class="nilai"><?= $na !== null ? e(format_nilai($na)) : '-' ?></td>
        <td class="capai"><?= e($cap['tinggi']) ?><br><?= e($cap['rendah']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($nilaiEkstra): ?>
    <div class="rp-section-title">Ekstrakurikuler</div>
    <table class="rp-tbl" style="max-width:560px">
      <thead><tr><th style="width:42px">No</th><th>Kegiatan</th><th style="width:140px">Predikat</th></tr></thead>
      <tbody>
      <?php $ne = 0; foreach ($nilaiEkstra as $ek): $ne++; ?>
        <tr><td class="no"><?= $ne ?></td><td><?= e($ek['nama']) ?></td><td class="nilai"><?= e($ek['predikat']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="rp-section-title">Ketidakhadiran</div>
  <table class="rp-kehadiran">
    <thead>
      <tr><th rowspan="2" style="width:70px">No</th><th colspan="3">Ketidakhadiran (hari)</th></tr>
      <tr><th>Sakit</th><th>Izin</th><th>Tanpa Keterangan</th></tr>
    </thead>
    <tbody>
      <tr>
        <td class="no">1</td>
        <td><?= (int) ($hadir['sakit'] ?? 0) ?></td>
        <td><?= (int) ($hadir['izin'] ?? 0) ?></td>
        <td><?= (int) ($hadir['tka'] ?? 0) ?></td>
      </tr>
    </tbody>
  </table>

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
