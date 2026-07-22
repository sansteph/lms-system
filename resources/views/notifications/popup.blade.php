@if(!empty($popupNotifications) && $popupNotifications->isNotEmpty())
    <div class="modal fade" id="lmsNotificationPopup" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Notifications</h5>
                        <div class="small text-muted">Latest updates from InnovatEdge</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3" style="max-height: 70vh; overflow-y: auto;">
                    <div id="lmsNotificationPopupList"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        Got it
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const notifications = @json($popupNotifications);
            const viewerKey = @json($notificationPopupViewerKey ?? 'guest');
            const storageKey = 'innovatedge-notification-popup-' + viewerKey;
            const modalElement = document.getElementById('lmsNotificationPopup');
            const listElement = document.getElementById('lmsNotificationPopupList');

            if (!modalElement || !listElement || !window.bootstrap || !Array.isArray(notifications)) {
                return;
            }

            let seen = [];

            try {
                seen = JSON.parse(localStorage.getItem(storageKey) || '[]');
            } catch (error) {
                seen = [];
            }

            const unseen = notifications.filter(function (notification) {
                return seen.indexOf(notification.signature) === -1;
            });

            if (!unseen.length) {
                return;
            }

            listElement.innerHTML = unseen.map(function (notification) {
                return `
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h6 class="fw-bold mb-0">${escapeHtml(notification.title)}</h6>
                            <span class="badge bg-primary">${escapeHtml(notification.institute)}</span>
                        </div>
                        <p class="mb-2">${escapeHtml(notification.message)}</p>
                        <div class="small text-muted">${notification.created_at ? 'Posted ' + escapeHtml(notification.created_at) : ''}</div>
                    </div>
                `;
            }).join('');

            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            modalElement.addEventListener('hidden.bs.modal', function () {
                const updatedSeen = Array.from(new Set(seen.concat(unseen.map(function (notification) {
                    return notification.signature;
                }))));

                localStorage.setItem(storageKey, JSON.stringify(updatedSeen));
            }, { once: true });

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }
        });
    </script>
@endif
