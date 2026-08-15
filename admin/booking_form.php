<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('admin');

$admin = new Administrator($_SESSION['user_id']);
$bookingModel = new Booking();
$locationModel = new Location();

$bookingId = (int)($_GET['id'] ?? $_POST['booking_id'] ?? 0);
$editing = $bookingId > 0;
$booking = $editing ? $bookingModel->getById($bookingId) : null;

if ($editing && !$booking) {
    flash('error', 'Booking not found.');
    header('Location: bookings.php');
    exit;
}

/* AJAX endpoint used by the administrator booking form. */
if (isset($_GET['availability'])) {
    header('Content-Type: application/json; charset=utf-8');

    $locationId = (int)($_GET['location_id'] ?? 0);
    $clientId = (int)($_GET['client_id'] ?? ($booking['client_id'] ?? 0));
    $date = $_GET['booking_date'] ?? '';
    $duration = (int)($_GET['duration_hours'] ?? 0);

    $slots = $bookingModel->getAvailableStartTimes(
        $locationId,
        $date,
        $duration,
        $editing ? $bookingId : null,
        $clientId > 0 ? $clientId : null
    );

    echo json_encode(['success' => true, 'slots' => $slots]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId   = (int)($_POST['client_id'] ?? 0);
    $locationId = (int)($_POST['location_id'] ?? 0);
    $date       = $_POST['booking_date'] ?? '';
    $startTime  = $_POST['start_time'] ?? '';
    $duration   = $_POST['duration_hours'] ?? '';

    if ($editing) {
        $result = $admin->modifyBooking($bookingId, $locationId, $date, $startTime, $duration);
    } elseif ($clientId <= 0) {
        $result = ['success' => false, 'errors' => ['Please select a client.']];
    } else {
        $result = $admin->createBookingForClient($clientId, $locationId, $date, $startTime, $duration);
    }

    if ($result['success']) {
        flash('success', 'Booking ' . ($editing ? 'updated' : 'created') . ' successfully.');
        header('Location: bookings.php');
        exit;
    }

    flashErrors($result['errors']);
    $booking = [
        'booking_id' => $bookingId,
        'client_id' => $clientId,
        'location_id' => $locationId,
        'booking_date' => $date,
        'start_time' => $startTime,
        'duration_hours' => $duration,
        'client_name' => $booking['client_name'] ?? '',
        'client_email' => $booking['client_email'] ?? '',
    ];
}

$clients = $admin->getAllClients();
$locations = $admin->getAllLocations();

$BASE = '../';
$pageTitle = $editing ? 'Modify Booking' : 'Create Booking';
$activeNav = 'bookings';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><?= $editing ? 'Modify Booking #' . $bookingId : 'Create Booking for a Client' ?></h1>
    <p class="subtitle">Select the client, location, date and duration. Available start times are shown automatically.</p>
</div>

<div class="card booking-card">
    <form method="POST" action="booking_form.php<?= $editing ? '?id=' . $bookingId : '' ?>" novalidate>
        <?php if ($editing): ?>
            <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="client_id">Client</label>
            <?php if ($editing): ?>
                <input type="text" disabled value="<?= e($booking['client_name'] ?? '') ?> (<?= e($booking['client_email'] ?? '') ?>)">
                <input type="hidden" id="client_id" name="client_id" value="<?= (int)$booking['client_id'] ?>">
                <p class="form-hint">The client is fixed when modifying an existing booking.</p>
            <?php else: ?>
                <select id="client_id" name="client_id" required>
                    <option value="">-- Select a client --</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= (int)$c['user_id'] ?>" <?= (isset($booking['client_id']) && $booking['client_id'] == $c['user_id']) ? 'selected' : '' ?>>
                            <?= e($c['name']) ?> (<?= e($c['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="location_id">Location</label>
            <select id="location_id" name="location_id" required>
                <option value="">-- Select a location --</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= (int)$loc['location_id'] ?>" data-cost="<?= e($loc['cost_per_hour']) ?>"
                        <?= (isset($booking['location_id']) && $booking['location_id'] == $loc['location_id']) ? 'selected' : '' ?>>
                        <?= e($loc['description']) ?> &mdash; <?= (int)$loc['num_studios'] ?> studios &mdash; <?= formatMoney($loc['cost_per_hour']) ?>/hr
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="booking_date">Booking Date</label>
                <input type="date" id="booking_date" name="booking_date" required min="<?= date('Y-m-d') ?>"
                       value="<?= e(substr($booking['booking_date'] ?? '', 0, 10)) ?>">
            </div>

            <div class="form-group">
                <label for="duration_hours">Session Duration (hours)</label>
                <input type="number" id="duration_hours" name="duration_hours" min="1" max="12" required
                       value="<?= e((string)($booking['duration_hours'] ?? '')) ?>">
                <p class="form-hint">Choose 1 to 12 hours. The session must finish by 10:00 PM.</p>
            </div>
        </div>

        <div class="form-group">
            <label>Available Start Times</label>
            <input type="hidden" id="start_time" name="start_time" value="<?= e(substr($booking['start_time'] ?? '', 0, 8)) ?>">

            <div id="availabilityPanel" class="availability-panel">
                <p class="availability-placeholder">Select a client, location, date and duration to see available start times.</p>
            </div>
        </div>

        <div class="booking-summary">
            <span>Estimated cost</span>
            <strong id="estCost">$0.00</strong>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Create Booking' ?></button>
            <a href="bookings.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script>
function updateEstimate() {
    const select = document.getElementById('location_id');
    const duration = parseFloat(document.getElementById('duration_hours').value) || 0;
    const option = select.options[select.selectedIndex];
    const hourlyRate = option ? parseFloat(option.dataset.cost) || 0 : 0;
    document.getElementById('estCost').textContent = '$' + (hourlyRate * duration).toFixed(2);
}

async function loadAvailability() {
    const clientId = document.getElementById('client_id')?.value || '';
    const locationId = document.getElementById('location_id').value;
    const date = document.getElementById('booking_date').value;
    const duration = document.getElementById('duration_hours').value;
    const panel = document.getElementById('availabilityPanel');
    const startInput = document.getElementById('start_time');
    const previousStart = startInput.value;

    startInput.value = '';

    if (!clientId || !locationId || !date || !duration) {
        panel.innerHTML = '<p class="availability-placeholder">Select a client, location, date and duration to see available start times.</p>';
        return;
    }

    panel.innerHTML = '<p class="availability-loading">Checking studio availability...</p>';

    try {
        const url = 'booking_form.php?availability=1'
            + '&id=<?= $editing ? $bookingId : 0 ?>'
            + '&client_id=' + encodeURIComponent(clientId)
            + '&location_id=' + encodeURIComponent(locationId)
            + '&booking_date=' + encodeURIComponent(date)
            + '&duration_hours=' + encodeURIComponent(duration);

        const response = await fetch(url);
        const data = await response.json();

        if (!data.success || data.slots.length === 0) {
            panel.innerHTML = '<div class="availability-empty">No available start times for this date and duration. Try another date, duration or location.</div>';
            return;
        }

        panel.innerHTML = '';

        data.slots.forEach(slot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'time-slot';
            button.dataset.start = slot.start_time;
            button.innerHTML = '<strong>' + formatTime(slot.start_time) + '</strong><span>' + slot.studios_free + ' studio' + (slot.studios_free === 1 ? '' : 's') + ' free</span>';

            button.addEventListener('click', () => {
                document.querySelectorAll('.time-slot').forEach(btn => btn.classList.remove('selected'));
                button.classList.add('selected');
                startInput.value = slot.start_time;
            });

            panel.appendChild(button);

            if (previousStart && previousStart.substring(0, 5) === slot.start_time.substring(0, 5)) {
                button.click();
            }
        });
    } catch (error) {
        panel.innerHTML = '<div class="availability-empty">Unable to load availability. Please try again.</div>';
    }
}

function formatTime(time) {
    const parts = time.substring(0, 5).split(':');
    const hour = parseInt(parts[0], 10);
    const minute = parseInt(parts[1], 10);
    const suffix = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return displayHour + ':' + String(minute).padStart(2, '0') + ' ' + suffix;
}

document.getElementById('location_id').addEventListener('change', () => {
    updateEstimate();
    loadAvailability();
});
document.getElementById('booking_date').addEventListener('change', loadAvailability);
document.getElementById('duration_hours').addEventListener('input', () => {
    updateEstimate();
    loadAvailability();
});
if (document.getElementById('client_id')) {
    document.getElementById('client_id').addEventListener('change', loadAvailability);
}

updateEstimate();
loadAvailability();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
