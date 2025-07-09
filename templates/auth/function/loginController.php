<?php


if (
    isset($_SESSION['user']) &&
    is_array($_SESSION['user']) &&
    isset($_SESSION['user']['logged_in']) &&
    $_SESSION['user']['logged_in'] === true
) {
    header("Location: /dashboard");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginSuccess = handleLogin();

    if ($loginSuccess) {
        header("Location: /dashboard");
        exit;
    } else {
        $errorMessage = 'Invalid email/username or password.';
    }
}

?>