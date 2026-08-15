<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (User::isLoggedIn()) {
    header("Location: " . ($_SESSION['user_type'] === 'admin' ? "../admin/dashboard.php" : "../client/dashboard.php"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = Validator::clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!Validator::required($email) || !Validator::required($password)) {
        flash('error', 'Please enter both email and password.');
    } else {
        $user = new User();
        $row = $user->login($email, $password);
        if ($row) {
            $_SESSION['user_id']   = (int)$row['user_id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_type'] = $row['user_type'];
            flash('success', 'Welcome back, ' . $row['name'] . '!');
            header("Location: " . ($row['user_type'] === 'admin' ? "../admin/dashboard.php" : "../client/dashboard.php"));
            exit;
        } else {
            flash('error', 'Invalid email or password.');
        }
    }
}

$BASE = '../';
$pageTitle = 'Login';
$activeNav = 'login';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
    <div class="card">
        <h1>Welcome Back</h1>
        <p class="text-muted text-center">Log in to manage your bookings.</p>

        <form method="POST" action="login.php" novalidate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Login</button>
        </form>

        <p class="auth-switch">Don't have an account? <a href="register.php">Register here</a></p>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
