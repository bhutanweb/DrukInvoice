<?php
$pageTitle = 'Dashboard | Bhutan Foundation Grants';
require __DIR__ . '/db.php';

$stats = fetch_one($pdo, "
    SELECT
        COUNT(*) AS total_grants,
        SUM(status = 'Active') AS active_grants,
        SUM(status = 'Completed') AS completed_grants,
        SUM(status = 'Closed') AS closed_grants,
        SUM(CASE WHEN end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status IN ('Active','Paused') THEN 1 ELSE 0 END) AS expiring_soon,
        COALESCE(AVG(progress_percent), 0) AS avg_progress,
        COALESCE(SUM(amount), 0) AS total_amount
    FROM grants
");

$expiring = fetch_all($pdo, "
    SELECT id, title, grantee, end_date, status, progress_percent
    FROM grants
    WHERE end_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ORDER BY end_date ASC
    LIMIT 6
");

$recentNotifications = fetch_all($pdo, "
    SELECT n.id, n.message, n.type, n.created_at, g.title
    FROM notifications n
    LEFT JOIN grants g ON n.grant_id = g.id
    ORDER BY n.created_at DESC
    LIMIT 6
");

$activeGrants = fetch_all($pdo, "
    SELECT id, title, grantee, amount, progress_percent, end_date, status
    FROM grants
    WHERE status IN ('Active', 'Paused')
    ORDER BY end_date ASC
    LIMIT 8
");

require __DIR__ . '/partials_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Grant Management Dashboard</h1>
        <p class="text-muted mb-0">At-a-glance view of Bhutan Foundation grant portfolio health.</p>
    </div>
    <a class="btn btn-primary" href="grant_form.php">+ New Grant</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-3">
        <div class="p-3 stat-card rounded">
            <div class="text-muted">Total Grants</div>
            <div class="stat-value"><?= number_format((int) $stats['total_grants']) ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="p-3 stat-card rounded">
            <div class="text-muted">Active Grants</div>
            <div class="stat-value text-success"><?= number_format((int) $stats['active_grants']) ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="p-3 stat-card rounded">
            <div class="text-muted">Expiring (30 days)</div>
            <div class="stat-value text-warning"><?= number_format((int) $stats['expiring_soon']) ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="p-3 stat-card rounded">
            <div class="text-muted">Total Funding (BTN)</div>
            <div class="stat-value"><?= number_format((float) $stats['total_amount'], 2) ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="p-3 stat-card rounded">
            <div class="text-muted">Average Progress</div>
            <div class="stat-value text-primary"><?= number_format((float) $stats['avg_progress'], 1) ?>%</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="p-3 stat-card rounded">
            <div class="text-muted">Completed Grants</div>
            <div class="stat-value text-info"><?= number_format((int) $stats['completed_grants']) ?></div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="p-3 stat-card rounded">
            <div class="text-muted">Closed Grants</div>
            <div class="stat-value text-secondary"><?= number_format((int) $stats['closed_grants']) ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header">Active Portfolio Progress</div>
            <div class="card-body">
                <?php if (!$activeGrants) : ?>
                    <p class="text-muted">No active grants yet. Add a new grant to get started.</p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Grant</th>
                                    <th>Grantee</th>
                                    <th>Funding</th>
                                    <th>Progress</th>
                                    <th>End Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeGrants as $grant) : ?>
                                    <tr>
                                        <td>
                                            <a class="fw-semibold" href="grant_view.php?id=<?= $grant['id'] ?>">
                                                <?= htmlspecialchars($grant['title']) ?>
                                            </a>
                                            <div class="text-muted small"><?= htmlspecialchars($grant['status']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($grant['grantee']) ?></td>
                                        <td>BTN <?= number_format((float) $grant['amount'], 2) ?></td>
                                        <td style="min-width: 160px;">
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?= (int) $grant['progress_percent'] ?>%"></div>
                                            </div>
                                            <span class="small text-muted"><?= (int) $grant['progress_percent'] ?>%</span>
                                        </td>
                                        <td><?= htmlspecialchars($grant['end_date']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header">Upcoming Expiries</div>
            <div class="card-body">
                <?php if (!$expiring) : ?>
                    <p class="text-muted mb-0">No upcoming expiries in the next 60 days.</p>
                <?php else : ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($expiring as $grant) : ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($grant['title']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($grant['grantee']) ?> · <?= htmlspecialchars($grant['status']) ?></small>
                                </div>
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($grant['end_date']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header">Notifications & Alerts</div>
            <div class="card-body">
                <?php if (!$recentNotifications) : ?>
                    <p class="text-muted mb-0">No notifications yet. System alerts will appear here.</p>
                <?php else : ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentNotifications as $note) : ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold"><?= htmlspecialchars($note['type']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($note['created_at']) ?></small>
                                </div>
                                <div><?= htmlspecialchars($note['message']) ?></div>
                                <?php if ($note['title']) : ?>
                                    <small class="text-muted">Grant: <?= htmlspecialchars($note['title']) ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
