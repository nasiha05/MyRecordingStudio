<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('admin');

$admin = new Administrator($_SESSION['user_id']);
$allLocations = $admin->getAllLocations();
$availableLocations = $admin->getAvailableLocations();
$fullLocations = $admin->getFullyBookedLocations();
$allClients = $admin->getAllClients();
$activeNow = $admin->getClientsCurrentlyUsingStudio();
$allBookings = $admin->getAllBookings();

$BASE = '../';
$pageTitle = 'Admin Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Administrator Dashboard</h1>
    <p class="subtitle">Overview of the MyRecordingStudio system.</p>
</div>

<div class="card-grid">
    <div class="stat-card">
        <div class="stat-value"><?= count($allLocations) ?></div>
        <div class="stat-label">Total Locations</div>
    </div>
    <div class="stat-card" style="border-color:#16C79A;">
        <div class="stat-value"><?= count($availableLocations) ?></div>
        <div class="stat-label">Locations With Free Studios</div>
    </div>
    <div class="stat-card" style="border-color:#E4572E;">
        <div class="stat-value"><?= count($fullLocations) ?></div>
        <div class="stat-label">Fully Booked Locations</div>
    </div>
    <div class="stat-card" style="border-color:#5B3DF6;">
        <div class="stat-value"><?= count($allClients) ?></div>
        <div class="stat-label">Registered Clients</div>
    </div>
    <div class="stat-card" style="border-color:#F2A93B;">
        <div class="stat-value"><?= count($activeNow) ?></div>
        <div class="stat-label">Clients Currently In-Studio</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= count($allBookings) ?></div>
        <div class="stat-label">Total Bookings</div>
    </div>
</div>

<div class="card">
    <h2>Quick Actions</h2>
    <div class="btn-group">
        <a href="location_form.php" class="btn btn-primary">➕ Add Location</a>
        <a href="booking_form.php" class="btn btn-primary">➕ Create Booking for Client</a>
        <a href="locations.php" class="btn btn-outline">📍 Manage Locations</a>
        <a href="bookings.php" class="btn btn-outline">📋 Manage Bookings</a>
        <a href="clients.php" class="btn btn-outline">👥 View Clients</a>
    </div>
</div>

<div class="card">
    <h2>Clients Currently Using a Studio</h2>
    <?php if (empty($activeNow)): ?>
        <div class="empty-state">No client is currently in a session.</div>
    <?php else: ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Client</th><th>Email</th><th>Location</th><th>Session Time</th></tr></thead>
            <tbody>
            <?php foreach ($activeNow as $a): ?>
                <tr>
                    <td><?= e($a['name']) ?></td>
                    <td><?= e($a['email']) ?></td>
                    <td><?= e($a['location_description']) ?></td>
                    <td><?= formatDate($a['booking_date']) ?>, <?= formatTime($a['start_time']) ?> - <?= formatTime($a['end_time']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
