<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/store.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="container py-5"><h1 class="h3">Product not found</h1><p class="text-secondary">Choose a product from the shop.</p><a class="btn btn-primary" href="shop.php">Browse products</a></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, b.name AS brand_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN brands b ON b.id = p.brand_id
     WHERE p.slug = :slug AND p.is_active = 1
     LIMIT 1"
);
$stmt->execute(['slug' => $slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="container py-5"><h1 class="h3">Product not found</h1><p class="text-secondary">This product may have been removed or is no longer available.</p><a class="btn btn-primary" href="shop.php">Browse products</a></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$reviewErrors=[];$reviewMessage='';$reviewUser=customer_user();
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['review_action']??'')==='submit_review'){
 if(!csrf_is_valid($_POST['csrf_token']??null)){http_response_code(419);exit('Invalid form token.');}
 if(!$reviewUser){redirect('login.php?next='.rawurlencode('product.php?slug='.$slug).'#reviews');}
 $rating=(int)($_POST['rating']??0);$reviewTitle=trim((string)($_POST['review_title']??''));$reviewBody=trim((string)($_POST['review_body']??''));
 if($rating<1||$rating>5)$reviewErrors[]='Choose a rating from 1 to 5.';
 if($reviewBody==='')$reviewErrors[]='Please write a short review.';
 if(!$reviewErrors){$check=$pdo->prepare('SELECT id FROM product_reviews WHERE product_id=? AND user_id=? LIMIT 1');$check->execute([(int)$product['id'],(int)$reviewUser['id']]);if($check->fetch())$reviewErrors[]='You have already reviewed this product.';else{$ins=$pdo->prepare("INSERT INTO product_reviews(product_id,user_id,rating,title,review,status) VALUES(?,?,?,?,?,'pending')");$ins->execute([(int)$product['id'],(int)$reviewUser['id'],$rating,$reviewTitle?:null,$reviewBody]);$reviewMessage='Thank you. Your review has been submitted and will appear after moderation.';}}
}
$reviewStmt=$pdo->prepare("SELECT r.rating,r.title,r.review,r.created_at,u.full_name FROM product_reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? AND r.status='approved' ORDER BY r.created_at DESC");$reviewStmt->execute([(int)$product['id']]);$reviews=$reviewStmt->fetchAll();
$ratingStmt=$pdo->prepare("SELECT AVG(rating) average_rating,COUNT(*) review_count FROM product_reviews WHERE product_id=? AND status='approved'");$ratingStmt->execute([(int)$product['id']]);$ratingSummary=$ratingStmt->fetch();
$relatedStmt=$pdo->prepare('SELECT * FROM products WHERE is_active=1 AND category_id <=> ? AND id<>? ORDER BY is_featured DESC,created_at DESC LIMIT 12');$relatedStmt->execute([$product['category_id'],(int)$product['id']]);$relatedProducts=$relatedStmt->fetchAll();
$pageTitle = $product['name'];
$specifications = [];
if (!empty($product['specifications'])) {
    $decoded = json_decode((string) $product['specifications'], true);
    if (is_array($decoded)) {
        $specifications = $decoded;
    }
}
$imagesStmt = $pdo->prepare('SELECT image_path, alt_text FROM product_images WHERE product_id = :id ORDER BY sort_order, id');
$imagesStmt->execute(['id' => $product['id']]);
$extraImages = $imagesStmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="container py-4 py-lg-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
            <?php if (!empty($product['category_name'])): ?><li class="breadcrumb-item"><a href="shop.php?category=<?= (int) $product['category_id'] ?>"><?= e($product['category_name']) ?></a></li><?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= e($product['name']) ?></li>
        </ol>
    </nav>
    <div class="row g-4 g-lg-5">
        <div class="col-lg-6">
            <div class="product-detail-image" data-image-zoom role="button" tabindex="0" aria-label="Zoom product image">
                <?php if (!empty($product['image_path'])): ?>
                    <img src="<?= e($product['image_path']) ?>" alt="<?= e($product['name']) ?>">
                <?php else: ?>
                    <div class="product-placeholder"><span>BC</span></div>
                <?php endif; ?>
            </div>
            <?php if ($extraImages): ?>
                <div class="row g-2 mt-2">
                    <?php foreach ($extraImages as $image): ?>
                        <div class="col-3"><div class="product-thumb"><img src="<?= e($image['image_path']) ?>" alt="<?= e($image['alt_text'] ?: $product['name']) ?>" loading="lazy"></div></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-6">
            <span class="eyebrow"><?= e($product['category_name'] ?? 'ELECTRONICS') ?></span>
            <h1 class="display-6 fw-bold mt-2"><?= e($product['name']) ?></h1>
            <p class="text-secondary"><?= e($product['short_description'] ?? '') ?></p>
            <div class="d-flex align-items-center gap-3 my-4">
                <span class="display-6 fw-bold text-primary"><?= money($product['price']) ?></span>
                <?php if ($product['compare_at_price'] !== null && (float) $product['compare_at_price'] > (float) $product['price']): ?>
                    <span class="text-secondary text-decoration-line-through"><?= money($product['compare_at_price']) ?></span>
                <?php endif; ?>
            </div>
            <div class="mb-4">
                <?php if ((int) $product['stock_quantity'] > 0): ?>
                    <span class="stock-pill stock-available">In stock</span>
                    <span class="small text-secondary ms-2"><?= (int) $product['stock_quantity'] ?> available</span>
                <?php else: ?>
                    <span class="stock-pill stock-unavailable">Currently out of stock</span>
                <?php endif; ?>
            </div>
            <div class="product-description mb-4"><?= nl2br(e($product['description'] ?? 'Product details will be added soon.')) ?></div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php if ((int) $product['stock_quantity'] > 0): ?>
                <form method="post" action="cart-action.php" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <label class="visually-hidden" for="quantity">Quantity</label>
                    <input id="quantity" class="form-control" style="width:90px" type="number" name="quantity" min="1" max="<?= (int) $product['stock_quantity'] ?>" value="1" required>
                    <button type="submit" class="btn btn-primary btn-lg">Add to cart</button>
                    <button type="submit" name="go_checkout" value="1" class="btn btn-success btn-lg">Quick shop · Checkout</button>
                </form>
                <?php endif; ?>
                <a class="btn btn-outline-primary btn-lg" href="shop.php">Continue shopping</a>
            </div>
            <p class="small text-secondary mt-3">Checkout is available on this demo store. Manual M-Pesa payments require staff verification.</p>
            <hr class="my-4">
            <dl class="row small">
                <?php if (!empty($product['brand_name'])): ?><dt class="col-4 text-secondary">Brand</dt><dd class="col-8"><?= e($product['brand_name']) ?></dd><?php endif; ?>
                <dt class="col-4 text-secondary">SKU</dt><dd class="col-8"><?= e($product['sku']) ?></dd>
            </dl>
            <?php if ($specifications): ?>
                <h2 class="h5 mt-4">Specifications</h2>
                <div class="table-responsive"><table class="table table-sm"><tbody>
                    <?php foreach ($specifications as $key => $value): ?>
                        <tr><th><?= e((string) $key) ?></th><td><?= e(is_scalar($value) ? (string) $value : json_encode($value)) ?></td></tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>
    </div>

<div class="row mt-5"><div class="col-12"><section id="reviews" class="reviews-panel"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h2 class="h3 mb-1">Customer reviews</h2><div class="review-summary"><strong><?=number_format((float)($ratingSummary['average_rating']??0),1)?> / 5</strong> <span class="stars"><?=str_repeat('★',(int)round((float)($ratingSummary['average_rating']??0)))?><?=str_repeat('☆',5-(int)round((float)($ratingSummary['average_rating']??0)))?></span> <span class="text-secondary">(<?= (int)($ratingSummary['review_count']??0) ?> reviews)</span></div></div></div>
<?php if($reviewMessage):?><div class="alert alert-success"><?=e($reviewMessage)?></div><?php endif;?><?php foreach($reviewErrors as $err):?><div class="alert alert-danger"><?=e($err)?></div><?php endforeach;?>
<?php if($reviewUser):?><form method="post" class="review-form card border-0 p-3 mb-4"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="review_action" value="submit_review"><h3 class="h6">Write a review</h3><label class="form-label">Your rating</label><select class="form-select mb-2" name="rating" required><option value="">Choose rating</option><?php for($r=5;$r>=1;$r--):?><option value="<?=$r?>"><?=$r?> star<?=$r>1?'s':''?></option><?php endfor;?></select><input class="form-control mb-2" name="review_title" maxlength="160" placeholder="Review title (optional)"><textarea class="form-control mb-2" name="review_body" rows="3" required placeholder="Share your experience with this product"></textarea><button class="btn btn-primary align-self-start">Submit review</button><small class="text-secondary mt-2">Reviews are published after moderation.</small></form><?php else:?><p><a href="login.php?next=<?=e(rawurlencode('product.php?slug='.$slug.'#reviews'))?>">Log in</a> to submit a review.</p><?php endif;?>
<?php if(!$reviews):?><p class="text-secondary">No approved reviews yet. Be the first to share your experience.</p><?php else:foreach($reviews as $r):?><article class="customer-review"><div class="d-flex justify-content-between gap-2"><strong><?=e($r['full_name'])?></strong><small class="text-secondary"><?=e(date('M j, Y',strtotime($r['created_at'])))?></small></div><div class="stars"><?=str_repeat('★',(int)$r['rating'])?><?=str_repeat('☆',5-(int)$r['rating'])?></div><?php if($r['title']):?><h3 class="h6 mt-1"><?=e($r['title'])?></h3><?php endif;?><p class="mb-0"><?=nl2br(e($r['review']))?></p></article><?php endforeach;endif;?></section></div></div>
<?php if($relatedProducts):?><section class="mt-5 product-rail-section"><div class="rail-heading"><h2>Related products</h2><div class="d-flex gap-2"><button class="rail-arrow" data-rail-prev aria-label="Previous"><i class="bi bi-chevron-left"></i></button><button class="rail-arrow" data-rail-next aria-label="Next"><i class="bi bi-chevron-right"></i></button></div></div><div class="product-rail" data-product-rail><?php foreach($relatedProducts as $product):?><div class="product-rail-item"><?php require __DIR__.'/includes/product-card.php';?></div><?php endforeach;?></div></section><?php endif;?>
</section>
<div class="image-zoom-overlay" data-zoom-overlay><button type="button" data-zoom-close aria-label="Close">&times;</button><img src="" alt="Product enlarged image" data-zoom-image></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
