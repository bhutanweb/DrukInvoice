<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Druk Grants';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f5f7fb;
        }
        .navbar-brand span {
            color: #ffcd00;
        }
        .stat-card {
            border-left: 5px solid #0066b3;
            background: #fff;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
        }
        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .badge-status {
            font-size: 0.85rem;
        }
        .progress {
            height: 10px;
        }
        .table thead {
            background: #e9f1fb;
        }
        .card-header {
            background: #ffffff;
            font-weight: 600;
        }
        .footer {
            color: #6b7280;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background:#013163;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Bhutan <span>Foundation</span> Grants</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="grants.php">Grants</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-4">
