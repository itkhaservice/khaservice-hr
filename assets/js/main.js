/**
 * main.js - UX Enhancements for Khaservice HR
 */

// 1. Toast Notification System
const Toast = {
    container: null,
    
    init() {
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    },

    show(type, title, message) {
        this.init();
        
        const iconMap = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            info: 'fa-info-circle',
            warning: 'fa-exclamation-triangle'
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="fas ${iconMap[type]}"></i>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <i class="fas fa-times" style="cursor:pointer; opacity:0.5;" onclick="this.parentElement.remove()"></i>
        `;

        this.container.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    success(msg) { this.show('success', 'Thành công', msg); },
    error(msg) { this.show('error', 'Lỗi', msg); },
    info(msg) { this.show('info', 'Thông tin', msg); },
    warning(msg) { this.show('warning', 'Cảnh báo', msg); }
};

// 2. Custom Confirm Modal
const Modal = {
    currentCallback: null,

    init() {
        if (!document.getElementById('confirmModal')) {
            const html = `
                <div id="confirmModal" class="modal-overlay">
                    <div class="modal-box">
                        <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="modal-title">Xác nhận hành động</div>
                        <div class="modal-desc">Bạn có chắc chắn muốn thực hiện hành động này không? Hành động này không thể hoàn tác.</div>
                        <div class="modal-actions">
                            <button class="btn btn-secondary" onclick="Modal.close()">Hủy bỏ</button>
                            <button id="modalConfirmBtn" class="btn btn-danger">Xác nhận</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', html);
        }
    },

    confirm(message, callback) {
        this.init();
        const modal = document.getElementById('confirmModal');
        modal.querySelector('.modal-desc').textContent = message;
        
        this.currentCallback = callback;
        
        // Setup confirm button
        const btn = document.getElementById('modalConfirmBtn');
        // Remove old listeners to prevent stacking
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        
        newBtn.addEventListener('click', () => {
            if (typeof Modal.currentCallback === 'function') {
                Modal.currentCallback();
            }
            Modal.close();
        });

        modal.style.display = 'flex';
    },

    close() {
        document.getElementById('confirmModal').style.display = 'none';
    }
};

// 3. Page Loader
const Loader = {
    start() {
        if (!document.querySelector('.page-loader')) {
            const loader = document.createElement('div');
            loader.className = 'page-loader';
            loader.innerHTML = '<div class="bar"></div>';
            document.body.prepend(loader);
        }
        setTimeout(() => document.querySelector('.page-loader .bar').style.width = '70%', 100);
    },

    finish() {
        const bar = document.querySelector('.page-loader .bar');
        if (bar) {
            bar.style.width = '100%';
            setTimeout(() => {
                const loader = document.querySelector('.page-loader');
                if (loader) loader.remove();
            }, 300);
        }
    }
};

// 4. Theme Toggle (Dark/Light Mode)
const Theme = {
    init() {
        try {
            const toggleBtn = document.getElementById('theme-toggle');
            if (!toggleBtn) return;

            const currentTheme = localStorage.getItem('theme');
            const icon = toggleBtn.querySelector('i');

            // Apply saved theme
            if (currentTheme === 'dark') {
                document.body.classList.add('dark-mode');
                if (icon) icon.classList.replace('fa-moon', 'fa-sun');
            }

            toggleBtn.addEventListener('click', () => {
                document.body.classList.toggle('dark-mode');
                
                let theme = 'light';
                if (document.body.classList.contains('dark-mode')) {
                    theme = 'dark';
                    if (icon) icon.classList.replace('fa-moon', 'fa-sun');
                } else {
                    if (icon) icon.classList.replace('fa-sun', 'fa-moon');
                }
                
                localStorage.setItem('theme', theme);
            });
        } catch (e) {
            console.error('Theme init error:', e);
        }
    }
};

// 5. Password Toggle
function togglePassword(inputId, iconElement) {
    const input = document.getElementById(inputId);
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye');
    }
}

// Global Init
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Theme
    Theme.init();

    // --- Sidebar Logic (Mobile & Desktop) ---
    // Use Event Delegation for robustness
    document.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('#sidebarToggle');
        if (!toggleBtn) return;

        e.stopPropagation();
        
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const overlay = document.querySelector('.sidebar-overlay');

        // Create overlay if missing (lazy init)
        if (!overlay && window.innerWidth <= 768) {
            const newOverlay = document.createElement('div');
            newOverlay.className = 'sidebar-overlay';
            document.body.appendChild(newOverlay);
            // Re-select
            // overlay = newOverlay; 
            // Note: complex to re-assign const, but strictly checking DOM next time is fine.
            // For now, let's just use the newly created one if we need it immediately
             newOverlay.classList.toggle('active'); // Immediate toggle for this click
        } else if (overlay && window.innerWidth <= 768) {
             overlay.classList.toggle('active');
        }

        if (sidebar) {
            if (window.innerWidth <= 768) {
                // Mobile: Toggle Active
                sidebar.classList.toggle('active');
            } else {
                // Desktop: Toggle Collapsed
                sidebar.classList.toggle('collapsed');
                if (mainContent) {
                    mainContent.classList.toggle('expanded');
                }
                
                // Save state
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
            }
        }
    });

    // Sidebar Overlay Click (Delegation)
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('sidebar-overlay')) {
            const sidebar = document.querySelector('.sidebar');
            const overlay = e.target;
            
            if (sidebar) sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    });

    // 1. Create Overlay for Mobile (Pre-emptive)
    if (!document.querySelector('.sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    // 2. Restore State on Load (Desktop Only)
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    if (window.innerWidth > 768 && sidebar) {
        const savedState = localStorage.getItem('sidebarState');
        if (savedState === 'collapsed') {
            sidebar.classList.add('collapsed');
            if (mainContent) mainContent.classList.add('expanded');
        }
    }
    
    // 3. Auto-close on Link Click (Mobile only)
    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    const overlay = document.querySelector('.sidebar-overlay');
                    if (overlay) overlay.classList.remove('active');
                }
            });
        });
    }

    // Intercept form submissions for loader
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => Loader.start());
    });

    // Auto-setup password toggles
    document.querySelectorAll('.password-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const icon = this.querySelector('i');
            togglePassword(targetId, icon);
        });
    });
});
