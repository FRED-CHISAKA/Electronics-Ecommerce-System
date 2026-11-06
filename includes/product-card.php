<?php
$discount = !empty($product['compare_at_price']) && (float)$product['compare_at_price'] > (float)$product['price'] ? (int)round((1-(float)$product['price']/(float)$product['compare_at_price'])*100) : 0;
$stock=(int)($product['stock_quantity']??0);
?>
<article class="product-card h-100">
 <div class="product-image-wrap">
  <a href="product.php?slug=<?=e($product['slug'])?>" class="product-image" aria-label="View <?=e($product['name'])?>">
   <?php if(!empty($product['image_path'])):?><img src="<?=e($product['image_path'])?>" alt="<?=e($product['name'])?>" loading="lazy"><?php else:?><div class="product-placeholder"><span>BC</span></div><?php endif;?>
  </a>
  <?php if($discount>0):?><span class="product-badge discount-badge">-<?=$discount?>%</span><?php endif;?>
  <?php if(!empty($product['is_new'])):?><span class="product-badge new-badge">NEW</span><?php endif;?>
 </div>
 <div class="product-card-body">
  <?php if(!empty($product['sku'])):?><div class="product-sku">SKU <?=e($product['sku'])?></div><?php endif;?>
  <h3 class="product-card-title"><a href="product.php?slug=<?=e($product['slug'])?>"><?=e($product['name'])?></a></h3>
  <div class="product-prices"><strong><?=money($product['price'])?></strong><?php if($discount>0):?><del><?=money($product['compare_at_price'])?></del><?php endif;?></div>
  <div class="stock-label <?=$stock>0?'available':'unavailable'?>"><?=$stock<=0?'Out of stock':($stock<=5?'Only '.$stock.' left!':'In stock')?></div>
  <div class="product-card-actions"><?php if($stock>0):?><form method="post" action="cart-action.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="product_id" value="<?=(int)$product['id']?>"><input type="hidden" name="quantity" value="1"><input type="hidden" name="go_checkout" value="1"><button class="btn btn-outline-primary" type="submit">Quick Shop</button></form><?php else:?><button class="btn btn-outline-primary" disabled>Unavailable</button><?php endif;?><?php if($stock>0):?><form method="post" action="cart-action.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="product_id" value="<?=(int)$product['id']?>"><input type="hidden" name="quantity" value="1"><button class="btn btn-primary" type="submit">Add to Cart</button></form><?php else:?><button class="btn btn-primary" disabled>Unavailable</button><?php endif;?></div>
 </div>
</article>
