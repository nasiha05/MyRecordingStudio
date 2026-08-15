<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('client');

$client = new Client($_SESSION['user_id']);
$locations = $client->getAvailableLocations();

$BASE = '../';
$pageTitle = 'Available Studios';
$activeNav = 'available';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Locations With Available Studios</h1>
    <p class="subtitle">Showing locations that have at least one free studio right now.</p>
</div>

<div class="card">
    <?php if (empty($locations)): ?>
        <div class="empty-state">All studios are currently fully booked. Please check back later.</div>
    <?php else: ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Location</th><th>Total Studios</th><th>Studios Free Now</th><th>Cost / Hour</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($locations as $loc): ?>
                <tr>
                    <td><?= e($loc['description']) ?></td>
                    <td><?= (int)$loc['num_studios'] ?></td>
                    <td><span class="badge badge-available"><?= (int)$loc['studios_free'] ?> free</span></td>
                    <td><?= formatMoney($loc['cost_per_hour']) ?></td>
                    <td>
                        <a href="book.php?location_id=<?= (int)$loc['location_id'] ?>"
                        class="btn btn-sm btn-primary">
                            Book Now
                        </a>
                    </td>                
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
