<?php
require_once __DIR__ . '/session.php';


if (
    !isset($_SESSION['user']) ||
    !is_array($_SESSION['user']) ||
    empty($_SESSION['user']['logged_in']) ||
    $_SESSION['user']['logged_in'] !== true
) {
    header('Location: /login');
    exit;
}
