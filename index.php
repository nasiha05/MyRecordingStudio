<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (User::isLoggedIn()) {
    header("Location: " . ($_SESSION['user_type'] === 'admin' ? "admin/dashboard.php" : "client/dashboard.php"));
    exit;
}

$BASE = '';
$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';

$locationModel = new Location();
$featured = array_slice($locationModel->getAll(), 0, 4);
?>

<div class="hero">
    <h1>Book Your Perfect Recording Session</h1>
    <p>MyRecordingStudio connects you with professional, fully-equipped AV recording studios across multiple locations. Real-time availability, instant confirmation, no hassle.</p>
    <div class="btn-group">
        <a href="auth/register.php" class="btn btn-accent hero-register">
            Get Started
        </a>

        <a href="auth/login.php" class="btn btn-outline hero-login">
            Log In
        </a>
    </div>
</div>

<div class="card">
    <h2>Our Studio Locations</h2>
    <?php if (empty($featured)): ?>
        <p class="text-muted">No locations available yet. Please check back soon.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Location</th><th>Studios</th><th>Cost / Hour</th></tr></thead>
            <tbody>
            <?php foreach ($featured as $loc): ?>
                <tr>
                    <td><?= e($loc['description']) ?></td>
                    <td><?= (int)$loc['num_studios'] ?></td>
                    <td><?= formatMoney($loc['cost_per_hour']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<div class="card-grid">
    <div class="card">
        <h2>🕒 Flexible Hours</h2>
        <p class="text-muted">All studios operate daily from 10:00 AM to 10:00 PM. Book sessions from 1 up to 12 hours.</p>
    </div>
    <div class="card">
        <h2>📍 Multiple Locations</h2>
        <p class="text-muted">Search and compare studio locations, equipment capacity, and hourly rates.</p>
    </div>
    <div class="card">
        <h2>✅ Instant Confirmation</h2>
        <p class="text-muted">Receive an immediate confirmation with full booking details and total cost.</p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
