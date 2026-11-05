<?php
$pageTitle=$pageTitle??'BlueCircuit Electronics';
require_once __DIR__.'/store.php';
$storeName=setting('store_name','BlueCircuit Electronics'); $logo=setting('store_logo','');
$loggedUser=customer_user(); $cart=cart_contents();
$wa=preg_replace('/\D+/','',setting('whatsapp_number',''));
$facebook=setting('facebook_url',''); $instagram=setting('instagram_url',''); $tiktok=setting('tiktok_url','');
$searchQ=trim((string)($_GET['q']??'')); $navCategories=[];
try{$navCategories=$pdo->query('SELECT id,parent_id,name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();}catch(Throwable $e){}
$navTree=[]; foreach($navCategories as $nc){$pid=(int)($nc['parent_id']??0);$navTree[$pid][]=$nc;}
$navParentProducts=[]; try{$navParentProducts=$pdo->query('SELECT p.id,p.name,p.category_id FROM products p WHERE EXISTS (SELECT 1 FROM products v WHERE v.parent_product_id=p.id AND v.is_active=1) ORDER BY p.name')->fetchAll();}catch(Throwable $e){}
$navProductsByCategory=[]; foreach($navParentProducts as $np){$navProductsByCategory[(int)($np['category_id']??0)][]=$np;}
?>
<?php try{$pv=$pdo->prepare('INSERT INTO page_views(page_path,session_hash) VALUES(?,?)');$pv->execute([substr((string)($_SERVER['REQUEST_URI']??'/'),0,255),hash('sha256',session_id())]);}catch(Throwable $e){}?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?=e($metaDescription??setting('seo_description','Shop electronics, computers, phones and accessories in Kenya.'))?>">
<meta name="keywords" content="<?=e($metaKeywords??setting('seo_keywords','electronics Kenya, laptops, phones, accessories'))?>"><meta name="robots" content="index,follow">
<?php $favicon=setting('site_favicon',''); if($favicon): ?><link rel="icon" href="<?=e($favicon)?>" type="image/png"><?php else: ?><link rel="icon" href="assets/images/favicon.ico" sizes="any"><?php endif; ?>
<title><?=e($pageTitle)?> | <?=e($storeName)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/style.css?v=4.2.3" rel="stylesheet">
<script>try{document.documentElement.dataset.theme=localStorage.getItem('bc-theme')||'light';}catch(e){document.documentElement.dataset.theme='light';}</script>
</head><body>
<div class="store-promo text-center py-2"><?=e(setting('announcement_bar','Free shipping for orders above KSh 5,000'))?></div>
<div class="top-links border-bottom"><div class="container d-flex justify-content-between align-items-center">
 <div class="utility-links"><a href="about.php">About Us</a><a href="faq.php">FAQs</a><a href="contact.php">Contact</a>
  <a class="location-hover" href="locations.php" aria-label="Our location"><i class="bi bi-geo-alt-fill"></i><span><?=e(setting('business_location','Our Locations'))?></span></a>
 </div>
 <div class="top-socials d-flex gap-2 align-items-center">
  <?php if($facebook):?><a class="social-icon social-facebook" href="<?=e($facebook)?>" target="_blank" rel="noopener" aria-label="Facebook" title="Facebook"><i class="bi bi-facebook"></i></a><?php endif;?>
  <?php if($instagram):?><a class="social-icon social-instagram" href="<?=e($instagram)?>" target="_blank" rel="noopener" aria-label="Instagram" title="Instagram"><i class="bi bi-instagram"></i></a><?php endif;?>
  <?php if($tiktok):?><a class="social-icon social-tiktok" href="<?=e($tiktok)?>" target="_blank" rel="noopener" aria-label="TikTok" title="TikTok"><i class="bi bi-tiktok"></i></a><?php endif;?>
  <?php if($wa):?><a class="social-icon social-whatsapp" href="https://wa.me/<?=e($wa)?>" target="_blank" rel="noopener" aria-label="WhatsApp" title="WhatsApp"><i class="bi bi-whatsapp"></i></a><?php endif;?>
 </div></div></div>
<header class="store-header">
 <div class="header-main-wrap"><div class="container header-main">
  <a class="store-logo" href="index.php"><?php if($logo):?><img src="<?=e($logo)?>" alt="<?=e($storeName)?>"><?php else:?><span class="brand-mark"><?=e($storeName)?></span><?php endif;?></a>
  <form class="header-search" method="get" action="shop.php"><input type="search" name="q" value="<?=e($searchQ)?>" placeholder="Search products, brands or SKU..." aria-label="Search products"><button aria-label="Search"><i class="bi bi-search"></i></button></form>
  <div class="header-actions"><a class="account-link" href="<?=$loggedUser?'profile.php':'login.php'?>"><?php if($loggedUser&&!empty($loggedUser['profile_image'])):?><img class="header-avatar" src="<?=e($loggedUser['profile_image'])?>" alt="Profile"><?php else:?><i class="bi bi-person"></i><?php endif;?><span><?=$loggedUser?e(explode(' ',$loggedUser['full_name'])[0]):'Login'?></span></a>
  <?php if(!$loggedUser):?><a class="register-link" href="register.php" title="Register" aria-label="Register"><i class="bi bi-person-plus"></i><span>Register</span></a><?php endif;?>
  <a class="cart-link" href="cart.php"><i class="bi bi-cart3"></i><span><?=money($cart['subtotal'])?></span><b><?=$cart['count']?></b></a>
  <button class="theme-toggle" data-theme-toggle type="button" aria-label="Theme" title="Theme"><span aria-hidden="true">☾</span></button></div>
 </div></div>
 <nav class="category-nav" aria-label="Product categories"><div class="container nav-menu"><a class="nav-simple" href="index.php">Home</a><a class="nav-simple" href="shop.php">Shop</a>
 <?php foreach(($navTree[0]??[]) as $navCat): $children=$navTree[(int)$navCat['id']]??[]; $rootGroups=$navProductsByCategory[(int)$navCat['id']]??[]; $hasMenu=(bool)($children||$rootGroups); ?>
 <div class="nav-dropdown"><a class="nav-parent" href="shop.php?category=<?=(int)$navCat['id']?>"><?=e($navCat['name'])?><?php if($hasMenu):?><i class="bi bi-chevron-down nav-chevron" aria-hidden="true"></i><?php endif;?></a>
 <?php if($hasMenu):?><div class="nav-mega-menu"><div class="nav-mega-columns">
  <?php foreach($children as $child):$grand=$navTree[(int)$child['id']]??[];$groups=$navProductsByCategory[(int)$child['id']]??[];?>
   <div class="nav-mega-column"><a class="nav-mega-heading" href="shop.php?category=<?=(int)$child['id']?>"><?=e($child['name'])?></a>
    <?php foreach($grand as $leaf):?><a class="nav-mega-link" href="shop.php?category=<?=(int)$leaf['id']?>"><?=e($leaf['name'])?></a><?php endforeach;?>
    <?php foreach($groups as $group):?><a class="nav-mega-product" href="shop.php?parent_product=<?=(int)$group['id']?>"><?=e($group['name'])?></a><?php endforeach;?>
   </div>
  <?php endforeach;?>
  <?php if($rootGroups):?><div class="nav-mega-column"><span class="nav-mega-heading">Popular product groups</span><?php foreach($rootGroups as $group):?><a class="nav-mega-product" href="shop.php?parent_product=<?=(int)$group['id']?>"><?=e($group['name'])?></a><?php endforeach;?></div><?php endif;?>
 </div><a class="nav-view-all" href="shop.php?category=<?=(int)$navCat['id']?>">View all <?=e($navCat['name'])?> <i class="bi bi-arrow-right"></i></a></div><?php endif;?></div>
 <?php endforeach;?><a class="nav-simple" href="shop.php?sort=newest">New arrivals</a></div></nav>
</header><main>
