<?php
declare(strict_types=1);
require_once __DIR__.'/includes/store.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) { http_response_code(419); exit('Invalid request. Please refresh and try again.'); }
$email=filter_var(trim((string)($_POST['email']??'')),FILTER_VALIDATE_EMAIL);
if(!$email){$_SESSION['newsletter_message']='Please enter a valid email address.'; redirect('index.php#newsletter');}
try{$s=$pdo->prepare("INSERT INTO newsletter_subscribers(email) VALUES(?) ON DUPLICATE KEY UPDATE email=VALUES(email)");$s->execute([$email]);$_SESSION['newsletter_message']='Thank you for subscribing!';}catch(Throwable $e){$_SESSION['newsletter_message']='Subscription is temporarily unavailable. Please try again later.';}
redirect('index.php#newsletter');
