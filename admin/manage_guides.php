<?php
require_once '../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_guide'])) {
    $stmt = $pdo->prepare("UPDATE guides SET full_name=?, languages=?, specialization=?, contact_number=?, availability=? WHERE id=?");
    $stmt->execute([$_POST['full_name'], $_POST['languages'], $_POST['specialization'], $_POST['contact_number'], $_POST['availability'], $_POST['id']]);
    header('Location: manage_guides.php?msg=updated');
    exit();
}

$guides = $pdo->query("SELECT g.*, u.username, u.email FROM guides g JOIN users u ON g.user_id = u.id ORDER BY g.created_at DESC")->fetchAll();
$assignments = $pdo->query("SELECT ga.*, g.full_name as guide_name, b.booking_reference, p.package_name FROM guide_assignments ga JOIN guides g ON ga.guide_id = g.id JOIN bookings b ON ga.booking_id = b.id JOIN tour_packages p ON b.package_id = p.id ORDER BY ga.assignment_date DESC")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar"><?php include 'sidebar.php'; ?></nav>
        <main class="col-md-10 ms-sm-auto px-4">
            <div class="pt-3 pb-2 mb-3 border-bottom"><h1>Guide Management</h1></div>
            <?php if (isset($_GET['msg'])): ?><div class="alert alert-success">Guide updated successfully!</div><?php endif; ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Name</th><th>Username</th><th>Languages</th><th>Specialization</th><th>Contact</th><th>Availability</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($guides as $g): ?>
                        <tr>
                            <td><?= htmlspecialchars($g['full_name']) ?></td>
                            <td><?= htmlspecialchars($g['username']) ?></td>
                            <td><?= htmlspecialchars($g['languages'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($g['specialization'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($g['contact_number'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= $g['availability'] === 'available' ? 'success' : ($g['availability'] === 'occupied' ? 'warning' : 'secondary') ?>"><?= $g['availability'] ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#guideModal<?= $g['id'] ?>">Edit</button>
                            </td>
                        </tr>
                        <div class="modal fade" id="guideModal<?= $g['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header"><h5>Edit Guide</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                            <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($g['full_name']) ?>" required></div>
                                            <div class="mb-3"><label class="form-label">Languages</label><input type="text" name="languages" class="form-control" value="<?= htmlspecialchars($g['languages'] ?? '') ?>"></div>
                                            <div class="mb-3"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($g['specialization'] ?? '') ?>"></div>
                                            <div class="mb-3"><label class="form-label">Contact</label><input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($g['contact_number'] ?? '') ?>"></div>
                                            <div class="mb-3"><label class="form-label">Availability</label>
                                                <select name="availability" class="form-control">
                                                    <option value="available" <?= $g['availability'] === 'available' ? 'selected' : '' ?>>Available</option>
                                                    <option value="occupied" <?= $g['availability'] === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                                                    <option value="unavailable" <?= $g['availability'] === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer"><button type="submit" name="update_guide" class="btn btn-primary">Update</button></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h4 class="mt-4">Guide Assignments</h4>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Guide</th><th>Booking</th><th>Package</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['guide_name']) ?></td>
                            <td><?= htmlspecialchars($a['booking_reference']) ?></td>
                            <td><?= htmlspecialchars($a['package_name']) ?></td>
                            <td><?= $a['assignment_date'] ?></td>
                            <td><span class="badge bg-<?= $a['status'] === 'assigned' ? 'primary' : ($a['status'] === 'completed' ? 'success' : 'danger') ?>"><?= $a['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
