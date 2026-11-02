<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function customer_user(): ?array {
    global $pdo;
    if (empty($_SESSION['user_id'])) return null;
    $s=$pdo->prepare("SELECT id,full_name,email,phone,profile_image,role,status FROM users WHERE id=? LIMIT 1"); $s->execute([(int)$_SESSION['user_id']]); $u=$s->fetch();
    if (!$u || $u['role']!=='customer' || $u['status']!=='active') { unset($_SESSION['user_id']); return null; }
    return $u;
}
function require_customer(): array { $u=customer_user(); if(!$u) redirect('login.php?next=profile.php'); return $u; }
function cart_contents(): array {
 global $pdo; $cart=$_SESSION['cart']??[]; if(!is_array($cart))$cart=[]; $ids=array_values(array_unique(array_filter(array_map('intval',array_keys($cart)),fn($v)=>$v>0)));
 if(!$ids)return ['items'=>[],'count'=>0,'subtotal'=>0.0];
 $marks=implode(',',array_fill(0,count($ids),'?')); $s=$pdo->prepare("SELECT id,name,slug,price,stock_quantity,image_path,is_active FROM products WHERE id IN ($marks)");$s->execute($ids);$products=$s->fetchAll();$items=[];$count=0;$subtotal=0.0;
 foreach($products as $p){$id=(int)$p['id'];$qty=max(1,(int)($cart[$id]??1));if(!(int)$p['is_active']||$qty>(int)$p['stock_quantity'])$qty=max(0,min($qty,(int)$p['stock_quantity']));if($qty===0)continue;$line=(float)$p['price']*$qty;$p['quantity']=$qty;$p['line_total']=$line;$items[]=$p;$count+=$qty;$subtotal+=$line;}
 return ['items'=>$items,'count'=>$count,'subtotal'=>$subtotal];
}
function cart_add(int $productId,int $qty=1): void { global $pdo; $s=$pdo->prepare('SELECT id,stock_quantity,is_active FROM products WHERE id=?');$s->execute([$productId]);$p=$s->fetch();if(!$p||!(int)$p['is_active']||(int)$p['stock_quantity']<1)throw new RuntimeException('This product is currently unavailable.');$cart=$_SESSION['cart']??[];$new=max(1,(int)($cart[$productId]??0)+max(1,$qty));if($new>(int)$p['stock_quantity'])throw new RuntimeException('The requested quantity exceeds available stock.');$cart[$productId]=$new;$_SESSION['cart']=$cart; }
function setting(string $key,string $fallback=''): string { global $pdo; $s=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');$s->execute([$key]);$v=$s->fetchColumn();return $v===false?$fallback:(string)$v; }
