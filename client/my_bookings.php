<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('client');

$client = new Client($_SESSION['user_id']);
$bookingModel = new Booking();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $result = $client->cancelBooking((int)$_POST['cancel_booking_id']);

    if ($result['success']) {
        flash('success', 'Booking cancelled successfully.');
    } else {
        flashErrors($result['errors']);
    }

    header('Location: my_bookings.php?tab=' . urlencode($_GET['tab'] ?? 'all'));
    exit;
}

$tab = $_GET['tab'] ?? 'all';
if ($tab === 'upcoming') {
    $bookings = $client->getUpcomingSessions();
} elseif ($tab === 'completed') {
    $bookings = $client->getCompletedSessions();
} else {
    $tab = 'all';
    $bookings = $client->getAllBookings();
}

$BASE = '../';
$pageTitle = 'My Bookings';
$activeNav = 'bookings';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>My Bookings</h1>
    <p class="subtitle">View your sessions and modify or cancel an upcoming booking.</p>
</div>

<div class="tabs">
    <a href="?tab=all" class="<?= $tab === 'all' ? 'active' : '' ?>">All Bookings</a>
    <a href="?tab=upcoming" class="<?= $tab === 'upcoming' ? 'active' : '' ?>">Current &amp; Upcoming</a>
    <a href="?tab=completed" class="<?= $tab === 'completed' ? 'active' : '' ?>">Completed</a>
</div>

<div class="card">
    <?php if (empty($bookings)): ?>
        <div class="empty-state">No bookings found in this view. <a href="book.php">Book a studio session</a>.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ref</th><th>Location</th><th>Date</th><th>Time</th>
                        <th>Duration</th><th>Cost</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $b):
                    $status = $bookingModel->computedStatus($b);
                    $canModify = $status === 'upcoming';
                ?>
                    <tr>
                        <td>#<?= (int)$b['booking_id'] ?></td>
                        <td><?= e($b['location_description']) ?></td>
                        <td><?= formatDate($b['booking_date']) ?></td>
                        <td><?= formatTime($b['start_time']) ?> - <?= formatTime($b['end_time']) ?></td>
                        <td><?= (int)$b['duration_hours'] ?>h</td>
                        <td><?= formatMoney($b['total_cost']) ?></td>
                        <td><?= statusBadge($status) ?></td>
                        <td class="actions">
                            <?php if ($canModify): ?>
                                <div class="btn-group">
                                    <a class="btn btn-sm btn-outline" href="booking_edit.php?id=<?= (int)$b['booking_id'] ?>">Modify</a>
                                    <form method="POST" class="cancel-booking-form" data-booking-ref="#<?= (int)$b['booking_id'] ?>">
                                        <input type="hidden" name="cancel_booking_id" value="<?= (int)$b['booking_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Locked</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="cancelModal" hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="cancelModalTitle">
        <div class="modal-icon">!</div>
        <h2 id="cancelModalTitle">Cancel booking?</h2>
        <p>Are you sure you want to cancel booking <strong id="cancelBookingRef"></strong>?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" id="cancelModalNo">Keep Booking</button>
            <button type="button" class="btn btn-danger" id="cancelModalYes">Yes, Cancel</button>
        </div>
    </div>
</div>

<script>
let pendingCancelForm = null;
const cancelModal = document.getElementById('cancelModal');
const cancelBookingRef = document.getElementById('cancelBookingRef');

function closeCancelModal() {
    cancelModal.hidden = true;
    pendingCancelForm = null;
}

document.querySelectorAll('.cancel-booking-form').forEach(form => {
    form.addEventListener('submit', event => {
        event.preventDefault();
        pendingCancelForm = form;
        cancelBookingRef.textContent = form.dataset.bookingRef || '';
        cancelModal.hidden = false;
    });
});

document.getElementById('cancelModalNo').addEventListener('click', closeCancelModal);
document.getElementById('cancelModalYes').addEventListener('click', () => {
    if (pendingCancelForm) pendingCancelForm.submit();
});
cancelModal.addEventListener('click', event => {
    if (event.target === cancelModal) closeCancelModal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
