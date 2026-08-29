<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Destroys the old session completely
logout_user();

// Start a brand new session to hold the flash message
session_start();
set_flash('success', 'You have been logged out successfully.');
redirect('login.php');