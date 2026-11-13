<?php
$c = ctx();
$p = profil();
$kelas = $c['kelas'];

$totalSiswa = (int) scalar("SELECT COUNT(*) FROM siswa WHERE status='aktif'");
$siswaKelas = (int) scalar("SELECT COUNT(*) FROM siswa WHERE status='aktif' AND kelas=?", [$kelas]);
$mapelRaport = mapel_raport($kelas);
$tpCount = (int) scalar('SELECT COUNT(*) FROM tp WHERE fase=?', [$c['fase']]);
$ekstraCount = (int) scalar('SELECT COUNT(*) FROM ekstra');

$belum = [];
$totalTugas = count($mapelRaport);
foreach (all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=? ORDER BY nama", [$kelas]) as $s) {
    $isi = 0;
    foreach ($mapelRaport as $m) {
        if (nilai_akhir((int) $s['id'], (int) $m['id'], $c['ta'], $c['semester']) !== null) {
            $isi++;
        }
    }
    if ($totalTugas > 0 && $isi < $totalTugas) {
        $belum[] = ['nama' => $s['nama'], 'kurang' => $totalTugas - $isi, 'total' => $totalTugas];
    }
}
$lengkap = $siswaKelas - count($belum);
$pct = $siswaKelas > 0 ? round($lengkap / $siswaKelas * 100) : 0;

page_head('Dashboard', 'Ringkasan data dan progres penilaian raport.');
echo context_bar();
?>
<div class="grid grid-4 mb">
  <div class="stat">
    <div class="stat-k">Siswa Aktif</div>
    <div class="stat-v"><?= $totalSiswa ?></div>
    <div class="stat-s">Kelas <?= $kelas ?>: <?= $siswaKelas ?> siswa</div>
  </div>
  <div class="stat blue">
    <div class="stat-k">Mapel di Raport</div>
    <div class="stat-v"><?= count($mapelRaport) ?></div>
    <div class="stat-s">Mapel dengan KKM terisi</div>
  </div>
  <div class="stat warn">
    <div class="stat-k">Tujuan Pembelajaran</div>
    <div class="stat-v"><?= $tpCount ?></div>
    <div class="stat-s">Untuk <?= e($c['fase']) ?></div>
  </div>
  <div class="stat <?= $pct < 100 ? 'danger' : '' ?>">
    <div class="stat-k">Progres Nilai</div>
    <div class="stat-v"><?= $pct ?>%</div>
    <div class="stat-s"><?= $lengkap ?> dari <?= $siswaKelas ?> siswa lengkap</div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <h3>Nilai Belum Lengkap - Kelas <?= $kelas ?></h3>
    <?php if ($totalTugas === 0): ?>
      <div class="alert alert-warning">Belum ada mata pelajaran dengan KKM. Isi KKM pada menu <a href="index.php?page=mapel">Mata Pelajaran</a> agar mapel tampil di raport.</div>
    <?php elseif (!$belum): ?>
      <div class="alert alert-success">Seluruh siswa kelas <?= $kelas ?> sudah memiliki nilai akhir semua mapel.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="tbl">
          <thead><tr><th>Nama Siswa</th><th class="center">Kurang</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($belum as $b): ?>
            <tr>
              <td class="name-cell"><?= e($b['nama']) ?></td>
              <td class="center"><span class="badge badge-red"><?= $b['kurang'] ?> mapel</span></td>
              <td class="right"><a class="btn sm secondary" href="index.php?page=nilai_formatif">Input</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Akses Cepat</h3>
    <div class="grid grid-3">
      <a class="btn secondary" href="index.php?page=siswa">Data Siswa</a>
      <a class="btn secondary" href="index.php?page=nilai_formatif">Nilai Formatif</a>
      <a class="btn secondary" href="index.php?page=nilai_sumatif">Nilai Sumatif</a>
      <a class="btn secondary" href="index.php?page=kehadiran">Kehadiran</a>
      <a class="btn secondary" href="index.php?page=mapel">Monitoring Nilai</a>
      <a class="btn secondary" href="index.php?page=cetak">Cetak Raport</a>
    </div>
    <h3 class="mt">Profil Singkat</h3>
    <table class="tbl">
      <tbody>
        <tr><td>Sekolah</td><td class="right"><b><?= e($p['sekolah']) ?></b></td></tr>
        <tr><td>NPSN</td><td class="right"><b><?= e($p['npsn']) ?></b></td></tr>
        <tr><td>Kepala Sekolah</td><td class="right"><b><?= e($p['kepsek']) ?></b></td></tr>
        <tr><td>Ekstrakurikuler</td><td class="right"><b><?= $ekstraCount ?> kegiatan</b></td></tr>
      </tbody>
    </table>
  </div>
</div>
