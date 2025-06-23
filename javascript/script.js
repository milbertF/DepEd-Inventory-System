function redirect(page) {
    window.location.href = page;
}

function showSidebarAccountTooltip() {
    const sidebarAccountTooltip = document.getElementById('sidebarAccountTooltip');

    if (sidebarAccountTooltip.style.display === "none") {
        sidebarAccountTooltip.style.display = "flex";
    } else {
        sidebarAccountTooltip.style.display = "none";
    }
}