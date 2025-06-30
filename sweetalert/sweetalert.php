<?php
function showSweetAlert($icon, $title, $text, $redirect = null) {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            icon: '{$icon}',
            title: '{$title}',
            html: '{$text}',
            confirmButtonColor: '#3085d6'
        }).then(() => {
            " . ($redirect ? "window.location.href = '{$redirect}';" : "window.history.back();") . "
        });
    </script>
    ";
}
?>
