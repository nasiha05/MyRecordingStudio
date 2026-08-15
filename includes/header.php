<?php
/**
 * header.php
 * Shared page header and navigation bar.
 *
 * The including page should set:
 *   $BASE       - relative path back to project root ('' or '../')
 *   $pageTitle  - optional title shown in the browser tab
 *   $activeNav  - optional key used to highlight the active navigation link
 */

$BASE = $BASE ?? '';
$pageTitle = $pageTitle ?? 'MyRecordingStudio';
$activeNav = $activeNav ?? '';

$userName = $_SESSION['user_name'] ?? '';
$userType = $_SESSION['user_type'] ?? '';
$userInitial = $userName !== '' ? strtoupper(substr($userName, 0, 1)) : 'U';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | MyRecordingStudio</title>
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/style.css">
</head>

<body>

<header class="navbar">

    <!-- Top header contains the application identity and account controls. -->
    <div class="container header-top">

        <div class="brand-section">
            <a href="<?= $BASE ?>index.php" class="brand">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" role="img">
                        <path d="M12 14.5a3.5 3.5 0 0 0 3.5-3.5V6a3.5 3.5 0 0 0-7 0v5a3.5 3.5 0 0 0 3.5 3.5Z"></path>
                        <path d="M5 11a7 7 0 0 0 14 0M12 18v3M8.5 21h7"></path>
                    </svg>
                </span>
                <span>MyRecordingStudio</span>
            </a>
        </div>

        <?php if (User::isLoggedIn()): ?>

            <!-- The profile button opens a small account menu without
                 requiring a separate profile page. -->
            <div class="profile-menu">
                <button type="button" class="profile-button" id="profileButton" aria-expanded="false">
                    <span class="user-avatar"><?= e($userInitial) ?></span>
                    <span class="profile-name"><?= e($userName) ?></span>
                    <span class="profile-chevron">▾</span>
                </button>

                <div class="profile-dropdown" id="profileDropdown" hidden>
                    <div class="profile-summary">
                        <span class="profile-summary-avatar"><?= e($userInitial) ?></span>
                        <div>
                            <strong><?= e($userName) ?></strong>
                            <span><?= e($_SESSION['user_email'] ?? '') ?></span>
                        </div>
                    </div>

                    <div class="profile-details">
                        <span>Business</span>
                        <strong>MyRecordingStudio</strong>

                        <span>Role</span>
                        <strong><?= e(ucfirst($userType)) ?></strong>
                    </div>

                    <a href="<?= $BASE ?>auth/logout.php" class="profile-logout">
                        ↪&nbsp; Logout
                    </a>
                </div>
            </div>

        <?php else: ?>

            <div class="guest-actions">
                <a href="<?= $BASE ?>auth/login.php" class="guest-login">Login</a>
                <a href="<?= $BASE ?>auth/register.php" class="guest-register">Register</a>
            </div>

        <?php endif; ?>

    </div>


    <?php if (User::isLoggedIn()): ?>

        <!-- Navigation is intentionally kept in a separate bar below
             the application header so the page structure remains simple. -->
        <div class="navigation-bar">
            <div class="container">
                <nav class="main-navigation">

                    <?php if (User::isAdmin()): ?>
                        <a href="<?= $BASE ?>admin/dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                        <a href="<?= $BASE ?>admin/locations.php" class="<?= $activeNav === 'locations' ? 'active' : '' ?>">Locations</a>
                        <a href="<?= $BASE ?>admin/bookings.php" class="<?= $activeNav === 'bookings' ? 'active' : '' ?>">Bookings</a>
                        <a href="<?= $BASE ?>admin/clients.php" class="<?= $activeNav === 'clients' ? 'active' : '' ?>">Clients</a>
                        <a href="<?= $BASE ?>admin/search_locations.php" class="<?= $activeNav === 'search' ? 'active' : '' ?>">Search</a>
                    <?php else: ?>
                        <a href="<?= $BASE ?>client/dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                        <a href="<?= $BASE ?>client/book.php" class="<?= $activeNav === 'book' ? 'active' : '' ?>">Book a Studio</a>
                        <a href="<?= $BASE ?>client/my_bookings.php" class="<?= $activeNav === 'bookings' ? 'active' : '' ?>">My Bookings</a>
                        <a href="<?= $BASE ?>client/available_locations.php" class="<?= $activeNav === 'available' ? 'active' : '' ?>">Available Studios</a>
                        <a href="<?= $BASE ?>client/search_locations.php" class="<?= $activeNav === 'search' ? 'active' : '' ?>">Search</a>
                    <?php endif; ?>

                </nav>
            </div>
        </div>

    <?php endif; ?>

</header>

<?php if (User::isLoggedIn()): ?>
<script>
/* Toggle the small account menu in the top-right corner. */
const profileButton = document.getElementById('profileButton');
const profileDropdown = document.getElementById('profileDropdown');

if (profileButton && profileDropdown) {
    profileButton.addEventListener('click', event => {
        event.stopPropagation();
        const isOpen = !profileDropdown.hidden;
        profileDropdown.hidden = isOpen;
        profileButton.setAttribute('aria-expanded', String(!isOpen));
    });

    document.addEventListener('click', event => {
        if (!profileDropdown.hidden && !profileDropdown.contains(event.target) && event.target !== profileButton) {
            profileDropdown.hidden = true;
            profileButton.setAttribute('aria-expanded', 'false');
        }
    });
}
</script>
<?php endif; ?>

<main class="page">
    <div class="container">
        <?php renderFlashes(); ?>
