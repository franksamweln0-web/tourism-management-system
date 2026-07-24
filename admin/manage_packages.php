<?php
require_once '../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_package'])) {
        $stmt = $pdo->prepare("INSERT INTO tour_packages (package_name, destination, duration_days, price, max_capacity, description, inclusions, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['package_name'], $_POST['destination'], $_POST['duration_days'], $_POST['price'], $_POST['max_capacity'], $_POST['description'], $_POST['inclusions'], $_POST['image_url']]);
        header('Location: manage_packages.php?msg=added');
        exit();
    } elseif (isset($_POST['edit_package'])) {
        $stmt = $pdo->prepare("UPDATE tour_packages SET package_name=?, destination=?, duration_days=?, price=?, max_capacity=?, description=?, inclusions=?, image_url=?, status=? WHERE id=?");
        $stmt->execute([$_POST['package_name'], $_POST['destination'], $_POST['duration_days'], $_POST['price'], $_POST['max_capacity'], $_POST['description'], $_POST['inclusions'], $_POST['image_url'], $_POST['status'], $_POST['id']]);
        header('Location: manage_packages.php?msg=updated');
        exit();
    } elseif (isset($_POST['delete_package'])) {
        $stmt = $pdo->prepare("DELETE FROM tour_packages WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header('Location: manage_packages.php?msg=deleted');
        exit();
    }
}

$msg = $_GET['msg'] ?? '';
$packages = $pdo->query("SELECT * FROM tour_packages ORDER BY created_at DESC")->fetchAll();
$editPkg = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tour_packages WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editPkg = $stmt->fetch();
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar"><?php include 'sidebar.php'; ?></nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Tour Packages</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pkgModal">+ Add Package</button>
            </div>
            <?php if ($msg === 'added'): ?><div class="alert alert-success">Package added successfully!</div><?php endif; ?>
            <?php if ($msg === 'updated'): ?><div class="alert alert-success">Package updated successfully!</div><?php endif; ?>
            <?php if ($msg === 'deleted'): ?><div class="alert alert-success">Package deleted successfully!</div><?php endif; ?>
            <div class="table-responsive">
                <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Image</th><th>Name</th><th>Destination</th><th>Days</th><th>Price</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($packages as $p): ?>
                        <tr>
                            <td>
                                <?php if ($p['image_url']): ?>
                                <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['package_name']) ?>" style="width:60px;height:40px;object-fit:cover;border-radius:8px;">
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($p['package_name']) ?></strong></td>
                            <td><?= htmlspecialchars($p['destination']) ?></td>
                            <td><?= $p['duration_days'] ?></td>
                            <td>$<?= number_format($p['price'], 2) ?></td>
                            <td><?= $p['max_capacity'] ?></td>
                            <td><span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $p['status'] ?></span></td>
                            <td>
                                <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this package?')">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="delete_package" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="pkgModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5><?= $editPkg ? 'Edit' : 'Add' ?> Package</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $editPkg['id'] ?? '' ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Package Name</label>
                            <input type="text" name="package_name" class="form-control" value="<?= $editPkg['package_name'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Destination</label>
                            <input type="text" name="destination" class="form-control" value="<?= $editPkg['destination'] ?? '' ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Duration (Days)</label>
                            <input type="number" name="duration_days" class="form-control" value="<?= $editPkg['duration_days'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?= $editPkg['price'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Max Capacity</label>
                            <input type="number" name="max_capacity" class="form-control" value="<?= $editPkg['max_capacity'] ?? '' ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= $editPkg['description'] ?? '' ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Inclusions</label>
                        <textarea name="inclusions" class="form-control" rows="2"><?= $editPkg['inclusions'] ?? '' ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input type="url" name="image_url" class="form-control" value="<?= htmlspecialchars($editPkg['image_url'] ?? '') ?>" placeholder="https://images.unsplash.com/photo-...">
                        <small class="text-muted">Paste a high-quality image URL (Unsplash, Pexels, etc.)</small>
                    </div>
                    <?php if ($editPkg): ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= ($editPkg['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($editPkg['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="<?= $editPkg ? 'edit_package' : 'add_package' ?>" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editPkg): ?>
<script>document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('pkgModal')).show(); });</script>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
