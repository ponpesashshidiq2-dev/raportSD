<?php
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (login_attempt($username, $password)) {
        redirect('dashboard');
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<h2 class="login-title">Masuk</h2>
<div class="login-sub">Silakan login menggunakan akun yang diberikan.</div>
<?php if ($error): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<form method="post" action="index.php?page=login">
  <?= csrf_field() ?>
  <div class="field">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" autofocus autocomplete="username" required>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required>
  </div>
  <button class="btn" type="submit" style="width:100%;justify-content:center">Masuk</button>
  <div class="cred">
    Akun bawaan: <code>admin / admin123</code> &middot; <code>wali / wali123</code> &middot; <code>kepsek / kepsek123</code>
  </div>
</form>
