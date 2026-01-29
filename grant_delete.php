<?php
require __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id) {
    execute_query($pdo, 'DELETE FROM grants WHERE id = :id', ['id' => $id]);
}

header('Location: grants.php');
exit;
