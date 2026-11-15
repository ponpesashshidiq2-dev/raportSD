<?php
if (!is_admin()) {
    flash_set('error', 'Hanya administrator yang dapat mengakses backup dan restore.');
    redirect('dashboard');
}

function backup_dump(): array
{
    $data = ['meta' => [
        'app' => APP_SHORT,
        'versi' => 1,
        'waktu' => date('Y-m-d H:i:s'),
    ], 'tables' => []];
    foreach (db_tables() as $t) {
        $data['tables'][$t] = all('SELECT * FROM ' . $t);
    }
    return $data;
}

if (isset($_GET['act']) && $_GET['act'] === 'download') {
    $json = json_encode(backup_dump(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $file = 'backup-raport-' . date('Ymd-His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . strlen($json));
    echo $json;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'restore') {
        if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'File backup tidak terunggah.');
            redirect('backup');
        }
        $raw = file_get_contents($_FILES['file']['tmp_name']);
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['tables']) || !is_array($data['tables'])) {
            flash_set('error', 'File bukan backup yang valid.');
            redirect('backup');
        }
        try {
            db()->exec('PRAGMA foreign_keys = OFF');
            db()->beginTransaction();
            foreach (db_tables() as $t) {
                run('DELETE FROM ' . $t);
            }
            $allowed = db_tables();
            $inserted = 0;
            foreach ($data['tables'] as $t => $rows) {
                if (!in_array($t, $allowed, true) || !is_array($rows)) {
                    continue;
                }
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    insert_row($t, $row);
                    $inserted++;
                }
            }
            db()->commit();
            backfill_kelas_siswa(db());
            backfill_dimensi_ket(db());
            db()->exec('PRAGMA foreign_keys = ON');
            flash_set('success', "Restore berhasil: $inserted baris dari " . count($data['tables']) . " tabel.");
        } catch (Throwable $ex) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            db()->exec('PRAGMA foreign_keys = ON');
            flash_set('error', 'Restore gagal: ' . $ex->getMessage());
        }
        redirect('backup');
    }

    if ($aksi === 'reset_nilai') {
        db()->beginTransaction();
        try {
            $n = 0;
            foreach (['nilai_formatif', 'nilai_sumatif', 'nilai_ekstra', 'kokurikuler_nilai', 'kokurikuler_capaian', 'kehadiran', 'sumatif_jenis', 'siswa_jilid'] as $t) {
                $n += run('DELETE FROM ' . $t);
            }
            db()->commit();
            flash_set('success', "$n baris nilai dihapus. Data siswa, mapel, TP tetap aman.");
        } catch (Throwable $ex) {
            db()->rollBack();
            flash_set('error', 'Gagal: ' . $ex->getMessage());
        }
        redirect('backup');
    }
}

$counts = [];
$dbSize = file_exists(DB_FILE) ? filesize(DB_FILE) : 0;
foreach (db_tables() as $t) {
    $counts[$t] = (int) scalar('SELECT COUNT(*) FROM ' . $t);
}

page_head('Backup & Restore', 'Unduh seluruh data sebagai file JSON, atau pulihkan dari file backup sebelumnya.');
?>
<div class="grid grid-2">
  <div class="card">
    <h3>Backup (Unduh Data)</h3>
    <p class="hint">File backup berisi seluruh tabel: profil, pengguna, siswa, mapel, CP, TP, nilai formatif/sumatif, kokurikuler, ekstrakurikuler, dan kehadiran. Simpan file di lokasi aman.</p>
    <div class="table-wrap mb">
      <table class="tbl">
        <tbody>
          <?php foreach ($counts as $t => $n): ?>
            <tr><td><?= e($t) ?></td><td class="right"><b><?= $n ?> baris</b></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="hint">Ukuran database: <b><?= number_format($dbSize / 1024, 1) ?> KB</b></p>
    <a class="btn" href="index.php?page=backup&act=download">Unduh Backup (.json)</a>
  </div>

  <div class="card">
    <h3>Restore (Pulihkan Data)</h3>
    <p class="hint">Restore akan <b>menimpa seluruh data saat ini</b> dengan isi file backup. Pastikan telah melakukan backup terbaru sebelum melanjutkan.</p>
    <form method="post" action="index.php?page=backup" data-confirm="Seluruh data saat ini akan ditimpa oleh file backup. Lanjutkan?">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="restore">
      <div class="field">
        <label>File Backup (.json)</label>
        <input type="file" name="file" accept=".json,application/json" required>
      </div>
      <button class="btn blue" type="submit">Pulihkan dari File</button>
    </form>

    <hr style="border:none;border-top:1px solid var(--line);margin:18px 0">

    <h3>Kosongkan Seluruh Nilai</h3>
    <p class="hint">Menghapus semua nilai formatif, sumatif, kokurikuler, ekstrakurikuler, kehadiran, dan daftar penilaian. Data siswa, mapel, CP, serta TP tetap tersimpan. Berguna di awal tahun ajaran baru.</p>
    <form method="post" action="index.php?page=backup" data-confirm="Hapus seluruh nilai? Tindakan ini tidak dapat dibatalkan.">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="reset_nilai">
      <button class="btn danger" type="submit">Kosongkan Nilai</button>
    </form>
  </div>
</div>
