class Sidebar extends HTMLElement {
  connectedCallback() {
    const existingicon = document.querySelector("link[rel~='icon']");
    if (!existingicon) {
      const link = document.createElement("link");
      link.rel = "icon";
      link.href = "/images/assets/DepEd.png";

      link.type = "image/png";
      document.head.appendChild(link);
    }

    this.innerHTML = `
        <button class="part" onclick="redirect('dashboard')" tabindex="1">
            <div class="icon iconDashboard">
                <i class="fa-solid fa-table-columns"></i>
            </div>
            <label for="">Dashboard</label>
        </button>

        <button class="part" onclick="redirect('items')">
            <div class="icon iconItems">
                <i class="fas fa-list"></i>
            </div>
            <label for="">Item</label>
        </button>

        <button class="part" onclick="redirect('employee')">
            <div class="icon iconEmployee">
                <i class="fas fa-user-tie"></i>
            </div>
            <label for="">Employee</label>
        </button>

        <button class="part" onclick="redirect('position')">
            <div class="icon iconPosition">
                <i class="fa-solid fa-street-view"></i>
            </div>
            <label for="">Position</label>
        </button>

        <button class="part" onclick="redirect('office')">
            <div class="icon iconOffice">
                <i class="fa-solid fa-building"></i>
            </div>
            <label for="">Office</label>
        </button>

        <button class="part" onclick="redirect('request')">
            <div class="icon iconRequest">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <label for="">Request</label>
        </button>

        <button class="part" onclick="redirect('report')">
            <div class="icon iconReport">
                <i class="fas fa-file-lines"></i>

            </div>
            <label for="">Report</label>
        </button>

        <div class="set">
            <button>
                <i class="fa-solid fa-gear"></i>
            </button>
            <button class="acc" onclick="showSidebarAccountTooltip()">
                <img src="/images/user-profile/default-image.jpg" alt="">
                <div id="sidebarAccountTooltip" class="tooltip" style="display: none">
                    <p>Bruce Wayne</p>
                    <a tabindex="1" href= "/logout" type="button">Logout</a>
                </div>
            </button>
        </div>

        

        `;
  }
}

customElements.define("main-sidebar", Sidebar);
