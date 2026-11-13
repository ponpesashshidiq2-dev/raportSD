<?php
require __DIR__ . '/config.php';
require __DIR__ . '/app/kurikulum.php';
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/xlsx.php';
require __DIR__ . '/app/layout.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

db();

$page = $_GET['page'] ?? 'dashboard';
if (!is_string($page) || !preg_match('/^[a-z_]+$/', $page)) {
    $page = 'dashboard';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();
}

$publicPages = ['login'];
if ($page === 'logout') {
    logout_user();
    redirect('login');
}
if (!user() && !in_array($page, $publicPages, true)) {
    $page = 'login';
}
if (user() && $page === 'login') {
    redirect('dashboard');
}
if (user() && !menu_allowed($page)) {
    flash_set('error', 'Anda tidak memiliki akses ke menu tersebut.');
    redirect('dashboard');
}

$file = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($file)) {
    redirect('dashboard');
}

ob_start();
require $file;
$content = ob_get_clean();

if ($page === 'login') {
    echo render_auth($content, page_title($page));
} else {
    echo render_layout($content, page_title($page), $page);
}
