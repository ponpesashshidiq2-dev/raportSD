<?php
$c = ctx();
$kaKunci = kelas_angkatan();
$siswas = all("SELECT id, nama, nis, kelas FROM siswa WHERE " . scope_status((int) $c['kelas']) . scope_user() . " ORDER BY kelas, nama");
$mapelList = mapel_kelas($c['kelas']);

$nilaiMap = [];
$rataSiswa = [];
foreach ($siswas as $s) {
    $sid = (int) $s['id'];
    $sum = 0.0;
    $cnt = 0;
    $row = [];
    foreach ($mapelList as $m) {
        $mid = (int) $m['id'];
        $na = nilai_akhir($sid, $mid, $c['ta'], $c['semester']);
        $row[$mid] = $na;
        if ($na !== null) {
            $sum += $na;
            $cnt++;
        }
    }
    $nilaiMap[$sid] = $row;
    $rataSiswa[$sid] = $cnt > 0 ? round($sum / $cnt, 2) : null;
}

$urut = [];
foreach ($siswas as $s) {
    $sid = (int) $s['id'];
    if ($rataSiswa[$sid] !== null) {
        $urut[] = ['id' => $sid, 'rata' => $rataSiswa[$sid], 'nama' => (string) $s['nama']];
    }
}
usort($urut, fn($a, $b) => $b['rata'] <=> $a['rata'] ?: strcmp($a['nama'], $b['nama']));
$rank = [];
$prevVal = null;
$prevRank = 0;
foreach ($urut as $i => $u) {
    $r = ($u['rata'] === $prevVal) ? $prevRank : $i + 1;
    $rank[$u['id']] = $r;
    $prevVal = $u['rata'];
    $prevRank = $r;
}

$rataMapel = [];
$kkmMap = [];
foreach ($mapelList as $m) {
    $mid = (int) $m['id'];
    $kkmMap[$mid] = kkm_mapel($mid);
    $sum = 0.0;
    $cnt = 0;
    foreach ($nilaiMap as $row) {
        if (($row[$mid] ?? null) !== null) {
            $sum += $row[$mid];
            $cnt++;
        }
    }
    $rataMapel[$mid] = $cnt > 0 ? round($sum / $cnt, 2) : null;
}

$rataUmum = null;
if ($rataSiswa) {
    $vals = array_values(array_filter($rataSiswa, fn($v) => $v !== null));
    if ($vals) {
        $rataUmum = round(array_sum($vals) / count($vals), 2);
    }
}

$fmt = static function ($n, int $d = 1): string {
    $s = number_format((float) $n, $d, ',', '');
    if (str_contains($s, ',')) {
        $s = rtrim(rtrim($s, '0'), ',');
    }
    return $s;
};

page_head('Leger Nilai', 'Rekap nilai akhir seluruh mata pelajaran per siswa beserta rata-rata dan ranking pada konteks aktif.',
    '<button class="btn js-print" type="button">Cetak / Simpan PDF</button>');
echo context_bar();
?>
<style>
@page{size:A4 landscape;margin:8mm}
table.tbl-leger{table-layout:fixed;width:100%;min-width:960px}
table.tbl-leger th{overflow-wrap:anywhere;word-break:break-word;line-height:1.35}
table.tbl-leger td{overflow-wrap:break-word}
.leger-head{text-align:center;margin:0 0 8px}
.leger-title{font-size:15px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:#2a2008}
.leger-school{font-size:13px;font-weight:600;color:#7a6b45;margin-top:2px}
@media print{
  .leger-head{margin:0 0 6px}
  .leger-title{font-size:14px}
  .leger-school{font-size:11.5px}
  table.tbl-leger{min-width:0;font-size:9.5px}
  table.tbl-leger th,table.tbl-leger td{padding:3px 4px}
  table.tbl-leger thead th{font-size:8.5px;letter-spacing:.2px}
  table.tbl-leger thead{display:table-header-group}
  table.tbl-leger td[data-frozen],table.tbl-leger th[data-frozen]{position:static!important;box-shadow:none!important}
}
</style>
<?php if (!$siswas): ?>
  <div class="card empty"><?= teks_kosong_siswa($kaKunci, (int) $c['kelas']) ?></div>
<?php elseif (!$mapelList): ?>
  <div class="card empty">Belum ada mata pelajaran untuk kelas <?= (int) $c['kelas'] ?>. Tambahkan pada menu <a href="index.php?page=mapel">Mata Pelajaran</a>.</div>
<?php else:
$namaSekolahLeger = trim((string) (profil()['sekolah'] ?? ''));
?>
<div class="leger-head">
  <div class="leger-title">LEGER NILAI KELAS <?= (int) $c['kelas'] ?></div>
  <?php if ($namaSekolahLeger !== ''): ?><div class="leger-school"><?= e($namaSekolahLeger) ?></div><?php endif; ?>
</div>
<div class="table-wrap">
  <table class="tbl tbl-leger">
    <caption>Nilai akhir = rata-rata nilai sumatif (rata Lingkup Materi &amp; rata AES) &middot; <?= e($c['ta']) ?> Semester <?= (int) $c['semester'] ?> &middot; sel di bawah KKM ditandai merah</caption>
    <thead>
      <tr>
        <th class="center" style="width:3.5%" data-frozen="no">No</th>
        <th data-frozen="nama" style="width:17%">Nama</th>
        <?php foreach ($mapelList as $m): $mid = (int) $m['id']; ?>
        <th class="center"><?= e($m['nama']) ?><div class="hint">KKM <?= $kkmMap[$mid] !== null ? (int) $kkmMap[$mid] : '-' ?></div></th>
        <?php endforeach; ?>
        <th class="center" style="width:6%">Rata-rata</th>
        <th class="center" style="width:5%">Ranking</th>
      </tr>
    </thead>
    <tbody>
      <?php $no = 0; foreach ($siswas as $s): $no++; $sid = (int) $s['id']; ?>
      <tr>
        <td class="center" data-frozen="no"><?= $no ?></td>
        <td class="name-cell" data-frozen="nama"><?= e($s['nama']) ?><div class="hint">Kelas <?= (int) $s['kelas'] ?> &middot; <?= e($s['nis']) ?></div></td>
        <?php foreach ($mapelList as $m): $mid = (int) $m['id']; $na = $nilaiMap[$sid][$mid] ?? null; $kkm = $kkmMap[$mid]; ?>
        <td class="center<?= $na !== null && $kkm !== null && $na < $kkm ? ' nilai-red' : '' ?>"><?= $na !== null ? $fmt($na) : '-' ?></td>
        <?php endforeach; ?>
        <td class="center cell-rata"><?= $rataSiswa[$sid] !== null ? $fmt($rataSiswa[$sid], 2) : '-' ?></td>
        <td class="center cell-rank"><b><?= $rank[$sid] ?? '-' ?></b></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td class="center"></td>
        <td><b>Rata-rata kelas</b></td>
        <?php foreach ($mapelList as $m): $mid = (int) $m['id']; ?>
        <td class="center"><?= $rataMapel[$mid] !== null ? $fmt($rataMapel[$mid], 2) : '-' ?></td>
        <?php endforeach; ?>
        <td class="center"><?= $rataUmum !== null ? '<b>' . $fmt($rataUmum, 2) . '</b>' : '-' ?></td>
        <td class="center"></td>
      </tr>
    </tfoot>
  </table>
</div>
<?php endif; ?>
