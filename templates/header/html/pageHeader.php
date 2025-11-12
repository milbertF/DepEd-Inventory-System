

<?php
if (isset($category['category_name'])) {
  $pageTitle = "Items under category: " . htmlspecialchars($category['category_name']);
} elseif (!isset($pageTitle)) {
  $pageTitle = ucfirst(basename($_SERVER['PHP_SELF'], '.php'));
}


require_once __DIR__ . '/../function/fetchNotification.php';


$user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0;
$notifications = [];
$unread_count = 0;

if ($user_id > 0) {
   
    $notifications = getAllNotifications($conn, $user_id, 15);
    $unread_count = getUnreadNotificationsCount($conn, $user_id);
}
?>

<div class="header-title-row">
  <h3 class="header-title"><?= $pageTitle ?></h3>

  <div class="notif-bell" id="notifBell">
    <i class="fas fa-bell"></i>
    <?php if ($unread_count > 0): ?>
      <span class="notif-count" id="notifCount"><?= $unread_count ?></span>
    <?php else: ?>
      <span class="notif-count" id="notifCount" style="display: none;">0</span>
    <?php endif; ?>

    <!-- Notification Dropdown -->
    <div class="notif-dropdown" id="notifDropdown">
      <div class="notif-header">
        <h4>Notifications</h4>
        <?php if ($unread_count > 0): ?>
          <button id="markAllRead" class="mark-read-btn">Mark all as read</button>
        <?php endif; ?>
      </div>
      
      <div class="notif-list" id="notifList">
        <?php if (empty($notifications)): ?>
          <div class="notif-item empty">
         
            <div class="notif-content">
              <p>No notifications</p>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($notifications as $notif): ?>
            <div class="notif-item <?= $notif['action_type'] ?? 'general' ?> <?= $notif['is_read'] ? 'read' : 'unread' ?>" 
     data-id="<?= $notif['notification_id'] ?>"
     data-item-id="<?= $notif['item_id'] ?>">
              <i class="<?= getNotificationIcon($notif['action_type']) ?>"></i>
              <div class="notif-content">
                <p><?= htmlspecialchars($notif['message']) ?></p>
                <?php 
                // Show decrypted full name for admin viewing all notifications
                $is_admin = isAdmin($conn, $user_id);
                if ($is_admin && isset($notif['user_full_name'])): 
                ?>
                  <small class="notif-user">From: <?= htmlspecialchars($notif['user_full_name']) ?></small>
                <?php endif; ?>
                <span class="notif-time"><?= formatNotificationTime($notif['created_at']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      
   
    </div>
  </div>
</div>



<script>
class NotificationManager {
    constructor() {
        this.lastUpdate = 0;
        this.updateInterval = 30000;
        this.isUpdating = false;
        this.unreadCount = parseInt(document.getElementById('notifCount')?.textContent || 0);
        this.userAuthenticated = this.unreadCount >= 0;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.startPeriodicUpdates();
        this.setupVisibilityHandler();
    }

    setupEventListeners() {
        const bell = document.getElementById('notifBell');
        const dropdown = document.getElementById('notifDropdown');
        const markAllRead = document.getElementById('markAllRead');

      
        bell?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });

      
        markAllRead?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.markAllAsRead();
        });

      
        document.addEventListener('click', () => {
            this.hideDropdown();
        });

     
        dropdown?.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    setupVisibilityHandler() {
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.forceUpdate();
            }
        });
    }

    startPeriodicUpdates() {
     
        setTimeout(() => this.updateNotifications(), 1000);
        
     
        setInterval(() => this.updateNotifications(), this.updateInterval);
        
     
        setInterval(() => {
            if (this.isDropdownVisible()) {
                this.updateNotifications();
            }
        }, 5000);
    }

    async updateNotifications() {
        if (this.isUpdating || !this.userAuthenticated) return;
        
        const now = Date.now();
        if (now - this.lastUpdate < 1000) return;

        this.isUpdating = true;
        
        try {
            const response = await fetch('/templates/header/function/getNotification.php', {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Handle authentication errors
            if (data.error) {
                if (data.error === 'Not authenticated') {
                    this.userAuthenticated = false;
                    console.warn('User not authenticated for notifications');
                    return;
                }
                throw new Error(data.error);
            }
            
            this.handleNotificationUpdate(data);
            
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        } finally {
            this.isUpdating = false;
            this.lastUpdate = Date.now();
        }
    }

    handleNotificationUpdate(data) {
    const oldCount = this.unreadCount;
    this.unreadCount = data.unread_count;
    
   
    this.updateCountBadge();
    
  
    if (this.isDropdownVisible() || Math.abs(oldCount - this.unreadCount) > 0) {
        this.updateDropdownContent(data.notifications);
    }
    
   
    if (this.unreadCount > oldCount && oldCount > 0) {
        this.showNewNotificationAlert(this.unreadCount - oldCount);
    }
}

updateCountBadge() {
    const countElement = document.getElementById('notifCount');
    if (!countElement) return;

    if (this.unreadCount > 0) {
        countElement.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
        countElement.style.display = 'flex';
        
      
        if (parseInt(countElement.textContent) < this.unreadCount) {
            countElement.classList.add('pulse');
            setTimeout(() => countElement.classList.remove('pulse'), 2000);
        }
    } else {
        countElement.style.display = 'none';
    }
}


  updateDropdownContent(notifications) {
    const notifList = document.getElementById('notifList');
    if (!notifList) return;

    if (notifications.length === 0) {
        notifList.innerHTML = `
            <div class="notif-item empty">
                <div class="notif-content">
                    <p>No notifications</p>
                </div>
            </div>
        `;
        return;
    }


    notifList.innerHTML = notifications.map(notif => `
        <div class="notif-item ${notif.action_type} ${notif.is_read ? 'read' : 'unread'}" 
             data-id="${notif.notification_id}"
             data-item-id="${notif.item_id}"
             data-action-type="${notif.action_type}">
            <i class="${this.getNotificationIcon(notif.action_type)}"></i>
            <div class="notif-content">
                <p>${this.escapeHtml(notif.message)}</p>
                ${notif.user_full_name ? `<small class="notif-user">From: ${this.escapeHtml(notif.user_full_name)}</small>` : ''}
                <span class="notif-time">${this.formatTime(notif.created_at)}</span>
            </div>
        </div>
    `).join('');

    notifList.querySelectorAll('.notif-item').forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        const actionType = item.dataset.actionType;
        const itemId = item.dataset.itemId;

        let targetUrl = '/allItems';
        if (actionType === 'item_deleted') {
            targetUrl = '/recentlyDeleted';
        }

     
        if (itemId) {
            targetUrl += `?item_id=${encodeURIComponent(itemId)}`;
        }
        
    
        window.location.href = targetUrl;
        
      
        this.hideDropdown();
    });
});
}

    async markAllAsRead() {
    try {
        console.log('Attempting to mark all notifications as read...');
        
        const response = await fetch('/templates/header/function/markNotificationRead.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ mark_all: true })
        });
        
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Failed to parse JSON response:', parseError);
            console.error('Raw response was:', responseText);
            throw new Error('Server returned invalid JSON. Check console for details.');
        }
        
        console.log('Parsed response:', result);
        
        if (response.ok && result.success) {
            console.log('Successfully marked notifications as read:', result);
            this.unreadCount = 0;
            this.updateCountBadge();
            
       
            this.updateNotifications();
            
        
            if (result.marked_count > 0) {
           
            }
        } else {
            console.error('Server returned error:', result);
            throw new Error(result.message || `Server error: ${response.status}`);
        }
    } catch (error) {
        console.error('Failed to mark notifications as read:', error);
   
    }
}

    toggleDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        if (!dropdown) return;

        if (dropdown.style.display === 'block') {
            this.hideDropdown();
        } else {
            this.showDropdown();
          
            this.forceUpdate();
        }
    }

    showDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown) {
            dropdown.style.display = 'block';
        }
    }

    hideDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
    }

    isDropdownVisible() {
        const dropdown = document.getElementById('notifDropdown');
        return dropdown && dropdown.style.display === 'block';
    }

    forceUpdate() {
        this.lastUpdate = 0;
        this.updateNotifications();
    }

    showNewNotificationAlert(count) {
    
        if (count > 0 && !this.isDropdownVisible()) {
            console.log(`You have ${count} new notification(s)`);
        }
    }

    getNotificationIcon(actionType) {
        const icons = {
            'quantity_added': 'fas fa-plus-circle',
            'item_created': 'fas fa-box',
            'item_updated': 'fas fa-edit',
            'item_deleted': 'fas fa-trash',
            'low_stock': 'fas fa-exclamation-triangle',
            'general': 'fas fa-info-circle'
        };
        return icons[actionType] || 'fas fa-bell';
    }

    formatTime(timestamp) {
        const time = new Date(timestamp).getTime();
        const now = Date.now();
        const diff = now - time;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + ' min ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + ' hours ago';
        if (diff < 604800000) return Math.floor(diff / 86400000) + ' days ago';
        
        return new Date(timestamp).toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
    }

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}


document.addEventListener('DOMContentLoaded', () => {
    new NotificationManager();
});
</script>