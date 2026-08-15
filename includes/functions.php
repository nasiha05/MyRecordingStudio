<?php
/**
 * functions.php
 * Small shared helper functions used across pages (flash messages,
 * formatting helpers). Not a "class" on purpose - kept procedural
 * since these are simple display helpers, not domain objects.
 */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashErrors(array $errors): void
{
    foreach ($errors as $e) {
        flash('error', $e);
    }
}

function renderFlashes(): void
{
    if (empty($_SESSION['flash'])) return;
    foreach ($_SESSION['flash'] as $f) {
        $class = $f['type'] === 'success' ? 'alert-success' : 'alert-error';
        echo '<div class="alert ' . $class . '">' . htmlspecialchars($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

function formatMoney($amount): string
{
    return '$' . number_format((float)$amount, 2);
}

function formatDate($date): string
{
    return date('D, d M Y', strtotime($date));
}

function formatTime($time): string
{
    return date('g:i A', strtotime($time));
}

function statusBadge(string $status): string
{
    $labels = [
        'upcoming'    => 'Upcoming',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ];
    $label = $labels[$status] ?? ucfirst($status);
    return '<span class="badge badge-' . htmlspecialchars($status) . '">' . htmlspecialchars($label) . '</span>';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
