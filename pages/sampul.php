<?php
$c = ctx();
$p = profil();
$kelas = $c['kelas'];
$kaKunci = kelas_angkatan();
$kelasSiswa = $kaKunci ?? $kelas;

$semuaSiswa = all("SELECT * FROM siswa WHERE status='aktif' AND kelas=?" . scope_user() . " ORDER BY nama", [$kelasSiswa]);
$terpilih = [];
$pilihanBasi = false;
if (isset($_GET['s'])) {
    $ids = array_map('intval', (array) $_GET['s']);
    $terpilih = array_values(array_filter($semuaSiswa, fn($s) => in_array((int) $s['id'], $ids, true)));
    if (!$terpilih && $semuaSiswa !== []) {
        $pilihanBasi = true;
        $terpilih = $semuaSiswa;
    }
} else {
    $terpilih = $semuaSiswa;
}

$tglAman = function ($v): string {
    $v = trim((string) $v);
    if ($v === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        return tgl_indo($v);
    }
    return $v;
};

page_head('Cetak Sampul Raport', 'Konteks: Kelas ' . $kelas . ' - Fase ' . $c['fase'] . ' - Semester ' . $c['semester'] . ' - TA ' . $c['ta'],
    '<button class="btn" id="printBtn" type="button">Cetak / Simpan PDF</button>');
echo angkatan_lock_alert();
if ($pilihanBasi): ?>
  <div class="alert alert-info">Pilihan siswa sebelumnya tidak berlaku pada kelas ini — menampilkan seluruh siswa kelas <?= (int) $kelas ?>.</div>
<?php endif; ?>
?>
<div class="raport-tools no-print">
  <form method="get" action="index.php" class="card" style="margin:0;padding:12px 14px;width:100%">
    <input type="hidden" name="page" value="sampul">
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

<?php if (!$semuaSiswa): ?>
  <div class="card empty"><?= teks_kosong_siswa($kaKunci, (int) $kelas) ?></div>
<?php elseif (!$terpilih): ?>
  <div class="card empty">Tidak ada siswa dipilih.</div>
<?php endif; ?>

<?php foreach ($terpilih as $s):
    $nisNisn = trim($s['nis'] . '/' . $s['nisn'], '/');
    $ttl = [];
    if (trim($s['tempat_lahir']) !== '') {
        $ttl[] = trim($s['tempat_lahir']);
    }
    $tglLahir = $tglAman($s['tanggal_lahir']);
    if ($tglLahir !== '') {
        $ttl[] = $tglLahir;
    }
    $ttlTeks = $ttl ? implode(', ', $ttl) : '-';
    $jkTeks = $s['jk'] === 'P' ? 'Perempuan' : 'Laki-laki';

    $kotaCetak = trim($p['kota']);
    $tglBiodataProfil = $tglAman($p['tanggal_biodata'] ?? '');
    $tglMasuk = $tglBiodataProfil !== '' ? $tglBiodataProfil : $tglAman($s['tanggal_masuk'] ?? '');
    $tglCetak = $kotaCetak;
    if ($tglMasuk !== '') {
        $tglCetak = ($tglCetak !== '' ? $tglCetak . ', ' : '') . $tglMasuk;
    }

    $kontak = [];
    if (trim($p['website']) !== '') {
        $kontak[] = 'Website:' . trim($p['website']);
    }
    if (trim($p['email']) !== '') {
        $kontak[] = 'Email:' . trim($p['email']);
    }
    $kontakTeks = implode(', ', $kontak);

    $baris = function (string $label, string $nilai): string {
        $v = trim($nilai) !== '' ? $nilai : '-';
        return '<tr><td class="l">' . e($label) . '</td><td class="d">:</td><td class="v">' . e($v) . '</td></tr>';
    };
    $barisNo = function (string $no, string $label, string $nilai): string {
        $v = trim($nilai) !== '' ? $nilai : '-';
        return '<tr><td class="n">' . e($no) . '</td><td class="l">' . e($label) . '</td><td class="d">:</td><td class="v">' . e($v) . '</td></tr>';
    };
    $barisSub = function (string $label, string $nilai): string {
        $v = trim($nilai) !== '' ? $nilai : '-';
        return '<tr><td class="n"></td><td class="l sub">' . e($label) . '</td><td class="d">:</td><td class="v">' . e($v) . '</td></tr>';
    };
    ?>
<!-- HALAMAN 1 — SAMPUL -->
<div class="raport-sheet sp-cover">
  <div class="sp-top">
    <?php if (trim($p['logo']) !== ''): ?>
      <div class="sp-logo"><img src="data/<?= e($p['logo']) ?>" alt="Logo Sekolah"></div>
    <?php endif; ?>
    <div class="sp-title">RAPOR PESERTA DIDIK</div>
    <div class="sp-title">SEKOLAH DASAR</div>
    <div class="sp-title">(SD)</div>
  </div>

  <div class="sp-ident">
    <div class="sp-row">
      <span class="sp-lbl">Nama Peserta didik:</span>
      <span class="sp-val"><b><?= e($s['nama']) ?></b></span>
    </div>
    <div class="sp-row">
      <span class="sp-lbl">NIS/NISN:</span>
      <span class="sp-val"><?= e($nisNisn !== '' ? $nisNisn : '-') ?></span>
    </div>
  </div>

  <div class="sp-foot">
    <div class="sp-kem">KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RI</div>
    <div class="sp-school"><?= e(mb_strtoupper($p['sekolah'] !== '' ? $p['sekolah'] : 'Sekolah Dasar')) ?></div>
    <div><?= e($p['alamat']) ?></div>
    <?php if ($kontakTeks !== ''): ?><div><?= e($kontakTeks) ?></div><?php endif; ?>
    <div class="sp-city"><?= e($kotaCetak) ?></div>
  </div>
</div>

<!-- HALAMAN 2 — IDENTITAS SEKOLAH -->
<div class="raport-sheet">
  <div class="sp-title">RAPOR PESERTA DIDIK</div>
  <div class="sp-title">SEKOLAH DASAR</div>
  <table class="sp-table sp-sekolah">
    <?= $baris('Nama Sekolah', $p['sekolah']) ?>
    <?= $baris('NPSN', $p['npsn']) ?>
    <?= $baris('NSS', '') ?>
    <?= $baris('Alamat Sekolah', $p['alamat']) ?>
    <?= $baris('No. Telepon', $p['telp']) ?>
    <?= $baris('Desa/Kelurahan', '') ?>
    <?= $baris('Kecamatan', '') ?>
    <?= $baris('Kabupaten/Kota', $p['kota']) ?>
    <?= $baris('Provinsi', '') ?>
    <?= $baris('Website', $p['website']) ?>
    <?= $baris('Email', $p['email']) ?>
  </table>
</div>

<!-- HALAMAN 3 — IDENTITAS PESERTA DIDIK -->
<div class="raport-sheet">
  <div class="sp-title">IDENTITAS PESERTA DIDIK</div>
  <table class="sp-table">
    <?= $barisNo('1.', 'Nama Peserta Didik', $s['nama']) ?>
    <?= $barisNo('2.', 'NIS/NISN', $nisNisn) ?>
    <?= $barisNo('3.', 'Tempat, Tanggal Lahir', $ttlTeks) ?>
    <?= $barisNo('4.', 'Jenis Kelamin', $jkTeks) ?>
    <?= $barisNo('5.', 'Agama', $s['agama']) ?>
    <?= $barisNo('6.', 'Pendidikan Sebelumnya', $s['pendidikan_sebelumnya']) ?>
    <?= $barisNo('7.', 'Alamat Peserta Didik', $s['alamat']) ?>
    <?= $barisNo('8.', 'Nama Orang Tua', '') ?>
    <?= $barisSub('a. Ayah', $s['ayah']) ?>
    <?= $barisSub('b. Ibu', $s['ibu']) ?>
    <?= $barisNo('9.', 'Pekerjaan Orang Tua', '') ?>
    <?= $barisSub('a. Ayah', $s['kerja_ayah']) ?>
    <?= $barisSub('b. Ibu', $s['kerja_ibu']) ?>
    <?= $barisNo('10.', 'Alamat Orang Tua', '') ?>
    <?= $barisSub('Jalan', $s['ortu_jalan']) ?>
    <?= $barisSub('Kelurahan/Desa', $s['ortu_kelurahan']) ?>
    <?= $barisSub('Kecamatan', $s['ortu_kecamatan']) ?>
    <?= $barisSub('Kabupaten/Kota', $s['ortu_kota']) ?>
    <?= $barisSub('Provinsi', $s['ortu_provinsi']) ?>
    <?= $barisNo('11.', 'Wali Peserta Didik', '') ?>
    <?= $barisSub('Nama', $s['wali_nama']) ?>
    <?= $barisSub('Pekerjaan', $s['wali_kerja']) ?>
    <?= $barisSub('Alamat', $s['wali_alamat']) ?>
  </table>

  <div class="sp-bawah">
    <div class="sp-foto"></div>
    <div class="sp-ttd">
      <div class="sp-tgl"><?= e($tglCetak) ?></div>
      <div class="sp-kepsek">Kepala <?= e($p['sekolah'] !== '' ? $p['sekolah'] : 'Sekolah') ?></div>
      <div class="sp-sign"></div>
      <div class="sp-nama"><?= e($p['kepsek']) ?></div>
      <div class="sp-nip">NBM: <?= e($p['kepsek_nip']) ?></div>
    </div>
  </div>
</div>
<?php endforeach; ?>
