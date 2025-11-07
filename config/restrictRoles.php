<?php
require_once __DIR__ . '/session.php';
/**
 * Restrict access by role
 *
 * @param array $blockedRoles Array of roles that are NOT allowed to access the page
 */
function restrictRoles(array $blockedRoles) {
    $userRole = $_SESSION['user']['role'] ?? '';

    
    if (in_array($userRole, $blockedRoles)) {
        header('Location: /'); 
        exit;
    }
}
