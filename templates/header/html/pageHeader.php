<?php
if (isset($category['category_name'])) {
  $pageTitle = "Items under category: " . htmlspecialchars($category['category_name']);
} elseif (!isset($pageTitle)) {
  $pageTitle = ucfirst(basename($_SERVER['PHP_SELF'], '.php'));
}

// Fake notification data for now
$notifications = [];
for ($i = 1; $i <= 12; $i++) {
  $notifications[] = [
    'type' => 'low-stock',
    'icon' => 'fas fa-exclamation-triangle',
    'message' => "<strong>Bond Paper A4</strong> is running low ($i left)",
    'time' => date("M d, Y h:i A", strtotime("-$i hours"))
  ];
}
$notifications[] = [
  'type' => 'restocked',
  'icon' => 'fas fa-box',
  'message' => "<strong>Printer Ink</strong> has been restocked",
  'time' => date("M d, Y h:i A", strtotime("-1 day"))
];
$notifications[] = [
  'type' => 'general',
  'icon' => 'fas fa-info-circle',
  'message' => "System backup completed successfully",
  'time' => date("M d, Y h:i A", strtotime("-2 days"))
];
?>

<div class="header-title-row">
  <h3 class="header-title"><?= $pageTitle ?></h3>

  <div class="notif-bell" id="notifBell">
    <i class="fas fa-bell"></i>
    <span class="notif-count" id="notifCount" style="display: none;"><?= count($notifications) ?></span>

    <!-- Notification Dropdown -->
    <div class="notif-dropdown" id="notifDropdown">
      <div class="notif-list" id="notifList">
        <?php foreach ($notifications as $index => $notif): ?>
          <div class="notif-item <?= $notif['type'] ?>" data-index="<?= $index ?>">
            <i class="<?= $notif['icon'] ?>"></i>
            <div class="notif-content">
              <p><?= $notif['message'] ?></p>
              <span class="notif-time"><?= $notif['time'] ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="notif-footer">
        <a href="#" id="viewMore">View more notifications</a>
      </div>
    </div>
  </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', () => {
  const bell = document.getElementById('notifBell');
  const dropdown = document.getElementById('notifDropdown');
  const viewMore = document.getElementById('viewMore');
  const notifItems = document.querySelectorAll('.notif-item');
  const perPage = 5;
  let visibleCount = perPage;

  
  notifItems.forEach((item, i) => {
    if (i >= perPage) item.style.display = 'none';
  });


  dropdown.addEventListener('click', (e) => {
    e.stopPropagation();
  });

  viewMore.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation(); 
    const total = notifItems.length;
    const showingAll = visibleCount >= total;

    if (showingAll) {
     
      notifItems.forEach((item, i) => {
        item.style.display = i < perPage ? 'flex' : 'none';
      });
      visibleCount = perPage;
      viewMore.textContent = 'View more notifications';
    } else {
    
      notifItems.forEach((item, i) => {
        if (i < visibleCount + perPage) item.style.display = 'flex';
      });
      visibleCount += perPage;
      if (visibleCount >= total) viewMore.textContent = 'View less notifications';
    }
  });

  bell.addEventListener('click', (e) => {
    e.stopPropagation();
    const isVisible = dropdown.style.display === 'block';
    dropdown.style.display = isVisible ? 'none' : 'block';
  });

  document.addEventListener('click', () => {
    dropdown.style.display = 'none';
  });
});

</script>
