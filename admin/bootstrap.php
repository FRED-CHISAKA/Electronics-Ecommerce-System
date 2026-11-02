<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

function admin_require_login(): void {
    if (empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
        redirect('login.php');
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, full_name, email, role, status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([(int) $_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    if (!$admin || $admin['role'] !== 'admin' || $admin['status'] !== 'active') {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) { session_destroy(); }
        redirect('login.php?disabled=1');
    }
    $GLOBALS['currentAdmin'] = $admin;
}
function admin_page_start(string $title): void {
    $storeName = 'BlueCircuit Electronics';
    try { global $pdo; $q=$pdo->query("SELECT setting_value FROM settings WHERE setting_key='store_name' LIMIT 1"); $storeName=(string)($q->fetchColumn() ?: $storeName); } catch (Throwable $e) {}
    $adminName = $GLOBALS['currentAdmin']['full_name'] ?? 'Administrator';
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> | Admin</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><link href="assets/admin.css" rel="stylesheet"><script>try{document.documentElement.dataset.theme=localStorage.getItem('bc-theme')||'light';}catch(e){document.documentElement.dataset.theme='light';}</script></head><body><nav class="navbar navbar-dark admin-topbar px-3"><a class="navbar-brand fw-bold" href="index.php"><?= e($storeName) ?> <span class="badge text-bg-light ms-1">Admin</span></a><div class="d-flex align-items-center gap-3 text-white"><span class="d-none d-sm-inline"><?= e($adminName) ?></span><button class="theme-toggle theme-toggle-admin" data-theme-toggle type="button" aria-label="Switch to dark mode" title="Switch to dark mode" aria-pressed="false"><span aria-hidden="true">☾</span></button><a class="btn btn-sm btn-outline-light" href="../index.php" target="_blank">View shop</a><form method="post" action="logout.php" class="m-0"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-sm btn-light" type="submit">Log out</button></form></div></nav><div class="admin-shell"><aside class="admin-sidebar"><div class="sidebar-label">MANAGEMENT</div><a href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a><a href="products.php"><i class="bi bi-box-seam"></i> Products</a><a href="categories.php"><i class="bi bi-tags"></i> Categories</a><a href="orders.php"><i class="bi bi-bag-check"></i> Orders</a><a href="payments.php"><i class="bi bi-credit-card"></i> Payment approval</a><a href="inventory.php"><i class="bi bi-boxes"></i> Inventory</a><a href="media.php"><i class="bi bi-images"></i> Media library</a><a href="customers.php"><i class="bi bi-people"></i> Customers</a><a href="reviews.php"><i class="bi bi-star"></i> Product reviews</a><a href="seo.php"><i class="bi bi-search"></i> SEO</a><a href="settings.php"><i class="bi bi-gear"></i> Store settings</a></aside><main class="admin-main"><div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2"><div><div class="text-secondary small">ADMINISTRATION</div><h1 class="h3 mb-0"><?= e($title) ?></h1></div></div><?php if (!empty($_SESSION['flash'])): ?><div class="alert alert-info alert-dismissible fade show" role="alert"><?= e((string)$_SESSION['flash']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['flash']); endif; ?><?php
}
function admin_page_end(): void { ?></main></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="../assets/js/app.js"></script></body></html><?php }
function admin_csrf_check(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) { http_response_code(419); exit('Invalid or expired form token. Go back and try again.'); } }
function slugify(string $value): string { $value = strtolower(trim($value)); $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? ''; return trim($value, '-') ?: 'item-' . bin2hex(random_bytes(3)); }
