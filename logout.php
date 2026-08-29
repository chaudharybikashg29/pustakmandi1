<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

logout_user();

// Need a fresh session to hold the flash message after logout
session_start();
set_flash('success', 'You have been logged out successfully.');
redirect('login.php');
