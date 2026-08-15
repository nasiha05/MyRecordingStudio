<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('client');

$client = new Client($_SESSION['user_id']);
$upcoming = $client->getUpcomingSessions();
$completed = $client->getCompletedSessions();
$all = $client->getAllBookings();
$bookingModel = new Booking();

$BASE = '../';
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Welcome, <?= e($client->name) ?> 👋</h1>
    <p class="subtitle">Here's an overview of your studio bookings.</p>
</div>

<div class="card-grid">
    <div class="stat-card">
        <div class="stat-value"><?= count($all) ?></div>
        <div class="stat-label">Total Bookings</div>
    </div>
    <div class="stat-card" style="border-color:#F2A93B;">
        <div class="stat-value"><?= count($upcoming) ?></div>
        <div class="stat-label">Upcoming / Current Sessions</div>
    </div>
    <div class="stat-card" style="border-color:#16C79A;">
        <div class="stat-value"><?= count($completed) ?></div>
        <div class="stat-label">Completed Sessions</div>
    </div>
</div>

<div class="card">
    <h2>Quick Actions</h2>
    <div class="btn-group">
        <a href="book.php" class="btn btn-primary">➕ Book a Studio</a>
        <a href="available_locations.php" class="btn btn-outline">📍 View Available Studios</a>
        <a href="search_locations.php" class="btn btn-outline">🔍 Search Locations</a>
        <a href="my_bookings.php" class="btn btn-outline">📋 My Bookings</a>
    </div>
</div>

<div class="card">
    <h2>Your Next Sessions</h2>
    <?php if (empty($upcoming)): ?>
        <div class="empty-state">You have no upcoming sessions. <a href="book.php">Book one now</a>.</div>
    <?php else: ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Location</th><th>Date</th><th>Time</th><th>Duration</th><th>Cost</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($upcoming, 0, 5) as $b): ?>
                <tr>
                    <td><?= e($b['location_description']) ?></td>
                    <td><?= formatDate($b['booking_date']) ?></td>
                    <td><?= formatTime($b['start_time']) ?> - <?= formatTime($b['end_time']) ?></td>
                    <td><?= (int)$b['duration_hours'] ?>h</td>
                    <td><?= formatMoney($b['total_cost']) ?></td>
                    <td><?= statusBadge($bookingModel->computedStatus($b)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
