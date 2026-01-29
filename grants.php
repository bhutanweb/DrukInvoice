<?php
$pageTitle = 'Grants | Bhutan Foundation Grants';
require __DIR__ . '/db.php';

$statusFilter = $_GET['status'] ?? 'All';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM grants";
$conditions = [];
$params = [];

if ($statusFilter !== 'All') {
    $conditions[] = 'status = :status';
    $params['status'] = $statusFilter;
}

if ($search !== '') {
    $conditions[] = '(title LIKE :search OR grantee LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY created_at DESC';

$grants = fetch_all($pdo, $sql, $params);

require __DIR__ . '/partials_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Grant Portfolio</h1>
        <p class="text-muted mb-0">Track, update, and close Bhutan Foundation grants.</p>
    </div>
    <a class="btn btn-primary" href="grant_form.php">+ Add Grant</a>
</div>

<form class="row g-3 align-items-end mb-4" method="get">
    <div class="col-md-4">
        <label class="form-label">Search</label>
        <input class="form-control" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Grant title or grantee">
    </div>
    <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
            <?php foreach (['All','Pending','Active','Paused','Completed','Closed'] as $status) : ?>
                <option value="<?= $status ?>" <?= $status === $statusFilter ? 'selected' : '' ?>><?= $status ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-header">All Grants</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Grant</th>
                    <th>Grantee</th>
                    <th>Funding</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>End Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$grants) : ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No grants found.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($grants as $grant) : ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($grant['title']) ?></td>
                            <td><?= htmlspecialchars($grant['grantee']) ?></td>
                            <td>BTN <?= number_format((float) $grant['amount'], 2) ?></td>
                            <td><span class="badge bg-info-subtle text-dark badge-status"><?= htmlspecialchars($grant['status']) ?></span></td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?= (int) $grant['progress_percent'] ?>%"></div>
                                </div>
                                <small class="text-muted"><?= (int) $grant['progress_percent'] ?>%</small>
                            </td>
                            <td><?= htmlspecialchars($grant['end_date']) ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="grant_view.php?id=<?= $grant['id'] ?>">View</a>
                                <a class="btn btn-sm btn-outline-secondary" href="grant_form.php?id=<?= $grant['id'] ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-danger" href="grant_delete.php?id=<?= $grant['id'] ?>" onclick="return confirm('Delete this grant?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
