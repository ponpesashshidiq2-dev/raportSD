<?php
$c = ctx();

function daftar_agama(): array
{
    return [
        '01' => 'Islam',
        '02' => 'Kristen Protestan',
        '03' => 'Kristen Katolik',
        '04' => 'Hindu',
        '05' => 'Buddha',
        '06' => 'Khonghucu',
        '07' => 'Kepercayaan terhadap Tuhan YME',
        '08' => 'Lainnya',
    ];
}

function daftar_pendidikan_sebelumnya(): array
{
    return ['TK', 'RA', 'PAUD', 'Kelompok Bermain', 'Taman Kanak-kanak', 'Nonformal', 'Lainnya'];
}

function import_header_map(array $header): array
{
    $norm = [];
    foreach ($header as $i => $h) {
        $norm[preg_replace('/[^a-z0-9]/', '', mb_strtolower((string) $h))] = $i;
    }
    $find = function (array $keys) use ($norm) {
        foreach ($keys as $k) {
            if (isset($norm[$k])) {
                return $norm[$k];
            }
        }
        return null;
    };
    return [
        'nis' => $find(['nis', 'nomorinduksiswa']),
        'nisn' => $find(['nisn', 'nomorinduksiswanasional', 'nomorpesertadidiknasional', 'nisnnisnisn']),
        'nis_n' => $find(['nisnnisnis']),
        'nisn_nis' => $find(['nisnnis']),
        'nama' => $find(['nama', 'namasiswa', 'namapesertadidik', 'namalengkap']),
        'jk' => $find(['jk', 'jeniskelamin', 'kelamin', 'sex']),
        'tempat_lahir' => $find(['tempatlahir', 'tempat']),
        'tanggal_lahir' => $find(['tanggallahir', 'tglahir', 'lahir']),
        'tanggal_masuk' => $find(['tanggalmasuk', 'tglmasuk', 'tanggalmasuksiswa', 'masuksiswa']),
        'tempat_tanggal' => $find(['tempattanggallahir']),
        'alamat' => $find(['alamat', 'alamatlengkap', 'alamatpesertadidik']),
        'kode_agama' => $find(['kodeagama']),
        'agama' => $find(['agama', 'namaagama']),
        'pendidikan' => $find(['pendidikansebelumnya', 'asalsekolah', 'tkasebelumnya', 'pendidikan']),
        'ayah' => $find(['ayah', 'namaayah', 'namaortuayah', 'namaorangtuaayah', 'ortuayah', 'orangtuaayah', 'namaortu', 'namaorangtua', 'ortu']),
        'ibu' => $find(['ibu', 'namaibu', 'namaortuibu', 'namaorangtuaibu', 'ortuibu', 'orangtuaibu']),
        'kerja_ayah' => $find(['kerjaayah', 'pekerjaanayah', 'pekerjaanortuayah', 'pekerjaanorangtuaayah']),
        'kerja_ibu' => $find(['kerjaibu', 'pekerjaanibu', 'pekerjaanortuibu', 'pekerjaanorangtuaibu']),
        'ortu_jalan' => $find(['ortujalan', 'alamatortuajalankampungrtrw', 'alamatorangtuajalankampungrtrw', 'alamatortuajalan', 'alamatorangtuajalan', 'alamatortujalan', 'alamatjangankampungrtrw', 'jalankampungrtrw', 'alamatjalan', 'alamatortua', 'alamatorangtua']),
        'ortu_kelurahan' => $find(['ortukelurahan', 'alamatortuakelurahan', 'alamatorangtuakelurahan', 'kelurahan', 'desa']),
        'ortu_kecamatan' => $find(['ortukecamatan', 'alamatortuakecamatan', 'alamatorangtuakecamatan', 'kecamatan']),
        'ortu_kota' => $find(['ortukota', 'alamatortuakotakabupaten', 'alamatorangtuakotakabupaten', 'kotakabupaten', 'kotakab', 'kota']),
        'ortu_provinsi' => $find(['ortuprovinsi', 'alamatortuaprovinsi', 'alamatorangtuaprovinsi', 'provinsi']),
        'wali_nama' => $find(['walinama', 'walipesertadidiknama', 'namawali', 'wali']),
        'wali_kerja' => $find(['walipekerjaan', 'walipesertadidikpekerjaan', 'pekerjaanwali', 'walikerja']),
        'wali_alamat' => $find(['walialamat', 'walipesertadidikalamat', 'alamatwali']),
        'telp' => $find(['telp', 'telpon', 'telepon', 'nohp', 'nomorhp', 'handphone']),
        'kelas' => $find(['kelas', 'rombel', 'kelaswali']),
        'rombel' => $find(['rombel', 'kelompok']),
    ];
}

function import_merge_header(array $rows): array
{
    if (count($rows) < 2) {
        return $rows;
    }
    $r0 = $rows[0];
    $r1 = $rows[1];
    $max = max(count($r0), count($r1));
    $subCount = 0;
    $hasGap = false;
    for ($i = 0; $i < $max; $i++) {
        $a = trim((string) ($r0[$i] ?? ''));
        $b = trim((string) ($r1[$i] ?? ''));
        if ($b === '') {
            continue;
        }
        if ($a === '') {
            $hasGap = true;
        }
        if (!preg_match('/^(no|nomor|nama|nis|nisn|tempat|tanggal|jeniskelamin|kode|agama|pendidikan|alamat|ayah|ibu|pekerjaan|jalan|kampung|rt|rw|kelurahan|kecamatan|kota|kabupaten|provinsi|wali|pesertadidik|ortu|orangtua|telp|telepon|hp|kelas|rombel)/iu', $b)) {
            return $rows;
        }
        $subCount++;
    }
    if ($subCount === 0 || !$hasGap) {
        return $rows;
    }
    $header = [];
    $group = '';
    for ($i = 0; $i < $max; $i++) {
        $a = trim((string) ($r0[$i] ?? ''));
        $b = trim((string) ($r1[$i] ?? ''));
        if ($a !== '') {
            $group = $a;
        }
        if ($b !== '') {
            $header[$i] = $a !== '' ? ($a . ' ' . $b) : ($group !== '' ? ($group . ' ' . $b) : $b);
        } else {
            $header[$i] = $a;
        }
    }
    $out = [$header];
    foreach (array_slice($rows, 2) as $r) {
        $out[] = $r;
    }
    return $out;
}

function import_tanggal($v): string
{
    $v = trim((string) $v);
    if ($v === '') {
        return '';
    }
    if (preg_match('#^(\d{4})-(\d{2})-(\d{2})#', $v, $m)) {
        return $m[0];
    }
    if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})#', $v, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }
    if (is_numeric($v) && $v > 20000 && $v < 80000) {
        $d = new DateTime('1899-12-30');
        $d->modify('+' . (int) $v . ' days');
        return $d->format('Y-m-d');
    }
    return '';
}

function siswa_import_rows(array $rows, string $mode, ?int $kelasPaksa = null): array
{
    $stats = ['total' => 0, 'baru' => 0, 'ubah' => 0, 'lewati' => 0, 'pesan' => []];
    if (count($rows) < 2) {
        $stats['pesan'][] = 'File tidak berisi data (minimal baris header + 1 baris data).';
        return $stats;
    }
    $rows = import_merge_header($rows);
    $map = import_header_map($rows[0]);
    if ($map['nama'] === null) {
        $stats['pesan'][] = 'Kolom "Nama" tidak ditemukan pada header file.';
        return $stats;
    }
    db()->beginTransaction();
    try {
        foreach (array_slice($rows, 1) as $r) {
            $get = fn($k) => $map[$k] !== null && isset($r[$map[$k]]) ? trim((string) $r[$map[$k]]) : '';
            $nama = $get('nama');
            if ($nama === '') {
                continue;
            }
            $stats['total']++;
            $nisn = $get('nisn');
            $nis = $get('nis');
            if ($nis === '') {
                $nis = $get('nis_n');
            }
            if ($nisn === '' && $nis === '') {
                $nisn = $get('nisn_nis');
            }
            $kelas = (int) $get('kelas');
            if ($kelasPaksa !== null) {
                $kelas = $kelasPaksa;
            } elseif ($kelas < 1 || $kelas > 6) {
                $kelas = ctx()['kelas'];
            }
            $jk = strtoupper(substr($get('jk'), 0, 1));

            $tempat = $get('tempat_lahir');
            $tanggal = import_tanggal($get('tanggal_lahir'));
            if ($tempat === '' && $tanggal === '') {
                $combo = $get('tempat_tanggal');
                if ($combo !== '') {
                    $pos = strrpos($combo, ',');
                    if ($pos !== false) {
                        $tempat = trim(substr($combo, 0, $pos));
                        $tanggal = import_tanggal(substr($combo, $pos + 1));
                    } else {
                        $tanggal = import_tanggal($combo);
                    }
                }
            }

            $kodeAgama = $get('kode_agama');
            $agama = $get('agama');
            $agamaList = daftar_agama();
            if ($kodeAgama === '' && $agama !== '') {
                foreach ($agamaList as $k => $nm) {
                    if (mb_strtolower($nm) === mb_strtolower($agama)) {
                        $kodeAgama = $k;
                        break;
                    }
                }
            }
            if ($agama === '' && $kodeAgama !== '') {
                $agama = $agamaList[str_pad($kodeAgama, 2, '0', STR_PAD_LEFT)] ?? '';
            }

            $ayah = $get('ayah');
            $ibu = $get('ibu');
            $waliNama = $get('wali_nama');
            $data = [
                'nis' => $nis,
                'nisn' => $nisn,
                'nama' => $nama,
                'jk' => in_array($jk, ['P', 'W'], true) ? 'P' : 'L',
                'tempat_lahir' => $tempat,
                'tanggal_lahir' => $tanggal,
                'tanggal_masuk' => import_tanggal($get('tanggal_masuk')),
                'alamat' => $get('alamat'),
                'ortu' => $ayah !== '' ? $ayah : ($waliNama !== '' ? $waliNama : $ibu),
                'telp' => $get('telp'),
                'kelas' => $kelas,
                'rombel' => $get('rombel') ?: ('Kelas ' . $kelas),
                'status' => 'aktif',
                'kode_agama' => $kodeAgama,
                'agama' => $agama,
                'pendidikan_sebelumnya' => $get('pendidikan'),
                'ayah' => $ayah,
                'ibu' => $ibu,
                'kerja_ayah' => $get('kerja_ayah'),
                'kerja_ibu' => $get('kerja_ibu'),
                'ortu_jalan' => $get('ortu_jalan'),
                'ortu_kelurahan' => $get('ortu_kelurahan'),
                'ortu_kecamatan' => $get('ortu_kecamatan'),
                'ortu_kota' => $get('ortu_kota'),
                'ortu_provinsi' => $get('ortu_provinsi'),
                'wali_nama' => $get('wali_nama'),
                'wali_kerja' => $get('wali_kerja'),
                'wali_alamat' => $get('wali_alamat'),
            ];
            $existing = null;
            if ($nisn !== '') {
                $existing = one('SELECT id FROM siswa WHERE nisn=?' . scope_user(), [$nisn]);
            }
            if (!$existing && $nis !== '') {
                $existing = one('SELECT id FROM siswa WHERE nis=?' . scope_user(), [$nis]);
            }
            if (!$existing) {
                $existing = one('SELECT id FROM siswa WHERE LOWER(nama)=? AND kelas=?' . scope_user(), [mb_strtolower($nama), $kelas]);
            }
            if ($existing) {
                if ($mode === 'skip') {
                    $stats['lewati']++;
                    continue;
                }
                update_row('siswa', $data, 'id=?', [$existing['id']]);
                $stats['ubah']++;
            } else {
                insert_row('siswa', $data + ['user_id' => (int) (user()['id'] ?? 0)]);
                $stats['baru']++;
            }
        }
        db()->commit();
    } catch (Throwable $ex) {
        db()->rollBack();
        $stats['pesan'][] = 'Gagal mengimpor: ' . $ex->getMessage();
    }
    return $stats;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'angkatan' && user()) {
        $a = (int) ($_POST['angkatan'] ?? 0);
        if ($a !== 0 && ($a < 2000 || $a > 2100)) {
            flash_set('error', 'Angkatan harus tahun 2000-2100. Kosongkan untuk menonaktifkan kenaikan kelas otomatis.');
            redirect('siswa');
        }
        run('UPDATE users SET angkatan=? WHERE id=?', [$a, (int) user()['id']]);
        if ($a > 0) {
            $taStart = $a + (int) profil()['kelas_aktif'] - 1;
            $kel = $taStart - $a + 1;
            flash_set('success', $kel >= 1 && $kel <= 6
                ? "Angkatan $a tersimpan — kenaikan kelas otomatis aktif (saat ini siswa Kelas $kel); daftar Data Siswa tetap menampilkan semua siswa Anda."
                : "Angkatan $a tersimpan — tidak ada siswa angkatan ini pada kelas 1-6 tahun ajaran berjalan.");
        } else {
            flash_set('success', 'Angkatan dikosongkan — kenaikan kelas otomatis nonaktif; daftar tetap menampilkan semua siswa.');
        }
        redirect('siswa');
    }

    if (!can_edit()) {
        flash_set('error', 'Anda tidak memiliki akses untuk mengubah data siswa.');
        redirect('siswa');
    }

    if ($aksi === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $kodeAgama = trim($_POST['kode_agama'] ?? '');
        $agamaList = daftar_agama();
        if (!isset($agamaList[$kodeAgama])) {
            $kodeAgama = '';
        }
        $ayah = trim($_POST['ayah'] ?? '');
        $ibu = trim($_POST['ibu'] ?? '');
        $waliNama = trim($_POST['wali_nama'] ?? '');
        $data = [
            'nis' => trim($_POST['nis'] ?? ''),
            'nisn' => trim($_POST['nisn'] ?? ''),
            'nama' => trim($_POST['nama'] ?? ''),
            'jk' => ($_POST['jk'] ?? 'L') === 'P' ? 'P' : 'L',
            'tempat_lahir' => trim($_POST['tempat_lahir'] ?? ''),
            'tanggal_lahir' => trim($_POST['tanggal_lahir'] ?? ''),
            'tanggal_masuk' => preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($_POST['tanggal_masuk'] ?? '')) ? trim($_POST['tanggal_masuk']) : '',
            'alamat' => trim($_POST['alamat'] ?? ''),
            'ortu' => $ayah !== '' ? $ayah : $waliNama,
            'telp' => trim($_POST['telp'] ?? ''),
            'kelas' => min(6, max(1, (int) ($_POST['kelas'] ?? 1))),
            'rombel' => trim($_POST['rombel'] ?? ''),
            'status' => in_array($_POST['status'] ?? 'aktif', ['aktif', 'lulus', 'pindah'], true) ? $_POST['status'] : 'aktif',
            'kode_agama' => $kodeAgama,
            'agama' => $kodeAgama !== '' ? $agamaList[$kodeAgama] : '',
            'pendidikan_sebelumnya' => trim($_POST['pendidikan_sebelumnya'] ?? ''),
            'ayah' => $ayah,
            'ibu' => $ibu,
            'kerja_ayah' => trim($_POST['kerja_ayah'] ?? ''),
            'kerja_ibu' => trim($_POST['kerja_ibu'] ?? ''),
            'ortu_jalan' => trim($_POST['ortu_jalan'] ?? ''),
            'ortu_kelurahan' => trim($_POST['ortu_kelurahan'] ?? ''),
            'ortu_kecamatan' => trim($_POST['ortu_kecamatan'] ?? ''),
            'ortu_kota' => trim($_POST['ortu_kota'] ?? ''),
            'ortu_provinsi' => trim($_POST['ortu_provinsi'] ?? ''),
            'wali_nama' => $waliNama,
            'wali_kerja' => trim($_POST['wali_kerja'] ?? ''),
            'wali_alamat' => trim($_POST['wali_alamat'] ?? ''),
        ];
        if ($data['nama'] === '') {
            flash_set('error', 'Nama siswa wajib diisi.');
            redirect('siswa');
        }
        if ($data['rombel'] === '') {
            $data['rombel'] = 'Kelas ' . $data['kelas'];
        }
        if ($id > 0) {
            if (!one('SELECT id FROM siswa WHERE id=?' . scope_user(), [$id])) {
                flash_set('error', 'Data siswa tidak ditemukan atau bukan milik Anda.');
                redirect('siswa');
            }
            update_row('siswa', $data, 'id=?', [$id]);
            flash_set('success', 'Data siswa diperbarui.');
        } else {
            insert_row('siswa', $data + ['user_id' => (int) (user()['id'] ?? 0)]);
            $id = (int) db()->lastInsertId();
            flash_set('success', 'Siswa baru ditambahkan.');
        }
        $teksCatatan = trim($_POST['catatan_wali'] ?? '');
        $adaCat = one('SELECT id FROM catatan_wali WHERE siswa_id=? AND ta=? AND semester=? AND kelas=?',
            [$id, $c['ta'], $c['semester'], $c['kelas']]);
        if ($adaCat) {
            update_row('catatan_wali', ['teks' => $teksCatatan], 'id=?', [$adaCat['id']]);
        } else {
            insert_row('catatan_wali', ['siswa_id' => $id, 'ta' => $c['ta'], 'semester' => $c['semester'],
                'kelas' => $c['kelas'], 'teks' => $teksCatatan]);
        }
        redirect('siswa');
    }

    if ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        if (!one('SELECT id FROM siswa WHERE id=?' . scope_user(), [$id])) {
            flash_set('error', 'Data siswa tidak ditemukan atau bukan milik Anda.');
            redirect('siswa');
        }
        run('DELETE FROM nilai_formatif WHERE siswa_id=?', [$id]);
        run('DELETE FROM nilai_sumatif WHERE siswa_id=?', [$id]);
        run('DELETE FROM nilai_ekstra WHERE siswa_id=?', [$id]);
        run('DELETE FROM kehadiran WHERE siswa_id=?', [$id]);
        run('DELETE FROM catatan_wali WHERE siswa_id=?', [$id]);
        run('DELETE FROM kokurikuler_nilai WHERE siswa_id=?', [$id]);
        run('DELETE FROM kokurikuler_capaian WHERE siswa_id=?', [$id]);
        run('DELETE FROM siswa WHERE id=?', [$id]);
        flash_set('success', 'Siswa beserta nilai yang terkait dihapus.');
        redirect('siswa');
    }

    if ($aksi === 'import') {
        $fileList = [];
        if (!empty($_FILES['file']['name']) && is_array($_FILES['file']['name'])) {
            $nFiles = count($_FILES['file']['name']);
            for ($fi = 0; $fi < $nFiles; $fi++) {
                if ($_FILES['file']['name'][$fi] === '') {
                    continue;
                }
                $fileList[] = [
                    'name' => $_FILES['file']['name'][$fi],
                    'tmp' => $_FILES['file']['tmp_name'][$fi],
                    'error' => (int) $_FILES['file']['error'][$fi],
                ];
            }
        } elseif (!empty($_FILES['file']['name'])) {
            $fileList[] = [
                'name' => $_FILES['file']['name'],
                'tmp' => $_FILES['file']['tmp_name'],
                'error' => (int) $_FILES['file']['error'],
            ];
        }
        if (!$fileList) {
            flash_set('error', 'File tidak terunggah. Pilih file Excel (.xlsx) atau CSV (boleh lebih dari satu).');
            redirect('siswa');
        }
        $kt = (string) ($_POST['kelas_tujuan'] ?? '');
        $kelasPaksa = in_array($kt, ['1', '2', '3', '4', '5', '6'], true) ? (int) $kt : null;
        $modeImp = ($_POST['mode'] ?? 'update') === 'skip' ? 'skip' : 'update';
        $gabung = ['total' => 0, 'baru' => 0, 'ubah' => 0, 'lewati' => 0];
        $gagal = [];
        foreach ($fileList as $f) {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $gagal[] = $f['name'] . ' gagal diunggah';
                continue;
            }
            if (!in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
                $gagal[] = $f['name'] . ' bukan .xlsx/.csv';
                continue;
            }
            try {
                $rowsImp = xlsx_read($f['tmp'], $f['name']);
                $st = siswa_import_rows($rowsImp, $modeImp, $kelasPaksa);
                if ($st['pesan']) {
                    $gagal[] = $f['name'] . ': ' . implode(' ', $st['pesan']);
                }
                foreach (['total', 'baru', 'ubah', 'lewati'] as $kImp) {
                    $gabung[$kImp] += $st[$kImp];
                }
            } catch (Throwable $exImp) {
                $gagal[] = $f['name'] . ': ' . $exImp->getMessage();
            }
        }
        $ringkas = sprintf(
            '%d file, %d baris diproses, %d baru, %d diperbarui, %d dilewati',
            count($fileList), $gabung['total'], $gabung['baru'], $gabung['ubah'], $gabung['lewati']
        );
        if ($gagal) {
            flash_set('error', 'Impor tidak seluruhnya berhasil (' . $ringkas . '). Masalah: ' . implode(' | ', $gagal));
        } elseif ($gabung['total'] === 0) {
            flash_set('error', 'Tidak ada baris data yang terbaca dari ' . count($fileList) . ' file.');
        } else {
            flash_set('success', 'Impor selesai: ' . $ringkas . '.');
        }
        redirect('siswa');
    }
}

$myAng = (int) (user()['angkatan'] ?? 0);
$taStart = (int) substr($c['ta'], 0, 4);
$kelasDefault = $myAng > 0 ? min(6, max(1, $taStart - $myAng + 1)) : (int) $c['kelas'];

if (isset($_GET['act']) && $_GET['act'] === 'template' && user()) {
    $h0 = ['No', 'Nama Siswa', 'NISN/NIS', '', 'Tempat, Tanggal Lahir', 'Jenis Kelamin', 'Kode Agama', 'Agama', 'Pendidikan Sebelumnya', 'Alamat Peserta Didik', 'Nama Orang Tua', '', 'Pekerjaan Orang Tua', '', 'Alamat Orang Tua', '', '', '', '', 'Wali Peserta Didik', '', '', 'Kelas'];
    $h1 = ['', '', 'NISN', 'NIS', '', '', '', '', '', '', 'Ayah', 'Ibu', 'Ayah', 'Ibu', 'Jalan / Kampung / RT / RW', 'Kelurahan', 'Kecamatan', 'Kota/Kabupaten', 'Provinsi', 'Nama', 'Pekerjaan', 'Alamat', ''];
    $d1 = [1, 'Ahmad Fauzi', '0091234567', '2025001', 'Yogyakarta, 12/08/2017', 'L', '01', 'Islam', 'TK', 'Jl. Melati No. 3 RT 02/RW 05', 'Budi Santoso', 'Siti Aminah', 'Wiraswasta', 'Ibu Rumah Tangga', 'Jl. Melati No. 3', 'Muja Muju', 'Umbulharjo', 'Yogyakarta', 'DI Yogyakarta', '', '', '', $kelasDefault];
    $d2 = [2, 'Siti Aminah', '0091234568', '2025002', 'Sleman, 03/09/2017', 'P', '01', 'Islam', 'RA', 'Jl. Kenangan No. 8', 'Herman', 'Aminah', 'Petani', 'Guru', 'Jl. Kenangan No. 8', 'Condongcatur', 'Depok', 'Sleman', 'DI Yogyakarta', 'Kasek Sekolah', 'Guru', 'Jl. Sekolah No. 1', $kelasDefault];
    xlsx_download('template-data-siswa.xlsx', [$h0, $h1, $d1, $d2],
        [5, 26, 16, 12, 24, 14, 11, 18, 20, 34, 22, 22, 20, 20, 30, 18, 18, 18, 18, 22, 20, 30, 10],
        [5 => 'L,P', 6 => '01,02,03,04,05,06,07,08']);
}

if (isset($_GET['act']) && $_GET['act'] === 'export' && user()) {
    $h0 = ['No', 'Nama Siswa', 'NISN/NIS', '', 'Tempat, Tanggal Lahir', 'Jenis Kelamin', 'Kode Agama', 'Agama', 'Pendidikan Sebelumnya', 'Alamat Peserta Didik', 'Nama Orang Tua', '', 'Pekerjaan Orang Tua', '', 'Alamat Orang Tua', '', '', '', '', 'Wali Peserta Didik', '', '', 'Kelas'];
    $h1 = ['', '', 'NISN', 'NIS', '', '', '', '', '', '', 'Ayah', 'Ibu', 'Ayah', 'Ibu', 'Jalan / Kampung / RT / RW', 'Kelurahan', 'Kecamatan', 'Kota/Kabupaten', 'Provinsi', 'Nama', 'Pekerjaan', 'Alamat', ''];
    $out = [$h0, $h1];
    $sqlExp = 'SELECT * FROM siswa WHERE 1=1' . scope_user();
    $argsExp = [];
    $sqlExp .= ' ORDER BY kelas, nama';
    $no = 0;
    foreach (all($sqlExp, $argsExp) as $s) {
        $no++;
        $out[] = [
            $no, $s['nama'], $s['nisn'], $s['nis'],
            trim($s['tempat_lahir'] . ($s['tanggal_lahir'] !== '' ? ', ' . $s['tanggal_lahir'] : ''), ', '),
            $s['jk'], $s['kode_agama'], $s['agama'], $s['pendidikan_sebelumnya'], $s['alamat'],
            $s['ayah'], $s['ibu'], $s['kerja_ayah'], $s['kerja_ibu'],
            $s['ortu_jalan'], $s['ortu_kelurahan'], $s['ortu_kecamatan'], $s['ortu_kota'], $s['ortu_provinsi'],
            $s['wali_nama'], $s['wali_kerja'], $s['wali_alamat'], $s['kelas'],
        ];
    }
    xlsx_download('data-siswa.xlsx', $out, [5, 26, 16, 12, 24, 14, 11, 18, 20, 34, 22, 22, 20, 20, 30, 18, 18, 18, 18, 22, 20, 30, 10]);
}

$q = trim($_GET['q'] ?? '');
$filterKelas = (int) ($_GET['fk'] ?? 0);
$sql = 'SELECT * FROM siswa WHERE 1=1' . scope_user();
$args = [];
if ($q !== '') {
    $sql .= ' AND (nama LIKE ? OR nisn LIKE ? OR nis LIKE ?)';
    $like = '%' . $q . '%';
    array_push($args, $like, $like, $like);
}
if ($filterKelas >= 1 && $filterKelas <= 6) {
    $sql .= ' AND kelas=?';
    $args[] = $filterKelas;
}
$sql .= ' ORDER BY kelas, nama';
$siswas = all($sql, $args);
$edit = null;
if (!empty($_GET['edit'])) {
    $edit = one('SELECT * FROM siswa WHERE id=?' . scope_user(), [(int) $_GET['edit']]);
}
$catatanWali = '';
if ($edit) {
    $cn = one('SELECT teks FROM catatan_wali WHERE siswa_id=? AND ta=? AND semester=? AND kelas=?',
        [(int) $edit['id'], $c['ta'], $c['semester'], $c['kelas']]);
    $catatanWali = $cn['teks'] ?? '';
}
$totalSemua = (int) scalar('SELECT COUNT(*) FROM siswa WHERE 1=1' . scope_user());

page_head('Data Siswa', 'Kelola data peserta didik — seluruh siswa akun ini tampil berapa pun kelas konteks yang dibuka.',
    '<a class="btn secondary" href="index.php?page=siswa&act=template">Unduh Template</a>'
    . '<a class="btn secondary" href="index.php?page=siswa&act=export">Export Excel</a>'
    . '<a class="btn" href="#form-siswa">+ Tambah Siswa</a>');
?>
<form class="card" method="post" action="index.php?page=siswa">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="angkatan">
  <h3>Angkatan Saya</h3>
  <div class="hint mb">Isi tahun angkatan (mis. 2025). <b>Tahun Ajaran dihitung otomatis</b> dari tahun angkatan + Kelas Aktif konteks (mis. angkatan 2025 &amp; Kelas 3 → 2027/2028), sehingga tidak perlu mengisi Tahun Ajaran pada menu Profil. Daftar selalu menampilkan <b>seluruh siswa Anda dari semua kelas</b>, dan saat tahun ajaran naik seluruh siswa <b>otomatis dinaikkan satu tingkat kelas</b>.</div>
  <div class="toolbar" style="margin:0">
    <label style="margin:0">Angkatan</label>
    <input type="number" name="angkatan" min="2000" max="2100" value="<?= $myAng > 0 ? $myAng : '' ?>" placeholder="cth. 2025" style="width:130px">
    <button class="btn" type="submit">Simpan Angkatan</button>
    <?php if ($myAng > 0): ?>
      <span class="badge badge-blue">Angkatan <?= $myAng ?> &middot; daftar semua siswa &middot; naik kelas otomatis</span>
    <?php else: ?>
      <span class="badge badge-red">Angkatan belum diisi &middot; naik kelas otomatis nonaktif</span>
    <?php endif; ?>
    <span class="hint">Daftar Data Siswa selalu menampilkan seluruh siswa akun ini dari semua kelas &mdash; filter kelas dipilih di atas tabel.</span>
  </div>
</form>
<form class="card" id="form-siswa" method="post" action="index.php?page=siswa">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="simpan">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <h3><?= $edit ? 'Ubah Data Siswa' : 'Tambah Siswa' ?></h3>
  <div class="save-bar save-bar-in">
    <button class="btn" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Siswa' ?></button>
    <?php if ($edit): ?><a class="btn secondary" href="index.php?page=siswa">Batal</a><?php endif; ?>
    <span class="hint">Tombol menempel di atas — simpan tanpa menggulir ke bawah.</span>
  </div>
  <div class="form-row">
    <div class="field"><label>Nama Siswa</label><input type="text" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required></div>
    <div class="field"><label>NIS</label><input type="text" name="nis" value="<?= e($edit['nis'] ?? '') ?>"></div>
    <div class="field"><label>NISN</label><input type="text" name="nisn" value="<?= e($edit['nisn'] ?? '') ?>"></div>
    <div class="field">
      <label>Jenis Kelamin</label>
      <select name="jk">
        <option value="L"<?= ($edit['jk'] ?? 'L') === 'L' ? ' selected' : '' ?>>Laki-laki</option>
        <option value="P"<?= ($edit['jk'] ?? '') === 'P' ? ' selected' : '' ?>>Perempuan</option>
      </select>
    </div>
  </div>
  <div class="form-row">
    <div class="field"><label>Tempat Lahir</label><input type="text" name="tempat_lahir" value="<?= e($edit['tempat_lahir'] ?? '') ?>"></div>
    <div class="field"><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?= e($edit['tanggal_lahir'] ?? '') ?>"></div>
    <div class="field"><label>Tanggal Masuk</label><input type="date" name="tanggal_masuk" value="<?= e($edit['tanggal_masuk'] ?? '') ?>"></div>
    <div class="field">
      <label>Kode Agama</label>
      <select name="kode_agama">
        <option value="">- pilih -</option>
        <?php foreach (daftar_agama() as $k => $nm): ?>
          <option value="<?= $k ?>"<?= ($edit['kode_agama'] ?? '') === $k ? ' selected' : '' ?>><?= $k ?> - <?= e($nm) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Pendidikan Sebelumnya</label>
      <select name="pendidikan_sebelumnya">
        <option value="">- pilih -</option>
        <?php foreach (daftar_pendidikan_sebelumnya() as $pm): ?>
          <option value="<?= e($pm) ?>"<?= ($edit['pendidikan_sebelumnya'] ?? '') === $pm ? ' selected' : '' ?>><?= e($pm) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Kelas</label>
      <select name="kelas">
        <?php for ($k = 1; $k <= 6; $k++): ?>
          <option value="<?= $k ?>"<?= (int) ($edit['kelas'] ?? $kelasDefault) === $k ? ' selected' : '' ?>>Kelas <?= $k ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="field">
      <label>Status</label>
      <select name="status">
        <?php foreach (['aktif' => 'Aktif', 'lulus' => 'Lulus', 'pindah' => 'Pindah'] as $v => $lbl): ?>
          <option value="<?= $v ?>"<?= ($edit['status'] ?? 'aktif') === $v ? ' selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="form-row">
    <div class="field" style="grid-column:1/-1"><label>Alamat Peserta Didik</label><input type="text" name="alamat" value="<?= e($edit['alamat'] ?? '') ?>"></div>
  </div>
  <div class="form-row">
    <div class="field"><label>Nama Ayah</label><input type="text" name="ayah" value="<?= e($edit['ayah'] ?? '') ?>"></div>
    <div class="field"><label>Pekerjaan Ayah</label><input type="text" name="kerja_ayah" value="<?= e($edit['kerja_ayah'] ?? '') ?>"></div>
    <div class="field"><label>Nama Ibu</label><input type="text" name="ibu" value="<?= e($edit['ibu'] ?? '') ?>"></div>
    <div class="field"><label>Pekerjaan Ibu</label><input type="text" name="kerja_ibu" value="<?= e($edit['kerja_ibu'] ?? '') ?>"></div>
  </div>
  <h3 style="font-size:14px;margin:4px 0 8px">Alamat Orang Tua</h3>
  <div class="form-row">
    <div class="field"><label>Jalan / Kampung / RT / RW</label><input type="text" name="ortu_jalan" value="<?= e($edit['ortu_jalan'] ?? '') ?>"></div>
    <div class="field"><label>Kelurahan</label><input type="text" name="ortu_kelurahan" value="<?= e($edit['ortu_kelurahan'] ?? '') ?>"></div>
    <div class="field"><label>Kecamatan</label><input type="text" name="ortu_kecamatan" value="<?= e($edit['ortu_kecamatan'] ?? '') ?>"></div>
    <div class="field"><label>Kota / Kabupaten</label><input type="text" name="ortu_kota" value="<?= e($edit['ortu_kota'] ?? '') ?>"></div>
    <div class="field"><label>Provinsi</label><input type="text" name="ortu_provinsi" value="<?= e($edit['ortu_provinsi'] ?? '') ?>"></div>
  </div>
  <h3 style="font-size:14px;margin:4px 0 8px">Wali Peserta Didik</h3>
  <div class="form-row">
    <div class="field"><label>Nama Wali</label><input type="text" name="wali_nama" value="<?= e($edit['wali_nama'] ?? '') ?>"></div>
    <div class="field"><label>Pekerjaan Wali</label><input type="text" name="wali_kerja" value="<?= e($edit['wali_kerja'] ?? '') ?>"></div>
    <div class="field"><label>Alamat Wali</label><input type="text" name="wali_alamat" value="<?= e($edit['wali_alamat'] ?? '') ?>"></div>
    <div class="field"><label>No. HP</label><input type="text" name="telp" value="<?= e($edit['telp'] ?? '') ?>"></div>
    <div class="field"><label>Rombel</label><input type="text" name="rombel" value="<?= e($edit['rombel'] ?? ('Kelas ' . ($edit['kelas'] ?? $kelasDefault))) ?>"></div>
  </div>
  <div class="form-row">
    <div class="field" style="grid-column:1/-1">
      <label>Catatan Wali Kelas &middot; Semester <?= (int) $c['semester'] ?> &middot; <?= e($c['ta']) ?></label>
      <textarea name="catatan_wali" rows="3" placeholder="Catatan wali kelas untuk raport semester ini — tampil pada kotak Catatan Wali Kelas di raport cetak..."><?= e($catatanWali) ?></textarea>
      <div class="hint">Khusus siswa yang sedang diubah/ditambah. Tampil di raport sesuai semester &amp; tahun ajaran konteks.</div>
    </div>
  </div>
</form>

<div class="grid grid-2">
  <form class="card" method="post" action="index.php?page=siswa" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="import">
    <h3>Import Excel / CSV</h3>
    <div class="hint mb">Kolom wajib: <b>Nama Siswa</b>. Format mengikuti template (header 2 baris): NISN/NIS, Tempat+Tanggal Lahir, Jenis Kelamin, Kode Agama, Agama, Pendidikan Sebelumnya, Alamat Peserta Didik, Nama &amp; Pekerjaan Orang Tua (Ayah/Ibu), Alamat Orang Tua (Jalan/Kampung/RT/RW, Kelurahan, Kecamatan, Kota/Kabupaten, Provinsi), serta Wali Peserta Didik. Data dikenali melalui NISN, NIS, atau nama+kelas.</div>
    <div class="form-row">
      <div class="field"><label>File (.xlsx / .csv) &mdash; boleh pilih lebih dari satu</label><input type="file" name="file[]" accept=".xlsx,.csv,.txt" multiple required></div>
      <div class="field">
        <label>Kelas tujuan (untuk file tanpa kolom Kelas)</label>
        <select name="kelas_tujuan">
          <option value="">Otomatis &mdash; ikuti kolom Kelas file, lalu kelas konteks</option>
          <?php for ($kt = 1; $kt <= 6; $kt++): ?>
            <option value="<?= $kt ?>">Langsung ke Kelas <?= $kt ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="field">
        <label>Jika data sudah ada</label>
        <select name="mode">
          <option value="update">Perbarui data lama</option>
          <option value="skip">Lewati data lama</option>
        </select>
      </div>
    </div>
    <button class="btn blue" type="submit">Impor Sekarang</button>
    <a class="btn secondary" href="index.php?page=siswa&act=template">Template Kosong</a>
  </form>

  <div class="card">
    <h3>Statistik</h3>
    <table class="tbl">
      <tbody>
        <tr><td>Total siswa terdaftar</td><td class="right"><b><?= $totalSemua ?></b></td></tr>
        <tr><td>Tampil pada daftar</td><td class="right"><b><?= count($siswas) ?></b></td></tr>
        <tr><td>Angkatan saya</td><td class="right"><b><?= $myAng > 0 ? $myAng : 'Belum diisi' ?></b></td></tr>
        <tr><td>Konteks nilai &amp; raport</td><td class="right"><b>Kelas <?= $c['kelas'] ?> (<?= e($c['fase']) ?>)</b></td></tr>
      </tbody>
    </table>
    <div class="hint mt">Kenaikan kelas berjalan <b>otomatis saat tahun ajaran naik</b> — tidak perlu tombol Naik Kelas.</div>
  </div>
</div>

<form class="toolbar" method="get" action="index.php">
  <input type="hidden" name="page" value="siswa">
  <input class="search" type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama / NISN / NIS...">
  <select name="fk">
    <option value="0">Semua Kelas</option>
    <?php for ($k = 1; $k <= 6; $k++): ?>
      <option value="<?= $k ?>"<?= $filterKelas === $k ? ' selected' : '' ?>>Kelas <?= $k ?></option>
    <?php endfor; ?>
  </select>
  <button class="btn secondary" type="submit">Cari</button>
  <span class="hint">Filter kelas bersifat opsional — tanpa pilihan, seluruh siswa akun ini tampil.</span>
</form>

<div class="table-wrap">
  <table class="tbl">
    <thead>
      <tr>
        <th class="center" style="width:40px">No</th>
        <th>Nama</th><th>NIS</th><th>NISN</th><th class="center">L/P</th>
        <th class="center">Kelas</th><th>Orang Tua</th><th>Status</th><th class="center">Aksi</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$siswas): ?>
      <tr><td colspan="9" class="empty">Belum ada data siswa. Tambahkan manual atau impor Excel.</td></tr>
    <?php else: $no = 0; foreach ($siswas as $s): $no++; ?>
      <tr>
        <td class="center"><?= $no ?></td>
        <td class="name-cell"><?= e($s['nama']) ?><div class="hint"><?= e($s['rombel']) ?></div></td>
        <td><?= e($s['nis']) ?></td>
        <td><?= e($s['nisn']) ?></td>
        <td class="center"><?= e($s['jk']) ?></td>
        <td class="center"><?= (int) $s['kelas'] ?></td>
        <td><?= e($s['ortu']) ?></td>
        <td><span class="badge <?= $s['status'] === 'aktif' ? 'badge-green' : 'badge-gray' ?>"><?= e($s['status']) ?></span></td>
        <td class="center">
          <a class="btn sm secondary" href="index.php?page=siswa&edit=<?= (int) $s['id'] ?>">Ubah</a>
          <?php if (can_edit()): ?>
          <form method="post" action="index.php?page=siswa" data-confirm="Hapus siswa beserta seluruh nilainya?">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <button class="btn sm danger" type="submit">Hapus</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
