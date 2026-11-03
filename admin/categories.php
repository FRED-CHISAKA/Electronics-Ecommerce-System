<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
admin_require_login();
$id = (int) ($_GET['id'] ?? 0);
$edit = $id > 0;
$row = ['name'=>'','slug'=>'','description'=>'','parent_id'=>null,'is_active'=>1,'show_on_home'=>0];
$parentOptions=$pdo->query('SELECT id,name,parent_id FROM categories ORDER BY name')->fetchAll();
$errors = [];
if ($edit) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Category not found.'); }
    $row = array_merge($row, $found);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_check();
    $action = (string) ($_POST['form_action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['flash'] = 'Category deleted. Products in it will become uncategorised.';
        redirect('categories.php');
    }
    $name = trim((string) ($_POST['name'] ?? ''));
    $slug = slugify(trim((string) ($_POST['slug'] ?? '')) ?: $name);
    $description = trim((string) ($_POST['description'] ?? ''));
    $active = isset($_POST['is_active']) ? 1 : 0;
    $showOnHome = isset($_POST['show_on_home']) ? 1 : 0;
    $parentId = ($_POST['parent_id']??'')!=='' ? (int)$_POST['parent_id'] : null;
    if($parentId===$id && $id>0)$errors[]='A category cannot be its own parent.';
    $row = ['name'=>$name,'slug'=>$slug,'description'=>$description,'parent_id'=>$parentId,'is_active'=>$active,'show_on_home'=>$showOnHome];
    if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Name is required (maximum 120 characters).';
    if (!$errors) {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE categories SET name=?, slug=?, description=?, parent_id=?, is_active=?, show_on_home=? WHERE id=?');
                $stmt->execute([$name,$slug,$description ?: null,$parentId,$active,$showOnHome,$id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (name,slug,description,parent_id,is_active,show_on_home) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$name,$slug,$description ?: null,$parentId,$active,$showOnHome]);
            }
            $_SESSION['flash'] = 'Category saved.';
            redirect('categories.php');
        } catch (PDOException $exception) {
            if (str_contains($exception->getMessage(), 'show_on_home')) {
                $errors[] = 'Please run database/migrations/20261027_category_home_sections.sql in phpMyAdmin first.';
            } else {
                $errors[] = $exception->getCode() === '23000' ? 'That slug is already in use.' : 'Could not save category. Check the PHP error log.';
            }
        }
    }
}
try {
    $rows = $pdo->query('SELECT c.*, parent.name AS parent_name, COUNT(p.id) AS product_count FROM categories c LEFT JOIN categories parent ON parent.id=c.parent_id LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY COALESCE(parent.name,c.name),c.name')->fetchAll();
} catch (Throwable $exception) { $rows = []; $errors[] = 'Could not load categories. Check that the category home-section migration has been applied.'; }
admin_page_start($edit ? 'Edit category' : 'Categories');
if ($errors): ?><div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<div class="row g-3"><div class="col-lg-4"><div class="panel"><h2 class="h5"><?= $edit ? 'Edit category' : 'Add category' ?></h2>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $id ?>">
<div class="mb-3"><label class="form-label">Name *</label><input class="form-control" name="name" maxlength="120" required value="<?= e((string)$row['name']) ?>"></div>
<div class="mb-3"><label class="form-label">Slug</label><input class="form-control" name="slug" value="<?= e((string)$row['slug']) ?>"><small class="text-secondary">Leave blank to generate it from the name.</small></div>
<div class="mb-3"><label class="form-label">Parent category</label><select class="form-select" name="parent_id"><option value="">None — top-level category</option><?php foreach($parentOptions as $po):if((int)$po['id']===$id)continue;?><option value="<?=(int)$po['id']?>" <?=((string)($row['parent_id']??'')===(string)$po['id'])?'selected':''?>><?=e($po['name'])?></option><?php endforeach;?></select><div class="form-text">Choose a parent to make this a subcategory. A child can also have its own children.</div></div><div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?= e((string)($row['description'] ?? '')) ?></textarea></div>
<div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" id="active" <?= !empty($row['is_active']) ? 'checked' : '' ?>><label class="form-check-label" for="active">Active / visible in shop</label></div>
<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="show_on_home" id="show_on_home" <?= !empty($row['show_on_home']) ? 'checked' : '' ?>><label class="form-check-label" for="show_on_home">Show this category and its products on the home page</label><div class="form-text">When enabled, up to four active products in this category appear in their own home-page section.</div></div>
<button class="btn btn-primary">Save category</button><?php if ($edit): ?> <a class="btn btn-outline-secondary" href="categories.php">Cancel</a><?php endif; ?></form></div></div>
<div class="col-lg-8"><div class="panel"><div class="table-responsive"><table class="table"><thead><tr><th>Category</th><th>Products</th><th>Home page</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="5" class="text-secondary text-center py-4">No categories found.</td></tr><?php else: foreach ($rows as $r): ?><tr><td><strong><?= e($r['name']) ?></strong><?php if(!empty($r['parent_name'])):?><div class="small text-secondary">Under <?=e($r['parent_name'])?></div><?php endif;?><div class="small text-secondary"><?= e($r['slug']) ?></div></td><td><?= (int)$r['product_count'] ?></td><td><?= !empty($r['show_on_home']) ? '<span class="badge text-bg-primary">Shown</span>' : '<span class="badge text-bg-secondary">Hidden</span>' ?></td><td><?= $r['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Hidden</span>' ?></td><td><div class="d-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="categories.php?id=<?= (int)$r['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this category? Products will remain but lose this category.');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="form_action" value="delete"><button class="btn btn-sm btn-outline-danger">Delete</button></form></div></td></tr><?php endforeach; endif; ?>
</tbody></table></div></div></div></div><?php admin_page_end(); ?>
