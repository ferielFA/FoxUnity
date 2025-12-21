<?php
/**
 * ========================================
 * SYSTÈME DE NOTIFICATIONS UNIVERSEL
 * Tout-en-un : HTML + CSS + JavaScript
 * ========================================
 */

// Vérifier si l'utilisateur est connecté
if (!isset($currentUser)) {
    return;
}
?>

<!-- ========== STYLES CSS ========== -->
<style>
    /* Notification Container */
    .notification-container {
        position: relative;
    }

    .notification-btn {
        position: relative;
        background: rgba(255, 122, 0, 0.1);
        border: 2px solid rgba(255, 122, 0, 0.3);
        border-radius: 50%;
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #ff7a00;
        font-size: 18px;
    }

    .notification-btn:hover {
        background: rgba(255, 122, 0, 0.2);
        border-color: #ff7a00;
        transform: scale(1.1);
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: linear-gradient(135deg, #f44336, #d32f2f);
        color: white;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        border: 2px solid var(--bg-card, #1a1a1a);
        animation: pulse 2s infinite;
    }

    .notification-badge.hidden {
        display: none;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.7);
        }

        50% {
            transform: scale(1.1);
            box-shadow: 0 0 0 8px rgba(244, 67, 54, 0);
        }
    }

    .notification-dropdown {
        position: absolute;
        top: 60px;
        right: 0;
        width: 350px;
        max-height: 500px;
        background: var(--bg-card, #1a1a1a);
        border: 2px solid var(--border-color, rgba(255, 255, 255, 0.1));
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: none;
        overflow: hidden;
    }

    .notification-dropdown.active {
        display: block;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .notification-header {
        padding: 15px 20px;
        border-bottom: 2px solid var(--border-color, rgba(255, 255, 255, 0.1));
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-header h4 {
        color: var(--text-light, #fff);
        font-size: 16px;
        font-weight: 700;
        margin: 0;
    }

    .mark-all-read {
        background: transparent;
        border: none;
        color: #ff7a00;
        cursor: pointer;
        font-size: 12px;
        padding: 5px 10px;
        border-radius: 5px;
        transition: all 0.3s ease;
    }

    .mark-all-read:hover {
        background: rgba(255, 122, 0, 0.1);
    }

    .notification-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 15px 20px;
        border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .notification-item:hover {
        background: rgba(255, 122, 0, 0.05);
    }

    .notification-item.unread {
        background: rgba(255, 122, 0, 0.1);
        border-left: 3px solid #ff7a00;
    }

    .notification-icon {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ff7a00, #ff4f00);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
        flex-shrink: 0;
    }

    .notification-content {
        flex: 1;
    }

    .notification-title {
        color: var(--text-light, #fff);
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .notification-text {
        color: var(--text-gray, #aaa);
        font-size: 12px;
        line-height: 1.4;
    }

    .notification-time {
        color: var(--text-gray, #aaa);
        font-size: 11px;
        margin-top: 5px;
    }

    .notification-empty {
        padding: 40px 20px;
        text-align: center;
        color: var(--text-gray, #aaa);
        font-size: 14px;
    }

    /* Mode clair */
    :root[data-theme="light"] .notification-dropdown {
        background: #ffffff;
        border-color: rgba(0, 0, 0, 0.1);
    }

    :root[data-theme="light"] .notification-header h4,
    :root[data-theme="light"] .notification-title {
        color: #1a1a1a;
    }

    :root[data-theme="light"] .notification-text,
    :root[data-theme="light"] .notification-time,
    :root[data-theme="light"] .notification-empty {
        color: #666;
    }

    :root[data-theme="light"] .notification-item {
        border-bottom-color: rgba(0, 0, 0, 0.1);
    }

    :root[data-theme="light"] .notification-badge {
        border-color: #ffffff;
    }

    :root[data-theme="light"] .notification-btn {
        color: #ff7a00;
    }
</style>

<!-- ========== HTML ========== -->
<div class="notification-container" id="notification-container">
    <button class="notification-btn" id="notification-btn" title="Notifications">
        <i class="fas fa-bell"></i>
        <span class="notification-badge hidden" id="notification-badge">0</span>
    </button>
    <div class="notification-dropdown" id="notification-dropdown">
        <div class="notification-header">
            <h4>Notifications</h4>
            <button class="mark-all-read" id="mark-all-read">Tout marquer comme lu</button>
        </div>
        <div class="notification-list" id="notification-list">
            <div class="notification-empty">Aucune nouvelle réclamation</div>
        </div>
    </div>
</div>

<!-- ========== JAVASCRIPT ========== -->
<script>
    (function () {
        'use strict';

        // ========== VARIABLES ==========
        let unreadCount = 0;
        let lastCheckTime = 0;
        let isInitialLoad = true;
        let globalAudioContext = null;
        let audioEnabled = false;

        // Éléments DOM
        const notificationBtn = document.getElementById('notification-btn');
        const notificationDropdown = document.getElementById('notification-dropdown');
        const notificationBadge = document.getElementById('notification-badge');
        const notificationList = document.getElementById('notification-list');
        const markAllReadBtn = document.getElementById('mark-all-read');

        if (!notificationBtn || !notificationDropdown) {
            console.warn('⚠️ Système de notifications non trouvé');
            return;
        }

        // ========== AUDIO ==========
        function enableAudio() {
            if (!audioEnabled && (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined')) {
                try {
                    globalAudioContext = new (window.AudioContext || window.webkitAudioContext)();

                    if (globalAudioContext.state === 'suspended') {
                        globalAudioContext.resume().then(() => {
                            audioEnabled = true;
                            console.log('✅ Audio activé');
                        }).catch(e => {
                            audioEnabled = true;
                        });
                    } else {
                        audioEnabled = true;
                        console.log('✅ Audio activé');
                    }
                } catch (e) {
                    console.log('Erreur activation audio:', e);
                }
            }
        }

        function playBeep() {
            try {
                if (!globalAudioContext) return;

                const notes = [
                    { freq: 523.25, delay: 0 },     // Do
                    { freq: 659.25, delay: 150 },   // Mi
                    { freq: 783.99, delay: 300 }    // Sol
                ];

                notes.forEach((note, index) => {
                    setTimeout(() => {
                        if (!globalAudioContext || globalAudioContext.state === 'closed') return;

                        const osc = globalAudioContext.createOscillator();
                        const gain = globalAudioContext.createGain();

                        osc.connect(gain);
                        gain.connect(globalAudioContext.destination);

                        osc.frequency.value = note.freq;
                        osc.type = 'sine';

                        const t = globalAudioContext.currentTime;
                        gain.gain.setValueAtTime(0, t);
                        gain.gain.linearRampToValueAtTime(index === 2 ? 0.5 : 0.4, t + 0.05);
                        gain.gain.linearRampToValueAtTime(0, t + 0.2);

                        osc.start(t);
                        osc.stop(t + 0.2);
                    }, note.delay);
                });
            } catch (e) {
                console.error('❌ Erreur playBeep:', e);
            }
        }

        function playNotificationSound() {
            if (!audioEnabled) enableAudio();

            if (!globalAudioContext) {
                globalAudioContext = new (window.AudioContext || window.webkitAudioContext)();
            }

            if (globalAudioContext.state === 'suspended') {
                globalAudioContext.resume().then(() => playBeep());
            } else {
                playBeep();
            }
        }

        // ========== NOTIFICATIONS ==========
        function updateNotificationBadge() {
            if (unreadCount > 0) {
                notificationBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                notificationBadge.classList.remove('hidden');
            } else {
                notificationBadge.classList.add('hidden');
            }
        }

        function getIconForType(type) {
            switch (type) {
                case 'user': return '<i class="fas fa-user-plus" style="background: #2196F3;"></i>';
                case 'shop': return '<i class="fas fa-shopping-cart" style="background: #FFC107; color: #000;"></i>';
                case 'trade': return '<i class="fas fa-exchange-alt" style="background: #9C27B0;"></i>';
                case 'support':
                default: return '<i class="fas fa-headset"></i>';
            }
        }

        function addNotification(notif, prepend = true) {
            const notificationItem = document.createElement('div');
            notificationItem.className = 'notification-item unread';
            notificationItem.setAttribute('data-notification-id', notif.id || '');
            notificationItem.setAttribute('data-type', notif.type || 'support');

            const title = notif.title || 'Notification';
            const text = notif.text || '';
            const time = notif.time || 'À l\'instant';

            // Custom icon based on type
            const iconHtml = getIconForType(notif.type);

            notificationItem.innerHTML = `
            <div class="notification-icon">
                ${iconHtml}
            </div>
            <div class="notification-content">
                <div class="notification-title">${escapeHtml(title)}</div>
                <div class="notification-text">${escapeHtml(text)}</div>
                <div class="notification-time">${escapeHtml(time)}</div>
            </div>
        `;

            notificationItem.addEventListener('click', function () {
                this.classList.remove('unread');
                unreadCount = Math.max(0, unreadCount - 1);
                updateNotificationBadge();

                // Redirect based on type
                if (notif.type === 'user') window.location.href = 'users.php';
                else if (notif.type === 'shop') window.location.href = 'shopb.php';
                else if (notif.type === 'trade') window.location.href = 'tradingb.php';
                else if (notif.id) window.location.href = 'reclamback.php#reclamation-' + notif.id;
            });

            const emptyMsg = notificationList.querySelector('.notification-empty');
            if (emptyMsg) emptyMsg.remove();

            if (prepend) {
                notificationList.insertBefore(notificationItem, notificationList.firstChild);
            } else {
                notificationList.appendChild(notificationItem);
            }
        }

        // Unused in new logic but kept for safety
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function loadExistingNotifications() {
            // Initial load using same API but with logic to get recent ones
            await checkNewReclamations();
        }

        async function checkNewReclamations() {
            try {
                // Relatif à view/back/ (dashboard.php context) -> api/get_notifications.php
                const response = await fetch('api/get_notifications.php?last_check=' + lastCheckTime);
                if (!response.ok) throw new Error('Network response was not ok');

                const data = await response.json();

                if (data.success && data.new_count > 0) {
                    if (lastCheckTime === 0 || isInitialLoad) {
                        console.log('🔔 Initial notification load');
                        notificationList.innerHTML = ''; // Clear empty message

                        if (data.notifications && data.notifications.length > 0) {
                            data.notifications.forEach(notif => addNotification(notif, false)); // Append in order received (desc)
                        }

                        unreadCount = data.new_count;
                        updateNotificationBadge();
                        // Don't play sound on initial load to avoid annoyance on refresh
                    } else {
                        console.log('📋 New notifications detected:', data.new_count);
                        unreadCount += data.new_count;
                        updateNotificationBadge();

                        if (data.notifications && data.notifications.length > 0) {
                            // Prepend new ones
                            data.notifications.reverse().forEach(notif => addNotification(notif, true));
                        }

                        playNotificationSound();

                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification('Nine Tailed Fox', {
                                body: `${data.new_count} new notification(s)`,
                                icon: '../images/Nine__1_-removebg-preview.png',
                                silent: true
                            });
                        }
                    }
                    lastCheckTime = Date.now();
                } else if (data.success) {
                    if (lastCheckTime === 0) lastCheckTime = Date.now();
                }
            } catch (error) {
                console.error('Notification check error:', error);
            }
        }

        // ========== ÉVÉNEMENTS ==========
        notificationBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('active');
        });

        document.addEventListener('click', function (e) {
            if (!notificationDropdown.contains(e.target) && !notificationBtn.contains(e.target)) {
                notificationDropdown.classList.remove('active');
            }
        });

        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function () {
                unreadCount = 0;
                updateNotificationBadge();
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
            });
        }

        // ========== INITIALISATION ==========
        window.addEventListener('load', () => setTimeout(enableAudio, 100));
        document.addEventListener('click', () => { if (!audioEnabled) enableAudio(); }, { once: true });

        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        // Charger notifications existantes
        loadExistingNotifications();

        // Vérifier nouvelles réclamations toutes les 30 secondes
        setInterval(checkNewReclamations, 30000);

        // Première vérification après 2 secondes
        setTimeout(() => {
            checkNewReclamations();
            setTimeout(() => { isInitialLoad = false; }, 1000);
        }, 2000);

        console.log('✅ Système de notifications initialisé');

    })();
</script>