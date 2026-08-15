<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('admin');

$admin = new Administrator($_SESSION['user_id']);
$locationModel = new Location();

$locationId = (int)($_GET['id'] ?? $_POST['location_id'] ?? 0);
$editing = $locationId > 0;
$location = $editing ? $locationModel->getById($locationId) : ['description' => '', 'num_studios' => '', 'cost_per_hour' => ''];

if ($editing && !$location) {
    flash('error', 'Location not found.');
    header("Location: locations.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = Validator::clean($_POST['description'] ?? '');
    $numStudios  = $_POST['num_studios'] ?? '';
    $cost        = $_POST['cost_per_hour'] ?? '';

    if ($editing) {
        $result = $admin->updateLocation($locationId, $description, $numStudios, $cost);
    } else {
        $result = $admin->createLocation($description, $numStudios, $cost);
    }

    if ($result['success']) {
        flash('success', 'Location ' . ($editing ? 'updated' : 'created') . ' successfully.');
        header("Location: locations.php");
        exit;
    } else {
        flashErrors($result['errors']);
        $location = ['description' => $description, 'num_studios' => $numStudios, 'cost_per_hour' => $cost];
    }
}

$BASE = '../';
$pageTitle = $editing ? 'Edit Location' : 'Add Location';
$activeNav = 'locations';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><?= $editing ? 'Edit Location #' . $locationId : 'Add New Location' ?></h1>
</div>

<div class="card" style="max-width:560px;">
    <form method="POST" action="location_form.php" novalidate>
        <?php if ($editing): ?><input type="hidden" name="location_id" value="<?= $locationId ?>"><?php endif; ?>

        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" required
                   value="<?= e((string)$location['description']) ?>" placeholder="e.g. Wollongong CBD Studio">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="num_studios">Number of Recording Studios</label>
                <input type="number" id="num_studios" name="num_studios" min="1" required
                       value="<?= e((string)$location['num_studios']) ?>">
            </div>
            <div class="form-group">
                <label for="cost_per_hour">Cost Per Hour ($)</label>
                <input type="number" id="cost_per_hour" name="cost_per_hour" min="0.01" step="0.01" required
                       value="<?= e((string)$location['cost_per_hour']) ?>">
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Create Location' ?></button>
            <a href="locations.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
