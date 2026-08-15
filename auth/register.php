<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (User::isLoggedIn()) {
    header("Location: " . ($_SESSION['user_type'] === 'admin' ? "../admin/dashboard.php" : "../client/dashboard.php"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = Validator::clean($_POST['name'] ?? '');
    $phone    = Validator::clean($_POST['phone'] ?? '');
    $email    = Validator::clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $userType = $_POST['user_type'] ?? 'client';

    if ($password !== $confirm) {
        flash('error', 'Passwords do not match.');
    } else {
        $user = new User();
        $result = $user->register($name, $phone, $email, $password, $userType);
        if ($result['success']) {
            flash('success', 'Registration successful! You can now log in.');
            header("Location: login.php");
            exit;
        } else {
            flashErrors($result['errors']);
        }
    }
}

$BASE = '../';
$pageTitle = 'Register';
$activeNav = 'register';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
    <div class="card">
        <h1>Create an Account</h1>
        <p class="text-muted text-center">Register as a Client to book studio sessions, or as an Administrator to manage the site.</p>

        <form method="POST" action="register.php" novalidate>
            <div class="form-group">
                <label>I am registering as</label>
                <div class="role-toggle">
                    <label><input type="radio" name="user_type" value="client" checked onclick="this.form.dataset.t=1"> <span>Client</span></label>
                    <label><input type="radio" name="user_type" value="admin"> <span>Administrator</span></label>
                </div>
            </div>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required value="<?= isset($_POST['name']) ? e($_POST['name']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required placeholder="e.g. 0412 345 678" value="<?= isset($_POST['phone']) ? e($_POST['phone']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
            </div>
            <p class="form-hint">Password must be at least 6 characters.</p>

            <button type="submit" class="btn btn-primary" style="width:100%">Register</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="login.php">Log in here</a></p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
