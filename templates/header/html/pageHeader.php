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
            <div class="notif-item <?= $notif['action_type'] ?? 'general' ?> <?= $notif['notification_type'] ?? 'system' ?> <?= getStatusClass($notif['action_type']) ?> <?= $notif['is_read'] ? 'read' : 'unread' ?>" 
     data-id="<?= $notif['notification_id'] ?? $notif['log_id'] ?>"
     data-item-id="<?= $notif['item_id'] ?>"
     data-action-type="<?= $notif['action_type'] ?>"
     data-notification-type="<?= $notif['notification_type'] ?? 'system' ?>">
              <i class="<?= getNotificationIcon($notif['action_type'], $notif['notification_type'] ?? 'system') ?>"></i>
              <div class="notif-content">
                <p><?= htmlspecialchars($notif['message']) ?></p>
                <?php if (($notif['notification_type'] ?? 'system') === 'system' && isset($notif['user_full_name'])): ?>
                  <small class="notif-user">From: <?= htmlspecialchars($notif['user_full_name']) ?></small>
                <?php elseif (($notif['notification_type'] ?? 'system') === 'request' && isset($notif['display_text'])): ?>
                  <small class="notif-user"><?= htmlspecialchars($notif['display_text']) ?></small>
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
        this.dropdownUpdateInterval = 5000;
        this.isUpdating = false;
        this.unreadCount = parseInt(document.getElementById('notifCount')?.textContent || 0);
        this.userAuthenticated = this.unreadCount >= 0;
        this.currentOffset = 0;
        this.hasMore = true;
        this.isLoadingMore = false;
        this.dropdownUpdateTimer = null;
        this.scrollThrottle = null;
        
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

        // Add scroll listener for infinite scroll
        this.setupInfiniteScroll();
    }

    setupInfiniteScroll() {
        const notifList = document.getElementById('notifList');
        if (!notifList) return;

        notifList.addEventListener('scroll', () => {
            if (this.scrollThrottle) {
                clearTimeout(this.scrollThrottle);
            }

            this.scrollThrottle = setTimeout(() => {
                this.checkScrollPosition();
            }, 100);
        });
    }

    checkScrollPosition() {
        if (this.isLoadingMore || !this.hasMore) return;

        const notifList = document.getElementById('notifList');
        if (!notifList) return;

        // Check if user has scrolled near the bottom (within 100px)
        const scrollPosition = notifList.scrollTop + notifList.clientHeight;
        const scrollThreshold = notifList.scrollHeight - 100;

        if (scrollPosition >= scrollThreshold) {
            this.loadMoreNotifications();
        }
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
    }

    startDropdownUpdates() {
        this.stopDropdownUpdates();
        this.dropdownUpdateTimer = setInterval(() => {
            this.updateNotifications(true);
        }, this.dropdownUpdateInterval);
    }

    stopDropdownUpdates() {
        if (this.dropdownUpdateTimer) {
            clearInterval(this.dropdownUpdateTimer);
            this.dropdownUpdateTimer = null;
        }
    }

    async updateNotifications(forceRefresh = false) {
        if (this.isUpdating || !this.userAuthenticated) return;
        
        const now = Date.now();
        if (now - this.lastUpdate < 1000 && !forceRefresh) return;

        this.isUpdating = true;
        
        try {
            const offset = forceRefresh ? 0 : this.currentOffset;
            const response = await fetch(`/templates/header/function/getNotification.php?limit=15&offset=${offset}`, {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                },
                cache: 'no-cache'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.error) {
                if (data.error === 'Not authenticated') {
                    this.userAuthenticated = false;
                    console.warn('User not authenticated for notifications');
                    return;
                }
                throw new Error(data.error);
            }
            
            this.handleNotificationUpdate(data, forceRefresh);
            
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        } finally {
            this.isUpdating = false;
            this.lastUpdate = Date.now();
        }
    }

    handleNotificationUpdate(data, forceRefresh = false) {
        const oldCount = this.unreadCount;
        this.unreadCount = data.unread_count;
        this.hasMore = data.has_more || false;
        
        this.updateCountBadge();
        
        if (forceRefresh || this.isDropdownVisible() || Math.abs(oldCount - this.unreadCount) > 0) {
            this.updateDropdownContent(data.notifications, forceRefresh);
        }
        
        if (this.unreadCount > oldCount && oldCount > 0) {
            this.showNewNotificationAlert(this.unreadCount - oldCount);
        }
    }

    async loadMoreNotifications() {
        if (this.isLoadingMore || !this.hasMore) return;
        
        this.isLoadingMore = true;
        
        try {
            // Show loading indicator
            this.showLoadingIndicator();
            
            this.currentOffset += 15;
            await this.updateNotifications(false);
            
        } finally {
            this.isLoadingMore = false;
            this.hideLoadingIndicator();
        }
    }

    showLoadingIndicator() {
        const notifList = document.getElementById('notifList');
        if (!notifList) return;

        // Remove existing loading indicator
        this.hideLoadingIndicator();

        const loadingHtml = `
            <div class="loading-indicator" id="loadingIndicator">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div class="loading-text">Loading more notifications...</div>
            </div>
        `;

        notifList.insertAdjacentHTML('beforeend', loadingHtml);
    }

    hideLoadingIndicator() {
        const loadingIndicator = document.getElementById('loadingIndicator');
        if (loadingIndicator) {
            loadingIndicator.remove();
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

    updateDropdownContent(notifications, forceRefresh = false) {
        const notifList = document.getElementById('notifList');
        if (!notifList) return;

        this.hideLoadingIndicator();

        if (forceRefresh) {
            this.currentOffset = 0;
            notifList.innerHTML = '';
        }

        if (notifications.length === 0 && forceRefresh) {
            notifList.innerHTML = `
                <div class="notif-item empty">
                    <div class="notif-content">
                        <p>No notifications</p>
                    </div>
                </div>
            `;
            return;
        }

        if (notifications.length > 0) {
            const existingNotifications = new Map();
            
            if (!forceRefresh) {
                notifList.querySelectorAll('.notif-item').forEach(item => {
                    const id = item.dataset.id;
                    if (id) {
                        existingNotifications.set(id, true);
                    }
                });
            }

            const notificationsHtml = notifications.map(notif => {
                const notificationType = notif.notification_type || 'system';
                const iconClass = this.getNotificationIcon(notif.action_type, notificationType);
                const itemId = notif.notification_id || notif.log_id;
                
                if (!forceRefresh && existingNotifications.has(itemId)) {
                    return '';
                }
                
                let displayHtml = '';
                if (notificationType === 'system' && notif.user_full_name) {
                    displayHtml = `<small class="notif-user">From: ${this.escapeHtml(notif.user_full_name)}</small>`;
                } else if (notificationType === 'request' && notif.display_text) {
                    displayHtml = `<small class="notif-user">${this.escapeHtml(notif.display_text)}</small>`;
                }
                
                return `
                    <div class="notif-item ${notif.action_type} ${notificationType} ${notif.is_read ? 'read' : 'unread'}" 
                         data-id="${itemId}"
                         data-item-id="${notif.item_id || ''}"
                         data-action-type="${notif.action_type}"
                         data-notification-type="${notificationType}">
                        <i class="${iconClass}"></i>
                        <div class="notif-content">
                            <p>${this.escapeHtml(notif.message)}</p>
                            ${displayHtml}
                            <span class="notif-time">${this.formatTime(notif.created_at)}</span>
                        </div>
                    </div>
                `;
            }).filter(html => html !== '').join('');

            if (forceRefresh) {
                notifList.innerHTML = notificationsHtml;
            } else if (notificationsHtml) {
                notifList.insertAdjacentHTML('beforeend', notificationsHtml);
        
                if (this.isNearBottom()) {
                    setTimeout(() => {
                        notifList.scrollTop = notifList.scrollHeight;
                    }, 100);
                }
            }

            this.updateNoMoreIndicator();

            this.addNotificationClickHandlers(forceRefresh);
        }
    }

    isNearBottom() {
        const notifList = document.getElementById('notifList');
        if (!notifList) return false;

        const scrollPosition = notifList.scrollTop + notifList.clientHeight;
        const scrollThreshold = notifList.scrollHeight - 50;
        return scrollPosition >= scrollThreshold;
    }

    updateNoMoreIndicator() {
        const notifList = document.getElementById('notifList');
        if (!notifList) return;

        // Remove existing "no more" indicator
        const existingNoMore = notifList.querySelector('.no-more-indicator');
        if (existingNoMore) {
            existingNoMore.remove();
        }

  
        if (!this.hasMore && notifList.children.length > 0) {
            const noMoreHtml = `
                <div class="no-more-indicator">
                    <div class="no-more-content">
                        <i class="fas fa-check-circle"></i>
                        <span>You're all caught up!</span>
                    </div>
                </div>
            `;
            notifList.insertAdjacentHTML('beforeend', noMoreHtml);
        }
    }

    addNotificationClickHandlers(forceRefresh = false) {
        const notifList = document.getElementById('notifList');
        if (!notifList) return;

        if (!forceRefresh) {
            const newItems = Array.from(notifList.querySelectorAll('.notif-item')).slice(-15);
            newItems.forEach(item => {
                item.addEventListener('click', this.handleNotificationClick.bind(this));
            });
        } else {
            notifList.querySelectorAll('.notif-item').forEach(item => {
                item.addEventListener('click', this.handleNotificationClick.bind(this));
            });
        }
    }

    handleNotificationClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const item = e.currentTarget;
    const actionType = item.dataset.actionType;
    const itemId = item.dataset.itemId;
    const notificationType = item.dataset.notificationType;
    const isAdmin = <?php echo (isAdmin($conn, $user_id) ? 'true' : 'false'); ?>;

    let targetUrl = '';
    let redirectParam = '';
    let redirectValue = '';
    
    // Extract the message to find request IDs
    const message = item.querySelector('.notif-content p').textContent;
    console.log('📝 Notification message:', message);
    
    if (notificationType === 'system') {
        // System notifications (inventory) use item_id
        if (actionType === 'item_deleted') {
            targetUrl = '/recentlyDeleted';
        } else {
            targetUrl = '/allItems';
        }
        redirectParam = 'item_id';
        redirectValue = itemId;
    } else if (notificationType === 'request') {
        // Request notifications - extract request ID from message
        if (isAdmin) {
            targetUrl = '/request';
        } else {
            targetUrl = '/my-request';
        }
        redirectParam = 'request_id';
        
        // Try to extract request ID from the message
        const requestMatch = message.match(/Request #?(\w+)/i) || 
                            message.match(/request #?(\w+)/i) ||
                            message.match(/ID:? #?(\w+)/i);
        
        if (requestMatch) {
            redirectValue = requestMatch[1];
            console.log(' Extracted request ID:', redirectValue);
        } else {
            console.log(' Could not extract request ID from message');
            // Fallback: use item_id if no request ID found
            redirectValue = itemId;
        }
    } else {
        // Default fallback
        targetUrl = '/allItems';
        redirectParam = 'item_id';
        redirectValue = itemId;
    }

    // Add redirect parameter if we have a value
    if (redirectValue && redirectValue !== '') {
        const url = new URL(targetUrl, window.location.origin);
        url.searchParams.set(redirectParam, redirectValue);
        targetUrl = url.pathname + url.search;
        
        console.log('🔗 Redirecting to:', targetUrl, 'with', redirectParam + ':', redirectValue);
    } else {
        console.log('🔗 Redirecting to:', targetUrl, '(no parameters)');
    }
    
    this.hideDropdown();
    window.location.href = targetUrl;
}
    async markAllAsRead() {
        try {
            const response = await fetch('/templates/header/function/markNotificationRead.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ mark_all: true })
            });
            
            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Failed to parse JSON response:', parseError);
                throw new Error('Server returned invalid JSON. Check console for details.');
            }
            
            if (response.ok && result.success) {
                this.unreadCount = 0;
                this.updateCountBadge();
                this.forceUpdate(true);
            } else {
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
        }
    }

    showDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown) {
            dropdown.style.display = 'block';
            this.currentOffset = 0;
            this.forceUpdate(true);
            this.startDropdownUpdates();
        }
    }

    hideDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
            this.stopDropdownUpdates();
        }
    }

    isDropdownVisible() {
        const dropdown = document.getElementById('notifDropdown');
        return dropdown && dropdown.style.display === 'block';
    }

    forceUpdate(forceRefresh = false) {
        this.lastUpdate = 0;
        this.updateNotifications(forceRefresh);
    }

    showNewNotificationAlert(count) {
        if (count > 0 && !this.isDropdownVisible()) {
            console.log(`You have ${count} new notification(s)`);
        }
    }

    getNotificationIcon(actionType, notificationType = 'system') {
        if (notificationType === 'request') {
            const requestIcons = {
                'approve': 'fas fa-check text-success',
                'decline': 'fas fa-times text-danger',
                'release': 'fas fa-box-open text-primary',
                'return': 'fas fa-undo text-warning',
                'void': 'fas fa-ban text-secondary',
                'cancel': 'fas fa-ban text-secondary',
                'received': 'fas fa-check-circle text-success'
            };
            return requestIcons[actionType] || 'fas fa-bell text-info';
        }
        
        const systemIcons = {
            'quantity_added': 'fas fa-plus-circle',
            'item_created': 'fas fa-box',
            'item_updated': 'fas fa-edit',
            'item_deleted': 'fas fa-trash',
            'low_stock': 'fas fa-exclamation-triangle',
            'general': 'fas fa-info-circle'
        };
        return systemIcons[actionType] || 'fas fa-bell';
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