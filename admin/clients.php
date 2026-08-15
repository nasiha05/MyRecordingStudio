<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('admin');

$admin = new Administrator($_SESSION['user_id']);

$tab = $_GET['tab'] ?? 'all';
if ($tab === 'active') {
    $clients = $admin->getClientsCurrentlyUsingStudio();
} else {
    $tab = 'all';
    $clients = $admin->getAllClients();
}

$BASE = '../';
$pageTitle = 'Clients';
$activeNav = 'clients';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Clients</h1>
    <p class="subtitle">View all registered clients, or only those currently using a studio.</p>
</div>

<div class="tabs">
    <a href="?tab=all" class="<?= $tab === 'all' ? 'active' : '' ?>">All Registered Clients</a>
    <a href="?tab=active" class="<?= $tab === 'active' ? 'active' : '' ?>">Currently Using a Studio</a>
</div>

<div class="card">
    <?php if (empty($clients)): ?>
        <div class="empty-state">No clients found in this view.</div>
    <?php elseif ($tab === 'active'): ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Location</th><th>Session Time</th></tr></thead>
            <tbody>
            <?php foreach ($clients as $c): ?>
                <tr>
                    <td><?= e($c['name']) ?></td>
                    <td><?= e($c['phone']) ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e($c['location_description']) ?></td>
                    <td><?= formatDate($c['booking_date']) ?>, <?= formatTime($c['start_time']) ?> - <?= formatTime($c['end_time']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php else: ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Registered</th></tr></thead>
            <tbody>
            <?php foreach ($clients as $c): ?>
                <tr>
                    <td>#<?= (int)$c['user_id'] ?></td>
                    <td><?= e($c['name']) ?></td>
                    <td><?= e($c['phone']) ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= formatDate($c['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
