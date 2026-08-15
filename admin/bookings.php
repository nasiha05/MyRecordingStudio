<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('admin');

$admin = new Administrator($_SESSION['user_id']);
$bookingModel = new Booking();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $result = $admin->cancelBooking((int)$_POST['cancel_booking_id']);

    if ($result['success']) {
        flash('success', 'Booking cancelled successfully.');
    } else {
        flashErrors($result['errors']);
    }

    header('Location: bookings.php');
    exit;
}

$bookings = $admin->getAllBookings();

$BASE = '../';
$pageTitle = 'Bookings';
$activeNav = 'bookings';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>All Bookings</h1>
    <p class="subtitle">Create, modify and cancel client booking sessions.</p>
</div>

<div class="btn-group" style="margin-bottom:16px;">
    <a href="booking_form.php" class="btn btn-primary">+ Create Booking for a Client</a>
</div>

<div class="card">
    <?php if (empty($bookings)): ?>
        <div class="empty-state">No bookings have been made yet.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Client</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $b):
                    $status = $bookingModel->computedStatus($b);
                    $canModify = in_array($status, ['upcoming', 'in_progress'], true);
                    $canCancel = in_array($status, ['upcoming', 'in_progress'], true);
                ?>
                    <tr>
                        <td>#<?= (int)$b['booking_id'] ?></td>
                        <td>
                            <?= e($b['client_name']) ?><br>
                            <span class="text-muted"><?= e($b['client_email']) ?></span>
                        </td>
                        <td><?= e($b['location_description']) ?></td>
                        <td><?= formatDate($b['booking_date']) ?></td>
                        <td><?= formatTime($b['start_time']) ?> - <?= formatTime($b['end_time']) ?></td>
                        <td><?= formatMoney($b['total_cost']) ?></td>
                        <td><?= statusBadge($status) ?></td>
                        <td class="actions">
                            <?php if ($canModify || $canCancel): ?>
                                <div class="btn-group">
                                    <?php if ($canModify): ?>
                                        <a class="btn btn-sm btn-outline" href="booking_form.php?id=<?= (int)$b['booking_id'] ?>">Modify</a>
                                    <?php endif; ?>

                                    <?php if ($canCancel): ?>
                                        <form method="POST" class="cancel-booking-form" data-booking-ref="#<?= (int)$b['booking_id'] ?>">
                                            <input type="hidden" name="cancel_booking_id" value="<?= (int)$b['booking_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                        </form>
                                    <?php endif; ?>
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

<!-- Application-style confirmation popup used instead of the browser confirm() dialog. -->
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
/* Connect each cancel form to the application's confirmation popup. */
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
