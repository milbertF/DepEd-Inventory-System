<?php
function showSweetAlert($icon, $title, $text, $redirect = null) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Notification</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
   
    </head>
    <body>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '" . addslashes($icon) . "',
                    title: '" . addslashes($title) . "',
                    html: `" . addslashes($text) . "`,
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    " . ($redirect ? "window.location.href = '" . addslashes($redirect) . "';" : "window.history.back();") . "
                });
            });
        </script>
    </body>
    </html>";

}

?>
