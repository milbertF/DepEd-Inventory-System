<div class="sidebar">


  <div class="part">
    <div class="icon dashboardlogo" style="background:transparent;">
      <img src="/images/assets/baliwasan.png" alt="">
    </div>

  </div>

  <button class="part" onclick="redirect('dashboard')">
    <div class="icon iconDashboard">
      <i class="fa-solid fa-table-columns"></i>
    </div>
    <label>Dashboard</label>
  </button>

  <button class="part" onclick="redirect('inventory')">
    <div class="icon iconItems">
      <i class="fas fa-list"></i>
    </div>
    <label>Item</label>
  </button>

  <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Admin'): ?>
    
    <button class="part" onclick="redirect('employee')">
      <div class="icon iconEmployee">
        <i class="fas fa-user-tie"></i>
      </div>
      <label>Employee</label>
    </button>
    <?php endif; ?>

  <button class="part" onclick="redirect('position')">
    <div class="icon iconPosition">
      <i class="fa-solid fa-street-view"></i>
    </div>
    <label>Position</label>
  </button>

  <button class="part" onclick="redirect('office')">
    <div class="icon iconOffice">
      <i class="fa-solid fa-building"></i>
    </div>
    <label>Office</label>
  </button>

  <button class="part" onclick="redirect('request')">
    <div class="icon iconRequest">
      <i class="fa-solid fa-bullhorn"></i>
    </div>
    <label>Request</label>
  </button>

  <button class="part" onclick="redirect('report')">
    <div class="icon iconReport">
      <i class="fas fa-file-lines"></i>
    </div>
    <label>Report</label>
  </button>

  <div class="set">
    <button onclick="openSettings()">
      <i class="fa-solid fa-gear"></i>
    </button>
    <button class="acc" title="<?php echo isset($_SESSION['user']['first_name'], $_SESSION['user']['last_name'])
                                  ? htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'])
                                  : 'Unknown User'; ?>" onclick="showSidebarAccountTooltip()">
      <img
        src="<?php echo isset($_SESSION['user']['profile_photo']) && !empty($_SESSION['user']['profile_photo'])
                ? htmlspecialchars($_SESSION['user']['profile_photo'])
                : '/images/user-profile/default-image.jpg'; ?>"
        alt="Profile">
      <div id="sidebarAccountTooltip" class="tooltip" style="display: none;">
        <p>
          <?php
          echo isset($_SESSION['user']['first_name'], $_SESSION['user']['last_name'])
            ? htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'])
            : 'Unknown User';
          ?>
        </p>

        <a class="out" tabindex="1" href="/logout" type="button">Logout</a>
      </div>
    </button>
  </div>

</div>
