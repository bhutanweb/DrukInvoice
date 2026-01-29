<?php
$pageTitle = 'Grant Form | Bhutan Foundation Grants';
require __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$grant = null;

if ($id) {
    $grant = fetch_one($pdo, 'SELECT * FROM grants WHERE id = :id', ['id' => $id]);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'grantee' => trim($_POST['grantee'] ?? ''),
        'amount' => (float) ($_POST['amount'] ?? 0),
        'status' => $_POST['status'] ?? 'Pending',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? '',
        'progress_percent' => (int) ($_POST['progress_percent'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
    ];

    if ($data['title'] === '') {
        $errors[] = 'Grant title is required.';
    }
    if ($data['grantee'] === '') {
        $errors[] = 'Grantee name is required.';
    }
    if (!$data['start_date'] || !$data['end_date']) {
        $errors[] = 'Start and end dates are required.';
    }

    if (!$errors) {
        if ($id) {
            $data['id'] = $id;
            execute_query($pdo, "
                UPDATE grants
                SET title = :title,
                    grantee = :grantee,
                    amount = :amount,
                    status = :status,
                    start_date = :start_date,
                    end_date = :end_date,
                    progress_percent = :progress_percent,
                    description = :description
                WHERE id = :id
            ", $data);

            execute_query($pdo, "
                INSERT INTO notifications (grant_id, message, type)
                VALUES (:grant_id, :message, 'Info')
            ", [
                'grant_id' => $id,
                'message' => 'Grant details updated.',
            ]);
        } else {
            execute_query($pdo, "
                INSERT INTO grants (title, grantee, amount, status, start_date, end_date, progress_percent, description)
                VALUES (:title, :grantee, :amount, :status, :start_date, :end_date, :progress_percent, :description)
            ", $data);

            $id = (int) $pdo->lastInsertId();
            execute_query($pdo, "
                INSERT INTO notifications (grant_id, message, type)
                VALUES (:grant_id, :message, 'Info')
            ", [
                'grant_id' => $id,
                'message' => 'New grant created.',
            ]);
        }

        header('Location: grant_view.php?id=' . $id);
        exit;
    }
}

if (!$grant) {
    $grant = [
        'title' => '',
        'grantee' => '',
        'amount' => '',
        'status' => 'Pending',
        'start_date' => '',
        'end_date' => '',
        'progress_percent' => 0,
        'description' => '',
    ];
}

require __DIR__ . '/partials_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= $id ? 'Edit Grant' : 'Create Grant' ?></h1>
        <p class="text-muted mb-0">Capture funding, schedule, and progress milestones.</p>
    </div>
    <a class="btn btn-outline-secondary" href="grants.php">Back to Grants</a>
</div>

<?php if ($errors) : ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error) : ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Grant Title</label>
                    <input class="form-control" name="title" value="<?= htmlspecialchars($grant['title']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Grantee Organization</label>
                    <input class="form-control" name="grantee" value="<?= htmlspecialchars($grant['grantee']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Funding Amount (BTN)</label>
                    <input class="form-control" name="amount" type="number" step="0.01" value="<?= htmlspecialchars((string) $grant['amount']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['Pending','Active','Paused','Completed','Closed'] as $status) : ?>
                            <option value="<?= $status ?>" <?= $status === $grant['status'] ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Progress (%)</label>
                    <input class="form-control" name="progress_percent" type="number" min="0" max="100" value="<?= (int) $grant['progress_percent'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Start Date</label>
                    <input class="form-control" name="start_date" type="date" value="<?= htmlspecialchars($grant['start_date']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Date</label>
                    <input class="form-control" name="end_date" type="date" value="<?= htmlspecialchars($grant['end_date']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Narrative / Scope</label>
                    <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($grant['description']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save Grant</button>
                <a class="btn btn-outline-secondary" href="grants.php">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
