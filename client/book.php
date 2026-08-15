<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('client');

$client = new Client($_SESSION['user_id']);
$locationModel = new Location();
$bookingModel = new Booking();
$locations = $locationModel->getAll();

$selectedLocationId = isset($_POST['location_id'])
    ? (int)$_POST['location_id']
    : (int)($_GET['location_id'] ?? 0);

/* AJAX endpoint used by the booking form to display only start times
   that can accommodate the selected duration. */
if (isset($_GET['availability'])) {
    header('Content-Type: application/json; charset=utf-8');

    $locationId = (int)($_GET['location_id'] ?? 0);
    $date = $_GET['booking_date'] ?? '';
    $duration = (int)($_GET['duration_hours'] ?? 0);

    $slots = $bookingModel->getAvailableStartTimes(
        $locationId,
        $date,
        $duration,
        null,
        (int)$_SESSION['user_id']
    );

    $message = null;

    // If capacity is available for some times but every such time conflicts
    // with one of this client's existing sessions, explain that specific
    // reason instead of showing the generic "no slots" message.
    if (empty($slots)) {

        $capacitySlots = $bookingModel->getAvailableStartTimes(
            $locationId,
            $date,
            $duration,
            null,
            null,
            true
        );

        if (!empty($capacitySlots)) {
            $message = 'overlap';
        } else {
            $message = 'unavailable';
        }
    }

    echo json_encode([
        'success' => true,
        'slots' => $slots,
        'message' => $message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationId = (int)($_POST['location_id'] ?? 0);
    $date       = $_POST['booking_date'] ?? '';
    $startTime  = $_POST['start_time'] ?? '';
    $duration   = $_POST['duration_hours'] ?? '';

    $result = $client->bookStudio($locationId, $date, $startTime, $duration);

    if ($result['success']) {
        header('Location: booking_confirmation.php?id=' . $result['booking']['booking_id']);
        exit;
    }

    flashErrors($result['errors']);
}

$BASE = '../';
$pageTitle = 'Book a Studio';
$activeNav = 'book';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Book a Studio Session</h1>
    <p class="subtitle">Choose a location, date, duration and an available start time. Sessions operate from 10:00 AM to 10:00 PM.</p>
</div>

<div class="card booking-card">
    <form method="POST" action="book.php" novalidate>

        <!-- Location selection -->
        <div class="form-group">
            <label for="location_id">Location</label>
            <select id="location_id" name="location_id" required>
                <option value="">-- Select a location --</option>
                <?php foreach ($locations as $loc): ?>
                    <option
                        value="<?= (int)$loc['location_id'] ?>"
                        data-cost="<?= e($loc['cost_per_hour']) ?>"
                        <?= $selectedLocationId === (int)$loc['location_id'] ? 'selected' : '' ?>                    >
                        <?= e($loc['description']) ?> &mdash;
                        <?= (int)$loc['num_studios'] ?> studios &mdash;
                        <?= formatMoney($loc['cost_per_hour']) ?>/hr
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <!-- Booking date -->
            <div class="form-group">
                <label for="booking_date">Booking Date</label>
                <input
                    type="date"
                    id="booking_date"
                    name="booking_date"
                    required
                    min="<?= date('Y-m-d') ?>"
                    value="<?= isset($_POST['booking_date']) ? e($_POST['booking_date']) : '' ?>"
                >
            </div>

            <!-- Session duration is deliberately blank by default so
                 the client chooses the required duration. -->
            <div class="form-group">
                <label for="duration_hours">Session Duration (hours)</label>
                <input
                    type="number"
                    id="duration_hours"
                    name="duration_hours"
                    min="1"
                    max="12"
                    required
                    value="<?= isset($_POST['duration_hours']) ? e($_POST['duration_hours']) : '' ?>"
                >
                <p class="form-hint">Choose 1 to 12 hours. The session must finish by 10:00 PM.</p>
            </div>
        </div>

        <!-- Available start times are generated from the selected location,
             date and duration. The client does not need to guess a time. -->
        <div class="form-group">
            <label>Available Start Times</label>
            <input
                type="hidden"
                id="start_time"
                name="start_time"
                value="<?= isset($_POST['start_time']) ? e($_POST['start_time']) : '' ?>"
            >

            <div id="availabilityPanel" class="availability-panel">
                <p class="availability-placeholder">
                    Select a location, date and duration to see available start times.
                </p>
            </div>

            <p class="form-hint">
                Each button represents a start time that has at least one studio available for the full selected duration.
            </p>
        </div>

        <div class="booking-summary">
            <span>Estimated cost</span>
            <strong id="estCost">$0.00</strong>
        </div>

        <button type="submit" class="btn btn-primary booking-submit">Confirm Booking</button>
    </form>
</div>

<script>
/* Update the estimated cost using the selected location's hourly rate. */
function updateEstimate() {
    const select = document.getElementById('location_id');
    const duration = parseFloat(document.getElementById('duration_hours').value) || 0;
    const option = select.options[select.selectedIndex];
    const hourlyRate = option ? parseFloat(option.dataset.cost) || 0 : 0;

    document.getElementById('estCost').textContent = '$' + (hourlyRate * duration).toFixed(2);
}

/* Request the available start times from the PHP booking logic. */
async function loadAvailability() {
    const locationId = document.getElementById('location_id').value;
    const date = document.getElementById('booking_date').value;
    const duration = document.getElementById('duration_hours').value;
    const panel = document.getElementById('availabilityPanel');
    const startInput = document.getElementById('start_time');
    const previousStart = startInput.value;

    startInput.value = '';

    if (!locationId || !date || !duration) {
        panel.innerHTML = '<p class="availability-placeholder">Select a location, date and duration to see available start times.</p>';
        return;
    }

    panel.innerHTML = '<p class="availability-loading">Checking studio availability...</p>';

    try {
        const url = 'book.php?availability=1'
            + '&location_id=' + encodeURIComponent(locationId)
            + '&booking_date=' + encodeURIComponent(date)
            + '&duration_hours=' + encodeURIComponent(duration);

        const response = await fetch(url);
        const data = await response.json();

        if (!data.success || data.slots.length === 0) {

            if (data.message === 'overlap') {

                panel.innerHTML =
                    '<div class="availability-empty availability-warning">' +
                    '<strong>Overlapping booking:</strong> ' +
                    'you already have a booking that overlaps with the available times on this date. ' +
                    'Please choose a different date or a time that does not overlap with your existing session.' +
                    '</div>';

            } else {

                panel.innerHTML =
                    '<div class="availability-empty">' +
                    '<strong>No available start times.</strong> ' +
                    'All studios are occupied for the selected duration, or the selected date/time cannot be booked. ' +
                    'Try another date, duration or location.' +
                    '</div>';
            }

            return;
        }
        panel.innerHTML = '';

        data.slots.forEach(slot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'time-slot';
            button.dataset.start = slot.start_time;
            button.innerHTML = '<strong>' + formatTime(slot.start_time) + '</strong>'
                + '<span>' + slot.studios_free + ' studio' + (slot.studios_free === 1 ? '' : 's') + ' free</span>';

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

updateEstimate();
loadAvailability();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
