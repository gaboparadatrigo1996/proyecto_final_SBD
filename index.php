<?php
// Main entry point - redirect to login or dashboard
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('dashboard/index.php');
} else {
    redirect('auth/login.php');
}
