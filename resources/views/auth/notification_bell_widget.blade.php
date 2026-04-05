<div class="notification-bell-container">
    <div class="notification-bell" onclick="nwOpenNotificationModal()">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 22a2 2 0 0 0 2-2H10a2 2 0 0 0 2 2Zm6-6V11a6 6 0 1 0-12 0v5l-2 2v1h16v-1l-2-2Z" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        @if($user->unreadNotifications->count() > 0)
            <span class="notification-badge">{{ $user->unreadNotifications->count() }}</span>
        @endif
    </div>
</div>

<div id="notificationModalOverlay" class="notification-modal-overlay" style="display:none;">
    <div class="notification-modal unity-theme">
        <div class="notification-modal-header">
            <span>Notifications</span>
            <button class="notification-modal-close" onclick="nwCloseNotificationModal()">&times;</button>
        </div>
        <div class="notification-modal-body">
            @if($user->unreadNotifications->count() === 0)
                <div class="notification-empty">No new notifications.</div>
            @else
                <ul class="notification-list">
                    @foreach($user->unreadNotifications as $notification)
                        @php
                            $notificationId = $notification->notifications_id ?? $notification->id;
                        @endphp
                        <li class="notification-item">
                            <div class="notification-message">
                                {{ $notification->data['message'] ?? 'You have a new notification.' }}
                                <span class="notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                            <form method="POST" action="{{ route('notifications.markAsRead', ['notification' => $notificationId]) }}">
                                @csrf
                                <button type="submit" class="mark-as-read-btn">Mark as read</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

<style>
    .notification-bell-container {
        margin-left: auto;
    }

    .notification-bell {
        position: relative;
        width: 40px;
        height: 40px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        background: rgba(15, 23, 42, 0.28);
        border: 1px solid rgba(125, 211, 252, 0.45);
        box-shadow: 0 6px 16px rgba(2, 132, 199, 0.22);
        transition: transform 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }

    .notification-bell:hover {
        transform: translateY(-1px);
        background: rgba(14, 116, 144, 0.45);
        box-shadow: 0 8px 20px rgba(2, 132, 199, 0.32);
    }

    .notification-badge {
        position: absolute;
        top: -6px;
        right: -7px;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
        border: 2px solid #2f3f4a;
        color: #fff;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        box-shadow: 0 6px 14px rgba(239, 68, 68, 0.45);
    }

    .notification-modal.unity-theme {
        background: #034a50;
        border-radius: 18px;
        box-shadow: 0 12px 40px rgba(15,23,42,0.28);
        border: 2px solid #1f8a90;
        color: #e2e8f0;
    }

    .notification-modal.unity-theme .notification-modal-header {
        background: #1f8a90;
        color: #fff;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
        border-bottom: 1px solid #2a9fa6;
    }

    .notification-modal.unity-theme .notification-modal-close {
        color: #fff;
    }

    .notification-modal.unity-theme .notification-modal-close:hover {
        color: #f87171;
    }

    .notification-modal.unity-theme .notification-modal-body {
        background: #034a50;
        color: #e2e8f0;
    }

    .notification-modal.unity-theme .notification-list {
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .notification-modal.unity-theme .notification-item {
        background: transparent;
        border-radius: 10px;
        margin-bottom: 10px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }

    .notification-modal.unity-theme .notification-message {
        color: #e0e7ef;
        font-size: 15px;
    }

    .notification-modal.unity-theme .notification-time {
        display: block;
        font-size: 12px;
        color: #9ad6df;
        margin-top: 2px;
    }

    .notification-modal.unity-theme .mark-as-read-btn {
        background: linear-gradient(90deg, #1f909a 0%, #2aa8b0 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 5px 12px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
        transition: background 0.18s;
        white-space: nowrap;
    }

    .notification-modal.unity-theme .mark-as-read-btn:hover {
        background: linear-gradient(90deg, #2aa8b0 0%, #1f909a 100%);
    }

    .notification-modal.unity-theme .notification-empty {
        color: #9ad6df;
        text-align: center;
        padding: 18px 0;
    }
</style>

<script>
    function nwOpenNotificationModal() {
        document.getElementById('notificationModalOverlay').style.display = 'flex';
    }

    function nwCloseNotificationModal() {
        document.getElementById('notificationModalOverlay').style.display = 'none';
    }

    document.addEventListener('click', function(event) {
        var overlay = document.getElementById('notificationModalOverlay');
        var modal = document.querySelector('.notification-modal');
        var bell = document.querySelector('.notification-bell');

        if (!overlay || !modal || !bell) {
            return;
        }

        if (overlay.style.display === 'flex' && !modal.contains(event.target) && !bell.contains(event.target)) {
            overlay.style.display = 'none';
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            nwCloseNotificationModal();
        }
    });
</script>
