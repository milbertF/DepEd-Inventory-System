<?php
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($request) {
    case '/login':
        require __DIR__ . '/templates/auth/login.html';
        break;

    case '/dashboard':
        require __DIR__ . '/templates/dashboard/dashboard.html';
        break;

    case '/employee':
        require __DIR__ . '/templates/employee/employee.html';
        break;

    case '/position':
        require __DIR__ . '/templates/employee/position.html';
        break;

    case '/items':
        require __DIR__ . '/templates/inventory/items.html';
        break;

    case '/report':
        require __DIR__ . '/templates/inventory/report.html';
        break;

    case '/office':
        require __DIR__ . '/templates/office/office.html';
        break;

    case '/request':
        require __DIR__ . '/templates/request/request.html';
        break;

    case '/401':
        http_response_code(401);
        require __DIR__ . '/templates/errors/401.html';
        break;

    case '/403':
        http_response_code(403);
        require __DIR__ . '/templates/errors/403.html';
        break;

    case '/404':
        http_response_code(404);
        require __DIR__ . '/templates/errors/404.html';
        break;

    case '/':
    case '/index.php':
        header("Location: /login");
        exit;

    default:
        http_response_code(404);
        require __DIR__ . '/templates/errors/404.html';
        break;
}
?>
