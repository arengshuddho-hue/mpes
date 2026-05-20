<?php
// config/session.php - Shared session guard + helpers
// This file initializes the session and provides functions for authentication checks.

// Start the session to manage user login state
session_start();

/**
 * Checks if the user is logged in and optionally checks their role.
 * Redirects to the login page if the user is not authenticated or lacks the required role.
 * 
 * @param string|null $role The required role to access the page (e.g., 'admin', 'doctor', 'patient').
 */
function requireLogin($role = null) {
    // Check if the user ID is set in the session (user is logged in)
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }
    // Check if a specific role is required and if the current user has that role
    if ($role && $_SESSION['role'] !== $role) {
        header("Location: ../login.php");
        exit;
    }
}

/**
 * Retrieves the current logged-in user's details from the session.
 * 
 * @return array Associative array containing 'id', 'name', and 'role'.
 */
function currentUser() {
    return [
        'id'   => $_SESSION['user_id']  ?? null,  // User ID or null if not set
        'name' => $_SESSION['name']     ?? 'Guest', // User name or 'Guest' default
        'role' => $_SESSION['role']     ?? 'guest', // User role or 'guest' default
    ];
}
?>
