<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
admin_require_login();
$action=(string)($_GET['action']??'');$id=(int)($_GET['id']??0);$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 admin_csrf_check();$mode=(string)($_POST['mode']??'save');$target=(int)($_POST['id']??0);
 if($mode==='delete'){
  if($target===(int)$_SESSION['admin_id'])$error='You cannot delete the administrator account you are currently using.';
  else{$pdo->prepare("DELETE FROM users WHERE id=? AND role='admin'")->execute([$target]);$_SESSION['flash']='Administrator deleted.';redirect('admins.php');}
 }else{
  $name=trim((string)($_POST['full_name']??''));$email=trim((string)($_POST['email']??''));$password=(string)($_POST['password']??'');$status=($_POST['status']??'active')==='disabled'?'disabled':'active';
  if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL))$error='Enter a valid name and email address.';
  elseif($target===0&&strlen($password)<10)$error='New administrator passwords must be at least 10 characters.';
  elseif($password!==''&&strlen($password)<10)$error='Passwords must be at least 10 characters.';
  else{try{if($target>0){if($password!=='')$pdo->prepare("UPDATE users SET full_name=?,email=?,status=?,password_hash=? WHERE id=? AND role='admin'")->execute([$name,$email,$status,password_hash($password,PASSWORD_DEFAULT),$target]);else $pdo->prepare("UPDATE users SET full_name=?,email=?,status=? WHERE id=? AND role='admin'")->execute([$name,$email,$status,$target]);}else{$pdo->prepare("INSERT INTO users(full_name,email,password_hash,role,status) VALUES(?,?,?,'admin',?)")->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$status]);}$_SESSION['flash']='Administrator saved.';redirect('admins.php');}catch(PDOException $e){$error=$e->getCode()==='23000'?'That email address is already in use.':'Could not save the administrator.';}}
 }
}
$edit=null;if($action==='edit'&&$id>0){$st=$pdo->prepare("SELECT id,full_name,email,status FROM users WHERE id=? AND role='admin'");$st->execute([$id]);$edit=$st->fetch();if(!$edit){http_response_code(404);exit('Administrator not found.');}}
$rows=$pdo->query("SELECT id,full_name,email,status,last_login_at,created_at FROM users WHERE role='admin' ORDER BY created_at DESC")->fetchAll();
admin_page_start('Administrators');
?>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="panel mb-4"><h2 class="h5"><?=$edit?'Edit administrator':'Add administrator'?></h2><form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="mode" value="save"><input type="hidden" name="id" value="<?=(int)($edit['id']??0)?>"><div class="col-md-6"><label class="form-label">Full name</label><input class="form-control" name="full_name" required value="<?=e($edit['full_name']??'')?>"></div><div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required value="<?=e($edit['email']??'')?>"></div><div class="col-md-6"><label class="form-label">Password <?=$edit?'(leave blank to keep current)':'(minimum 10 characters)'?></label><input class="form-control" type="password" name="password" <?=$edit?'':'required'?> minlength="10" autocomplete="new-password"></div><div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active" <?=($edit['status']??'active')==='active'?'selected':''?>>Active</option><option value="disabled" <?=($edit['status']??'')==='disabled'?'selected':''?>>Disabled</option></select></div><div class="col-12"><button class="btn btn-primary">Save administrator</button><?php if($edit):?> <a class="btn btn-outline-secondary" href="admins.php">Cancel</a><?php endif;?></div></form></div>
<div class="panel"><h2 class="h5">Administrator accounts</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Last login</th><th>Actions</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['full_name'])?></td><td><?=e($r['email'])?></td><td><?=e(ucfirst($r['status']))?></td><td><?=e($r['last_login_at']??'Never')?></td><td><div class="d-flex gap-2"><a class="btn btn-sm btn-outline-primary" href="admins.php?action=edit&id=<?=(int)$r['id']?>">Edit</a><?php if((int)$r['id']!==(int)$_SESSION['admin_id']):?><form method="post" onsubmit="return confirm('Delete this administrator?')"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="mode" value="delete"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form><?php endif;?></div></td></tr><?php endforeach;?></tbody></table></div><p class="small text-secondary mb-0">Only administrator accounts are shown. Your currently signed-in account cannot be deleted from this page.</p></div>
<?php admin_page_end(); ?>
