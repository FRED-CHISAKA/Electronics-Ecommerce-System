<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Shop';
$q = trim((string) ($_GET['q'] ?? ''));
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$sort = (string) ($_GET['sort'] ?? 'newest');
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 12;
$offset = ($page - 1) * $perPage;

$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
$where = ['p.is_active = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :q_name OR p.description LIKE :q_desc OR p.sku LIKE :q_sku OR EXISTS (SELECT 1 FROM brands b WHERE b.id=p.brand_id AND b.name LIKE :q_brand))';
    $needle = '%' . $q . '%';
    $params['q_name'] = $needle;
    $params['q_desc'] = $needle;
    $params['q_sku'] = $needle;
    $params['q_brand'] = $needle;
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :category_id';
    $params['category_id'] = $categoryId;
}
$orderBy = match ($sort) {
    'price_low' => 'p.price ASC, p.name ASC',
    'price_high' => 'p.price DESC, p.name ASC',
    'name' => 'p.name ASC',
    default => 'p.created_at DESC',
};
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE {$whereSql}");
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalProducts / $perPage));

$sql = "SELECT p.id, p.name, p.slug, p.short_description, p.price, p.compare_at_price,
               p.image_path, p.stock_quantity, p.sku, p.created_at, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE {$whereSql}
        ORDER BY {$orderBy}
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="container py-4 py-lg-5">
        <span class="eyebrow">THE COLLECTION</span>
        <h1 class="display-6 fw-bold mt-2 mb-2">Shop electronics</h1>
        <p class="text-secondary mb-0">Find your next device, upgrade or everyday accessory.</p>
    </div>
</section>
<section class="container py-4 py-lg-5">
    <form class="shop-toolbar row g-2 align-items-end mb-4" method="get" action="shop.php">
        <div class="col-12 col-md-5">
            <label for="q" class="form-label small fw-semibold">Search products</label>
            <input id="q" name="q" class="form-control" type="search" value="<?= e($q) ?>" placeholder="Search name, details or SKU">
        </div>
        <div class="col-6 col-md-3">
            <label for="category" class="form-label small fw-semibold">Category</label>
            <select id="category" name="category" class="form-select">
                <option value="0">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="sort" class="form-label small fw-semibold">Sort by</label>
            <select id="sort" name="sort" class="form-select">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-grid"><button class="btn btn-primary" type="submit">Apply filters</button></div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="small text-secondary mb-0"><?= number_format($totalProducts) ?> product<?= $totalProducts === 1 ? '' : 's' ?> found</p>
        <?php if ($q !== '' || $categoryId > 0): ?><a class="small" href="shop.php">Clear filters</a><?php endif; ?>
    </div>

    <?php if (!$products): ?>
        <div class="empty-state text-center p-5">
            <h2 class="h5">No products found</h2>
            <p class="text-secondary">Try another search or category.</p>
            <a href="shop.php" class="btn btn-outline-primary">View all products</a>
        </div>
    <?php else: ?>
        <div class="row g-3 g-lg-4">
            <?php foreach ($products as $product): ?>
                <div class="col-6 col-md-4 col-xl-3"><?php require __DIR__.'/includes/product-card.php'; ?></div>
            <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="mt-5" aria-label="Product pages">
                <ul class="pagination justify-content-center">
                    <?php for ($n = 1; $n <= $totalPages; $n++): ?>
                        <?php $queryParams = ['page' => $n, 'q' => $q, 'category' => $categoryId, 'sort' => $sort]; ?>
                        <li class="page-item <?= $n === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e(http_build_query($queryParams)) ?>"><?= $n ?></a></li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
