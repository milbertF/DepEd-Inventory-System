class Sidebar extends HTMLElement {
  connectedCallback() {
    this.innerHTML = `
        <div class="part" onclick="redirect('dashboard.html')">
            <div class="icon iconDashboard">
                <i class="fa-solid fa-table-columns"></i>
            </div>
            <label for="">Dashboard</label>
        </div>

        <div class="part" onclick="redirect('items.html')">
            <div class="icon iconItems">
                <i class="fas fa-list"></i>
            </div>
            <label for="">Item</label>
        </div>

        <div class="part" onclick="redirect('employee.html')">
            <div class="icon iconEmployee">
                <i class="fas fa-user-tie"></i>
            </div>
            <label for="">Employee</label>
        </div>

        <div class="part" onclick="redirect('position.html')">
            <div class="icon iconPosition">
                <i class="fa-solid fa-street-view"></i>
            </div>
            <label for="">Position</label>
        </div>

        <div class="part" onclick="redirect('office.html')">
            <div class="icon iconOffice">
                <i class="fa-solid fa-building"></i>
            </div>
            <label for="">Office</label>
        </div>

        <div class="part" onclick="redirect('request.html')">
            <div class="icon iconRequest">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <label for="">Request</label>
        </div>

        <div class="part" onclick="redirect('report.html')">
            <div class="icon iconReport">
                <i class="fas fa-file-lines"></i>

            </div>
            <label for="">Report</label>
        </div>

        <div class="acc" onclick="showSidebarAccountTooltip()">
            <img src="../images/user-profile/default-image.jpg" alt="">
            <div id="sidebarAccountTooltip" class="tooltip" style="display: none">
                <p>Bruce Wayne</p>
                <button>Logout</button>
            </div>
        </div>

        `;
  }
}

customElements.define("main-sidebar", Sidebar);
