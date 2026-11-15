<?php
$c = ctx();
$p = profil();
$kelas = $c['kelas'];

$totalSiswa = (int) scalar("SELECT COUNT(*) FROM siswa WHERE status='aktif'" . scope_user());
$siswaKelas = (int) scalar("SELECT COUNT(*) FROM siswa WHERE status='aktif' AND kelas=?" . scope_user(), [$kelas]);
$mapelRaport = mapel_raport($kelas);
    $tpCount = (int) scalar('SELECT COUNT(*) FROM tp WHERE kelas=? AND semester=?', [$c['kelas'], $c['semester']]);
$ekstraCount = (int) scalar('SELECT COUNT(*) FROM ekstra WHERE 1=1' . scope_user());
$kokCount = (int) scalar('SELECT COUNT(*) FROM kokurikuler_kegiatan');

$belum = [];
$totalTugas = count($mapelRaport);
foreach (all("SELECT id, nama, nisn FROM siswa WHERE status='aktif' AND kelas=?" . scope_user() . " ORDER BY nama", [$kelas]) as $s) {
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
    <div class="stat-s">Kelas <?= $kelas ?> - Semester <?= $c['semester'] ?></div>
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
        <tr><td>Kokurikuler</td><td class="right"><b><?= $kokCount ?> kegiatan</b></td></tr>
        <tr><td>Ekstrakurikuler</td><td class="right"><b><?= $ekstraCount ?> kegiatan</b></td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="card mt">
  <h3>Semua Kelas — Data Tetap Tersimpan &amp; Bisa Dibuka Kembali</h3>
  <div class="hint mb">Ganti kelas kapan saja lewat dropdown <b>Kelas</b> di bar atas (atau menu Profil). Data kelas lain tidak pernah terhapus — klik <b>Buka</b> untuk melihat, menginput, atau mencetak raport kelas tersebut.</div>
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th class="center" style="width:70px">Kelas</th>
          <th class="center" style="width:70px">Fase</th>
          <th class="center">Siswa</th>
          <th class="center">Nilai Formatif</th>
          <th class="center">Nilai Sumatif</th>
          <th>Wali Kelas (Sem <?= (int) $c['semester'] ?>)</th>
          <th style="width:130px">Status</th>
          <th style="width:110px"></th>
        </tr>
      </thead>
      <tbody>
      <?php for ($k = 1; $k <= 6; $k++):
          $sk = (int) scalar("SELECT COUNT(*) FROM siswa WHERE status='aktif' AND kelas=?" . scope_user(), [$k]);
          $nf = (int) scalar('SELECT COUNT(*) FROM nilai_formatif nf JOIN siswa s ON s.id = nf.siswa_id WHERE nf.kelas=?' . scope_user('s.user_id'), [$k]);
          $ns = (int) scalar('SELECT COUNT(*) FROM nilai_sumatif ns JOIN sumatif_jenis sj ON sj.id = ns.jenis_id WHERE ns.kelas=?' . scope_user('sj.user_id'), [$k]);
          $wk = walikelas($k, (int) $c['semester']);
          $aktif = $k === $kelas; ?>
        <tr<?= $aktif ? ' style="background:var(--brand-soft)"' : '' ?>>
          <td class="center"><b><?= $k ?></b></td>
          <td class="center"><?= e(fase_of($k)) ?></td>
          <td class="center"><?= $sk ?></td>
          <td class="center"><?= $nf ?></td>
          <td class="center"><?= $ns ?></td>
          <td><?= e($wk['nama'] ?: '-') ?></td>
          <td><?= $aktif ? '<span class="badge badge-blue">Konteks Aktif</span>' : '<span class="badge badge-gray">Tersimpan</span>' ?></td>
          <td class="right">
            <?php if ($aktif): ?>
              <a class="btn sm secondary" href="index.php?page=cetak">Cetak</a>
            <?php else: ?>
              <form method="post" action="index.php?page=dashboard" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="switch_kelas">
                <input type="hidden" name="kembali" value="dashboard">
                <input type="hidden" name="kelas" value="<?= $k ?>">
                <button class="btn sm" type="submit">Buka</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endfor; ?>
      </tbody>
    </table>
  </div>
</div>
