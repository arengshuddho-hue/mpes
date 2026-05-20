// assets/js/notifications.js
// Dynamic notification polling, displaying, and read-status management.

document.addEventListener('DOMContentLoaded', () => {
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationList = document.getElementById('notificationList');
    const markAllReadBtn = document.getElementById('markAllReadBtn');

    if (!notificationBtn || !notificationDropdown) return;

    // Toggle dropdown
    notificationBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        notificationDropdown.classList.toggle('open');
        if (notificationDropdown.classList.contains('open')) {
            fetchNotifications();
        }
    });

    // Close dropdown on clicking outside
    document.addEventListener('click', (e) => {
        if (!notificationDropdown.contains(e.target) && !notificationBtn.contains(e.target)) {
            notificationDropdown.classList.remove('open');
        }
    });

    // Mark all as read
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            try {
                const apiPath = getApiPath();
                const response = await fetch(apiPath, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=mark_all_read'
                });
                const data = await response.json();
                if (data.success) {
                    fetchNotifications();
                }
            } catch (error) {
                console.error('Failed to mark all as read:', error);
            }
        });
    }

    // Determine path of API based on directory depth
    function getApiPath() {
        const isSubDir = window.location.pathname.includes('/patient/') || 
                         window.location.pathname.includes('/doctor/') || 
                         window.location.pathname.includes('/admin/');
        return isSubDir ? '../api/notifications.php' : 'api/notifications.php';
    }

    // Escape HTML to prevent XSS
    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }

    function getIconClass(type) {
        const classes = {
            appointment: 'appointment',
            prescription: 'prescription',
            report: 'report',
            system: 'system'
        };
        return classes[type] || 'system';
    }

    function getIcon(type) {
        const icons = {
            appointment: 'fa-calendar-check',
            prescription: 'fa-file-prescription',
            report: 'fa-flask-vial',
            system: 'fa-circle-exclamation'
        };
        return icons[type] || 'fa-bell';
    }

    async function fetchNotifications() {
        try {
            const apiPath = getApiPath();
            const response = await fetch(apiPath);
            
            if (response.status === 401) {
                // Not authenticated/session ended, hide badge
                if (notificationBadge) notificationBadge.style.display = 'none';
                return;
            }

            const data = await response.json();
            if (data.success) {
                // Update badge
                const unreadCount = parseInt(data.unread_count);
                if (notificationBadge) {
                    if (unreadCount > 0) {
                        notificationBadge.textContent = unreadCount;
                        notificationBadge.style.display = 'flex';
                    } else {
                        notificationBadge.style.display = 'none';
                    }
                }

                // Render in list if dropdown is open or loading first time
                if (notificationList) {
                    if (data.notifications.length === 0) {
                        notificationList.innerHTML = `
                            <div class="notification-empty">
                                <i class="fa-solid fa-bell-slash"></i>
                                <p>You have no notifications.</p>
                            </div>
                        `;
                    } else {
                        notificationList.innerHTML = data.notifications.map(n => {
                            const iconClass = getIconClass(n.type);
                            const icon = getIcon(n.type);
                            const unreadClass = n.is_read ? '' : 'unread';
                            const dot = n.is_read ? '' : '<div class="notification-status-dot"></div>';

                            return `
                                <div class="notification-item ${unreadClass}" data-id="${n.id}">
                                    <div class="notification-icon-wrapper ${iconClass}">
                                        <i class="fa-solid ${icon}"></i>
                                    </div>
                                    <div class="notification-content">
                                        <h4 class="notification-title">${escapeHTML(n.title)}</h4>
                                        <p class="notification-desc">${escapeHTML(n.message)}</p>
                                        <span class="notification-time">${n.time_ago}</span>
                                    </div>
                                    ${dot}
                                </div>
                            `;
                        }).join('');

                        // Attach individual item click actions
                        notificationList.querySelectorAll('.notification-item').forEach(item => {
                            item.addEventListener('click', async () => {
                                const notifId = item.dataset.id;
                                try {
                                    const responseMark = await fetch(apiPath, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: `action=mark_read&notification_id=${notifId}`
                                    });
                                    const dataMark = await responseMark.json();
                                    if (dataMark.success) {
                                        fetchNotifications();
                                    }
                                } catch (error) {
                                    console.error('Failed to mark notification read:', error);
                                }
                            });
                        });
                    }
                }
            }
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        }
    }

    // Initial badge load
    fetchNotifications();

    // Poll for notifications every 10 seconds
    setInterval(fetchNotifications, 10000);
});
