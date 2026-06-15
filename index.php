<?php
/**
 * Elyxar AI - Main Entry Point
 * Redirects based on login status
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Not logged in - show home/landing page
    header('Location: home.php');
    exit;
}

// User is logged in, show main chat
include 'chat.php';
