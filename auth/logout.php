<?php
require_once __DIR__ . '/../includes/bootstrap.php';
User::logout();
session_start();
flash('success', 'You have been logged out successfully.');
header("Location: login.php");
exit;
