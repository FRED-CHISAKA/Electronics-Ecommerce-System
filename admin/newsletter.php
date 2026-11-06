<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
admin_require_login();
$notice='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 admin_csrf_check();
 $action=(string)($_POST['action']??'');
 if($action==='send'){
  $ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['subscriber_ids']??[])),fn($v)=>$v>0)));
  $subject=trim((string)($_POST['subject']??''));$body=trim((string)($_POST['message']??''));
  if(!$ids||$subject===''||$body==='')$error='Choose at least one subscriber and enter an email subject and message.';
  elseif(!filter_var(setting_value_safe('business_email'),FILTER_VALIDATE_EMAIL))$error='Set a valid business email in Store Settings before sending newsletters.';
  else{
   $marks=implode(',',array_fill(0,count($ids),'?'));$st=$pdo->prepare("SELECT email FROM newsletter_subscribers WHERE status='active' AND id IN ($marks)");$st->execute($ids);$emails=$st->fetchAll(PDO::FETCH_COLUMN);
   $from=setting_value_safe('business_email');$sent=0;
   foreach($emails as $email){$headers=['From: BlueCircuit Electronics <'.$from.'>','Reply-To: '.$from,'MIME-Version: 1.0','Content-Type: text/plain; charset=UTF-8','X-Mailer: PHP/'.phpversion()];if(@mail((string)$email,$subject,$body,implode("\r\n",$headers)))$sent++;}
   $notice="Email accepted by the server for {$sent} of ".count($emails)." selected subscriber(s). Check your hosting mail logs/deliverability.";
  }
 }elseif($action==='unsubscribe'){$id=(int)($_POST['id']??0);$pdo->prepare("UPDATE newsletter_subscribers SET status='unsubscribed' WHERE id=?")->execute([$id]);$notice='Subscriber marked as unsubscribed.';}
}
function setting_value_safe(string $key): string {global $pdo;$s=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');$s->execute([$key]);return (string)($s->fetchColumn()?:'');}
$rows=$pdo->query('SELECT id,email,status,subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC')->fetchAll();
admin_page_start('Newsletter subscribers');
?>
<div class="panel mb-4"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><h2 class="h5 mb-0">Subscriber list</h2><span class="badge text-bg-primary"><?=count(array_filter($rows,fn($r)=>$r['status']==='active'))?> active</span></div><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="send"><div class="table-responsive"><table class="table align-middle"><thead><tr><th><input type="checkbox" id="selectAll"></th><th>Email address</th><th>Status</th><th>Subscribed</th><th></th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?php if($r['status']==='active'):?><input type="checkbox" name="subscriber_ids[]" value="<?=(int)$r['id']?>" class="subscriber-check"><?php endif;?></td><td><?=e($r['email'])?></td><td><span class="badge <?=$r['status']==='active'?'text-bg-success':'text-bg-secondary'?>"><?=e(ucfirst($r['status']))?></span></td><td><?=e($r['subscribed_at'])?></td><td><?php if($r['status']==='active'):?><button class="btn btn-sm btn-outline-secondary" type="submit" form="unsubscribe-<?=$r['id']?>">Unsubscribe</button><?php endif;?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="5" class="text-center text-secondary">No newsletter subscribers yet.</td></tr><?php endif;?></tbody></table></div><hr><h3 class="h6">Send newsletter</h3><div class="row g-3"><div class="col-12"><label class="form-label">Subject</label><input class="form-control" name="subject" maxlength="190" required></div><div class="col-12"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="6" required placeholder="Write your newsletter message..."></textarea></div><div class="col-12"><button class="btn btn-primary" type="submit"><i class="bi bi-send me-1"></i>Send to selected subscribers</button></div></div></form></div>
<?php foreach($rows as $r):if($r['status']!=='active')continue;?><form id="unsubscribe-<?=$r['id']?>" method="post" class="d-none"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="unsubscribe"><input type="hidden" name="id" value="<?=(int)$r['id']?>"></form><?php endforeach;?>
<?php if($notice):?><div class="alert alert-success"><?=e($notice)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<script>document.getElementById('selectAll')?.addEventListener('change',e=>document.querySelectorAll('.subscriber-check').forEach(c=>c.checked=e.target.checked));</script>
<?php admin_page_end(); ?>
