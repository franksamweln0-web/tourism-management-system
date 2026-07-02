<?php
require_once '../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO accommodations (name, location, contact_phone, contact_email, room_capacity, price_per_night, description) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['name'], $_POST['location'], $_POST['contact_phone'], $_POST['contact_email'], $_POST['room_capacity'], $_POST['price_per_night'], $_POST['description']]);
        header('Location: manage_accommodations.php?msg=added');
        exit();
    } elseif (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("UPDATE accommodations SET name=?, location=?, contact_phone=?, contact_email=?, room_capacity=?, price_per_night=?, description=?, status=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['location'], $_POST['contact_phone'], $_POST['contact_email'], $_POST['room_capacity'], $_POST['price_per_night'], $_POST['description'], $_POST['status'], $_POST['id']]);
        header('Location: manage_accommodations.php?msg=updated');
        exit();
    } elseif (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM accommodations WHERE id=?")->execute([$_POST['id']]);
        header('Location: manage_accommodations.php?msg=deleted');
        exit();
    }
}

$accommodations = $pdo->query("SELECT * FROM accommodations ORDER BY created_at DESC")->fetchAll();
$editAcc = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM accommodations WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $editAcc = $stmt->fetch();
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar"><?php include 'sidebar.php'; ?></nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Accommodations</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#accModal">+ Add Accommodation</button>
            </div>
            <?php if ($msg = $_GET['msg'] ?? ''): ?>
                <div class="alert alert-success">Accommodation <?= $msg ?>!</div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Name</th><th>Location</th><th>Capacity</th><th>Price/Night</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($accommodations as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['name']) ?></td>
                            <td><?= htmlspecialchars($a['location']) ?></td>
                            <td><?= $a['room_capacity'] ?></td>
                            <td>$<?= number_format($a['price_per_night'], 2) ?></td>
                            <td><span class="badge bg-<?= $a['status'] === 'active' ? 'success' : 'secondary' ?>"><?= $a['status'] ?></span></td>
                            <td>
                                <a href="?edit=<?= $a['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="accModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5><?= $editAcc ? 'Edit' : 'Add' ?> Accommodation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $editAcc['id'] ?? '' ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= $editAcc['name'] ?? '' ?>" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Location</label><input type="text" name="location" class="form-control" value="<?= $editAcc['location'] ?? '' ?>" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Contact Phone</label><input type="text" name="contact_phone" class="form-control" value="<?= $editAcc['contact_phone'] ?? '' ?>"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Contact Email</label><input type="email" name="contact_email" class="form-control" value="<?= $editAcc['contact_email'] ?? '' ?>"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Room Capacity</label><input type="number" name="room_capacity" class="form-control" value="<?= $editAcc['room_capacity'] ?? '' ?>" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Price per Night ($)</label><input type="number" step="0.01" name="price_per_night" class="form-control" value="<?= $editAcc['price_per_night'] ?? '' ?>" required></div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?= ($editAcc['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($editAcc['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= $editAcc['description'] ?? '' ?></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" name="<?= $editAcc ? 'edit' : 'add' ?>" class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>
<?php if ($editAcc): ?><script>document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('accModal')).show(); });</script><?php endif; ?>
<?php include '../includes/footer.php'; ?>
