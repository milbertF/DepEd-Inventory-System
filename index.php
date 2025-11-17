<?php
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($request) {
    case '/login':
        require __DIR__ . '/templates/auth/html/login.php';
        break;

    case '/dashboard':
        require __DIR__ . '/templates/dashboard/html/dashboard.php';
        break;

    case '/employee':
        require __DIR__ . '/templates/employee/html/employee.php';  
        break;


        case '/addEmployee':
            require __DIR__ . '/templates/employee/html/addEmployee.html';
            break;

            case '/position':
                require __DIR__ . '/templates/position/html/position.php';
                break;

    case '/inventory':
        require __DIR__ . '/templates/inventory/html/inventory.php';
        break;


        case '/itemsByCategory':
            require __DIR__ . '/templates/inventory/html/viewItemByCategory.php';
            break;

            case '/allItems':
                require __DIR__ . '/templates/inventory/html/viewAllItems.php';
                break;


                case '/recentlyDeleted':
                    require __DIR__ . '/templates/inventory/html/recentlyDeleted.php';
                    break;
    

    case '/report':
        require __DIR__ . '/templates/report/html/report.php';
        break;

    case '/office':
        require __DIR__ . '/templates/office/html/office.php';
        break;

    case '/request':
        require __DIR__ . '/templates/request/html/request.php';
        break;

        case '/my-request':
            require __DIR__ . '/templates/request/html/myRequest.php';
            break; 

    case '/forgot':
        require __DIR__ . '/templates/auth/html/forgot.html';
        break;

    case '/code':
        require __DIR__ . '/templates/auth/html/code.html';
        break;

    case '/reset':
        require __DIR__ . '/templates/auth/html/reset.html';
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


        case '/logout':
            require __DIR__ . '/templates/auth/function/logout.php';
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
