<?php
require_once '../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO itineraries (package_id, day_number, activity, location, timing, notes) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_POST['package_id'], $_POST['day_number'], $_POST['activity'], $_POST['location'], $_POST['timing'], $_POST['notes']]);
        header('Location: manage_itineraries.php?msg=added');
        exit();
    } elseif (isset($_POST['edit'])) {
        $stmt = $pdo->prepare("UPDATE itineraries SET package_id=?, day_number=?, activity=?, location=?, timing=?, notes=? WHERE id=?");
        $stmt->execute([$_POST['package_id'], $_POST['day_number'], $_POST['activity'], $_POST['location'], $_POST['timing'], $_POST['notes'], $_POST['id']]);
        header('Location: manage_itineraries.php?msg=updated');
        exit();
    } elseif (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM itineraries WHERE id=?")->execute([$_POST['id']]);
        header('Location: manage_itineraries.php?msg=deleted');
        exit();
    }
}

$packages = $pdo->query("SELECT id, package_name FROM tour_packages WHERE status='active'")->fetchAll();
$itineraries = $pdo->query("SELECT i.*, p.package_name FROM itineraries i JOIN tour_packages p ON i.package_id = p.id ORDER BY p.package_name, i.day_number")->fetchAll();
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM itineraries WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar"><?php include 'sidebar.php'; ?></nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Itinerary Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itModal">+ Add Itinerary</button>
            </div>
            <?php if ($msg = $_GET['msg'] ?? ''): ?>
                <div class="alert alert-success">Itinerary <?= $msg ?>!</div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Package</th><th>Day</th><th>Activity</th><th>Location</th><th>Timing</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($itineraries as $i): ?>
                        <tr>
                            <td><?= htmlspecialchars($i['package_name']) ?></td>
                            <td>Day <?= $i['day_number'] ?></td>
                            <td><?= htmlspecialchars($i['activity']) ?></td>
                            <td><?= htmlspecialchars($i['location'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($i['timing'] ?? '-') ?></td>
                            <td>
                                <a href="?edit=<?= $i['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="id" value="<?= $i['id'] ?>">
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

<div class="modal fade" id="itModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5><?= $editItem ? 'Edit' : 'Add' ?> Itinerary Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $editItem['id'] ?? '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Tour Package</label>
                        <select name="package_id" class="form-control" required>
                            <?php foreach ($packages as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($editItem['package_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['package_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Day Number</label><input type="number" name="day_number" class="form-control" value="<?= $editItem['day_number'] ?? '' ?>" required></div>
                    <div class="mb-3"><label class="form-label">Activity</label><textarea name="activity" class="form-control" rows="2" required><?= $editItem['activity'] ?? '' ?></textarea></div>
                    <div class="mb-3"><label class="form-label">Location</label><input type="text" name="location" class="form-control" value="<?= $editItem['location'] ?? '' ?>"></div>
                    <div class="mb-3"><label class="form-label">Timing</label><input type="text" name="timing" class="form-control" value="<?= $editItem['timing'] ?? '' ?>" placeholder="e.g., 8:00 AM - 12:00 PM"></div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"><?= $editItem['notes'] ?? '' ?></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" name="<?= $editItem ? 'edit' : 'add' ?>" class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>
<?php if ($editItem): ?><script>document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('itModal')).show(); });</script><?php endif; ?>
<?php include '../includes/footer.php'; ?>
