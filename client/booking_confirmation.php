<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::requireRole('client');

$bookingId = (int)($_GET['id'] ?? 0);
$bookingModel = new Booking();
$booking = $bookingModel->getById($bookingId);

// Security: make sure this booking belongs to the logged-in client
if (!$booking || (int)$booking['client_id'] !== (int)$_SESSION['user_id']) {
    header("Location: my_bookings.php");
    exit;
}

$BASE = '../';
$pageTitle = 'Booking Confirmed';
$activeNav = 'book';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Booking Confirmed! ✅</h1>
</div>

<div class="confirmation-box" style="max-width:640px;">
    <h2>Confirmation Note</h2>
    <p>Thank you, <?= e($_SESSION['user_name']) ?>. Your studio session has been successfully booked.</p>
    <dl>
        <dt>Booking Reference</dt><dd>#<?= (int)$booking['booking_id'] ?></dd>
        <dt>Location</dt><dd><?= e($booking['location_description']) ?></dd>
        <dt>Date</dt><dd><?= formatDate($booking['booking_date']) ?></dd>
        <dt>Time</dt><dd><?= formatTime($booking['start_time']) ?> &ndash; <?= formatTime($booking['end_time']) ?></dd>
        <dt>Duration</dt><dd><?= (int)$booking['duration_hours'] ?> hour(s)</dd>
        <dt>Rate</dt><dd><?= formatMoney($booking['cost_per_hour']) ?> / hour</dd>
        <dt>Total Cost</dt><dd><strong style="font-size:1.2rem;"><?= formatMoney($booking['total_cost']) ?></strong></dd>
    </dl>
</div>

<div class="btn-group" style="margin-top:20px;">
    <a href="my_bookings.php" class="btn btn-primary">View My Bookings</a>
    <a href="book.php" class="btn btn-outline">Book Another Session</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
