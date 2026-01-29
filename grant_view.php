<?php
$pageTitle = 'Grant Details | Bhutan Foundation Grants';
require __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$grant = $id ? fetch_one($pdo, 'SELECT * FROM grants WHERE id = :id', ['id' => $id]) : null;

if (!$grant) {
    header('Location: grants.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_update'])) {
        $summary = trim($_POST['summary'] ?? '');
        $progress = (int) ($_POST['update_progress'] ?? $grant['progress_percent']);
        $updateDate = $_POST['update_date'] ?? date('Y-m-d');

        if ($summary !== '') {
            execute_query($pdo, "
                INSERT INTO grant_updates (grant_id, update_date, summary, progress_percent)
                VALUES (:grant_id, :update_date, :summary, :progress_percent)
            ", [
                'grant_id' => $grant['id'],
                'update_date' => $updateDate,
                'summary' => $summary,
                'progress_percent' => $progress,
            ]);

            execute_query($pdo, "
                UPDATE grants SET progress_percent = :progress WHERE id = :id
            ", [
                'progress' => $progress,
                'id' => $grant['id'],
            ]);

            execute_query($pdo, "
                INSERT INTO notifications (grant_id, message, type)
                VALUES (:grant_id, :message, 'Info')
            ", [
                'grant_id' => $grant['id'],
                'message' => 'Progress update logged at ' . $progress . '%.',
            ]);
        }
    }

    if (isset($_POST['close_grant'])) {
        execute_query($pdo, "
            UPDATE grants SET status = 'Closed', progress_percent = 100 WHERE id = :id
        ", ['id' => $grant['id']]);

        execute_query($pdo, "
            INSERT INTO notifications (grant_id, message, type)
            VALUES (:grant_id, :message, 'Alert')
        ", [
            'grant_id' => $grant['id'],
            'message' => 'Grant closed and archived.',
        ]);
    }

    header('Location: grant_view.php?id=' . $grant['id']);
    exit;
}

$updates = fetch_all($pdo, "
    SELECT * FROM grant_updates
    WHERE grant_id = :grant_id
    ORDER BY update_date DESC, created_at DESC
", ['grant_id' => $grant['id']]);

require __DIR__ . '/partials_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($grant['title']) ?></h1>
        <p class="text-muted mb-0">Grant ID #<?= $grant['id'] ?> · <?= htmlspecialchars($grant['status']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="grants.php">Back</a>
        <a class="btn btn-primary" href="grant_form.php?id=<?= $grant['id'] ?>">Edit Grant</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header">Grant Overview</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="text-muted">Grantee</div>
                        <div class="fw-semibold"><?= htmlspecialchars($grant['grantee']) ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="text-muted">Funding Amount</div>
                        <div class="fw-semibold">BTN <?= number_format((float) $grant['amount'], 2) ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="text-muted">Start Date</div>
                        <div class="fw-semibold"><?= htmlspecialchars($grant['start_date']) ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="text-muted">End Date</div>
                        <div class="fw-semibold"><?= htmlspecialchars($grant['end_date']) ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="text-muted">Progress</div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?= (int) $grant['progress_percent'] ?>%"></div>
                        </div>
                        <small class="text-muted"><?= (int) $grant['progress_percent'] ?>%</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="text-muted">Status</div>
                        <span class="badge bg-info-subtle text-dark badge-status"><?= htmlspecialchars($grant['status']) ?></span>
                    </div>
                    <div class="col-12">
                        <div class="text-muted">Narrative</div>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($grant['description'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">Progress Updates</div>
            <div class="card-body">
                <?php if (!$updates) : ?>
                    <p class="text-muted">No updates logged yet.</p>
                <?php else : ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($updates as $update) : ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong><?= htmlspecialchars($update['update_date']) ?></strong>
                                    <span class="text-muted"><?= (int) $update['progress_percent'] ?>%</span>
                                </div>
                                <div><?= htmlspecialchars($update['summary']) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header">Log Progress Update</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Update Date</label>
                        <input class="form-control" type="date" name="update_date" value="<?= htmlspecialchars(date('Y-m-d')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Progress (%)</label>
                        <input class="form-control" type="number" name="update_progress" min="0" max="100" value="<?= (int) $grant['progress_percent'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Summary</label>
                        <textarea class="form-control" name="summary" rows="4" placeholder="Milestones achieved, spend progress, risks"></textarea>
                    </div>
                    <button class="btn btn-outline-primary" type="submit" name="add_update">Add Update</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">Grant Closure</div>
            <div class="card-body">
                <p class="text-muted">Close the grant once all deliverables, reporting, and payments are complete.</p>
                <form method="post" onsubmit="return confirm('Close this grant and mark as archived?')">
                    <button class="btn btn-danger" type="submit" name="close_grant">Close Grant</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
