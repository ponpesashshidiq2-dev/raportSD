<?php
$me = user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_admin()) {
        flash_set('error', 'Hanya administrator yang dapat mengelola pengguna.');
        redirect('users');
    }
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'wali', 'kepsek'], true) ? $_POST['role'] : 'wali';
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || $nama === '') {
            flash_set('error', 'Username dan nama wajib diisi.');
            redirect('users');
        }
        $dupe = one('SELECT id FROM users WHERE LOWER(username)=? AND id<>?', [mb_strtolower($username), $id]);
        if ($dupe) {
            flash_set('error', 'Username sudah digunakan.');
            redirect('users');
        }
        $data = ['username' => $username, 'nama' => $nama, 'role' => $role, 'aktif' => isset($_POST['aktif']) ? 1 : 0];
        if ($id > 0) {
            if ($password !== '') {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            update_row('users', $data, 'id=?', [$id]);
            flash_set('success', 'Pengguna diperbarui.');
        } else {
            if (strlen($password) < 6) {
                flash_set('error', 'Password minimal 6 karakter.');
                redirect('users');
            }
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            insert_row('users', $data);
            flash_set('success', 'Pengguna ditambahkan.');
        }
        redirect('users');
    }
    if ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $me['id']) {
            flash_set('error', 'Tidak dapat menghapus akun yang sedang digunakan.');
            redirect('users');
        }
        run('DELETE FROM users WHERE id=?', [$id]);
        flash_set('success', 'Pengguna dihapus.');
        redirect('users');
    }
}

$edit = null;
if (!empty($_GET['edit'])) {
    $edit = one('SELECT * FROM users WHERE id=?', [(int) $_GET['edit']]);
}
$daftar = all('SELECT id, username, nama, role, aktif, created_at FROM users ORDER BY role, username');

page_head('Pengguna', 'Akun multiuser: administrator, wali kelas, dan kepala sekolah.');
?>
<?php if (is_admin()): ?>
<form class="card" method="post" action="index.php?page=users">
  <?= csrf_field() ?>
  <input type="hidden" name="aksi" value="simpan">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <h3><?= $edit ? 'Ubah Pengguna' : 'Tambah Pengguna' ?></h3>
  <div class="save-bar save-bar-in">
    <button class="btn" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Pengguna' ?></button>
    <?php if ($edit): ?><a class="btn secondary" href="index.php?page=users">Batal</a><?php endif; ?>
    <span class="hint">Tombol menempel di atas — simpan tanpa menggulir.</span>
  </div>
  <div class="form-row">
    <div class="field"><label>Username</label><input type="text" name="username" value="<?= e($edit['username'] ?? '') ?>" required></div>
    <div class="field"><label>Nama Lengkap</label><input type="text" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required></div>
    <div class="field">
      <label>Peran</label>
      <select name="role">
        <?php foreach (['admin' => 'Administrator', 'wali' => 'Wali Kelas', 'kepsek' => 'Kepala Sekolah'] as $v => $lbl): ?>
          <option value="<?= $v ?>"<?= ($edit['role'] ?? 'wali') === $v ? ' selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Password <?= $edit ? '(kosongkan jika tidak diubah)' : '' ?></label>
      <input type="password" name="password" <?= $edit ? '' : 'required' ?> minlength="6" autocomplete="new-password">
    </div>
  </div>
  <label class="check mb"><input type="checkbox" name="aktif" value="1"<?= !$edit || $edit['aktif'] ? ' checked' : '' ?>> Akun aktif</label>
</form>
<?php endif; ?>

<div class="alert alert-info">
  <b>Administrator:</b> akses penuh termasuk profil, impor, backup, restore, dan pengosongan nilai. &nbsp;
  <b>Wali Kelas:</b> kelola siswa, seluruh input nilai, dan unduh backup. &nbsp;
  <b>Kepala Sekolah:</b> melihat data, monitoring, mencetak raport, dan unduh backup.
</div>

<div class="table-wrap">
  <table class="tbl">
    <thead><tr><th class="center" style="width:40px">No</th><th>Username</th><th>Nama</th><th>Peran</th><th>Status</th><?php if (is_admin()): ?><th class="center">Aksi</th><?php endif; ?></tr></thead>
    <tbody>
    <?php $no = 0; foreach ($daftar as $u): $no++; ?>
      <tr>
        <td class="center"><?= $no ?></td>
        <td class="name-cell"><?= e($u['username']) ?></td>
        <td><?= e($u['nama']) ?><?= (int) $u['id'] === (int) $me['id'] ? ' <span class="badge badge-blue">Anda</span>' : '' ?></td>
        <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-red' : ($u['role'] === 'kepsek' ? 'badge-amber' : 'badge-green') ?>"><?= e(role_label($u['role'])) ?></span></td>
        <td><?= $u['aktif'] ? '<span class="badge badge-green">Aktif</span>' : '<span class="badge badge-gray">Nonaktif</span>' ?></td>
        <?php if (is_admin()): ?>
        <td class="center">
          <a class="btn sm secondary" href="index.php?page=users&edit=<?= (int) $u['id'] ?>">Ubah</a>
          <?php if ((int) $u['id'] !== (int) $me['id']): ?>
          <form method="post" action="index.php?page=users" data-confirm="Hapus pengguna ini?">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <button class="btn sm danger" type="submit">Hapus</button>
          </form>
          <?php endif; ?>
        </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
