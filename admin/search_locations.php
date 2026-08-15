<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('admin');

$admin = new Administrator($_SESSION['user_id']);
$locationId = $_GET['location_id'] ?? '';
$description = $_GET['description'] ?? '';
$searched = isset($_GET['search']);

$results = $searched ? $admin->searchLocations($locationId, $description) : [];

$BASE = '../';
$pageTitle = 'Search Locations';
$activeNav = 'search';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Search Locations</h1>
    <p class="subtitle">Search by Location ID and/or Description. Partial matches are supported.</p>
</div>

<div class="card">
    <form method="GET" action="search_locations.php" class="search-box">
        <div class="form-group">
            <label for="location_id">Location ID</label>
            <input type="text" id="location_id" name="location_id" value="<?= e($locationId) ?>" placeholder="e.g. 1">
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" value="<?= e($description) ?>" placeholder="e.g. Wollongong">
        </div>
        <input type="hidden" name="search" value="1">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<?php if ($searched): ?>
<div class="card">
    <h2>Results (<?= count($results) ?>)</h2>
    <?php if (empty($results)): ?>
        <div class="empty-state">No locations matched your search.</div>
    <?php else: ?>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>ID</th><th>Description</th><th>Studios</th><th>Cost / Hour</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($results as $loc): ?>
                <tr>
                    <td>#<?= (int)$loc['location_id'] ?></td>
                    <td><?= e($loc['description']) ?></td>
                    <td><?= (int)$loc['num_studios'] ?></td>
                    <td><?= formatMoney($loc['cost_per_hour']) ?></td>
                    <td><a class="btn btn-sm btn-outline" href="location_form.php?id=<?= (int)$loc['location_id'] ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
