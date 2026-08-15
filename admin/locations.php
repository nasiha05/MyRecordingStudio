<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('admin');

$admin = new Administrator($_SESSION['user_id']);

$tab = $_GET['tab'] ?? 'all';
if ($tab === 'available') {
    $locations = $admin->getAvailableLocations();
} elseif ($tab === 'full') {
    $locations = $admin->getFullyBookedLocations();
} else {
    $tab = 'all';
    $locations = $admin->getAllLocations();
}

$BASE = '../';
$pageTitle = 'Locations';
$activeNav = 'locations';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Studio Locations</h1>
    <p class="subtitle">Create, edit, and monitor all recording studio locations.</p>
</div>

<div class="btn-group" style="margin-bottom:16px;">
    <a href="location_form.php" class="btn btn-primary">➕ Add New Location</a>
</div>

<div class="tabs">
    <a href="?tab=all" class="<?= $tab === 'all' ? 'active' : '' ?>">All Locations</a>
    <a href="?tab=available" class="<?= $tab === 'available' ? 'active' : '' ?>">With Available Studios</a>
    <a href="?tab=full" class="<?= $tab === 'full' ? 'active' : '' ?>">Fully Booked</a>
</div>

<div class="card">
    <?php if (empty($locations)): ?>
        <div class="empty-state">No locations found in this view.</div>
    <?php else: ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Description</th><th>Total Studios</th><th>Cost / Hour</th>
                    <?php if ($tab === 'available'): ?><th>Free Now</th><?php endif; ?>
                    <?php if ($tab === 'full'): ?><th>Busy Now</th><?php endif; ?>
                    <th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($locations as $loc): ?>
                <tr>
                    <td>#<?= (int)$loc['location_id'] ?></td>
                    <td><?= e($loc['description']) ?></td>
                    <td><?= (int)$loc['num_studios'] ?></td>
                    <td><?= formatMoney($loc['cost_per_hour']) ?></td>
                    <?php if ($tab === 'available'): ?><td><span class="badge badge-available"><?= (int)$loc['studios_free'] ?></span></td><?php endif; ?>
                    <?php if ($tab === 'full'): ?><td><span class="badge badge-full"><?= (int)$loc['studios_busy'] ?></span></td><?php endif; ?>
                    <td class="actions">
                        <a class="btn btn-sm btn-outline" href="location_form.php?id=<?= (int)$loc['location_id'] ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
