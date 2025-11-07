<?php

require __DIR__ . '/../function/loginFunction.php';
require __DIR__ . '/../function/loginController.php';



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" href="/images/assets/baliwasan.png" />
    <title>BCSI-Login</title>
    <link rel="stylesheet" href="/styles/login.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <div class="index">
        <img src="/images/assets/DepEd.png" alt="DepEd Logo Background" />

        <div class="logo">
            <img src="/images/assets/baliwasan.png" alt="DepEd Logo" />
        </div>

        <div class="titletext">
            <h1><span>Baliwasan</span> <span style="color:orange;"> Central School</span> Inventory System</h1>
        </div>

        <form id="loginForm" method="POST">
            <div class="container">
                <div class="inputContainer">
                    <label for="login-identifier">Email or Username</label>
                    <div class="input">
                        <input id="login-identifier" name="identifier" type="text" required />
                    </div>
                </div>

                <div class="inputContainer">
                    <label for="login-password">Password</label>
                    <div class="input">
                        <input id="login-password" name="password" type="password" required />
                        <i class="fa-solid fa-eye" id="togglePassword" style="cursor:pointer;"></i>
                    </div>
                    <a href="forgot" class="forgotPassword">Forgot password?</a>
                </div>

                <button type="submit" id="login-btn">Sign in</button>
            </div>
        </form>

        <div class="credits">
            <p>Built and maintained by <a href="/templates/aboutus/aboutus.php">CRAMATIX</a></p>
        </div>
    </div>



    <?php if (isset($errorMessage)): ?>
        <div id="login-error" data-message="<?= htmlspecialchars($errorMessage, ENT_QUOTES) ?>" style="display:none;"></div>
    <?php endif; ?>



    <script src="/javascript/login.js"></script>



</body>

</html>