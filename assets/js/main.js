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
        const toggleBtn = document.getElementById('theme-toggle');
        if (!toggleBtn) return;

        const currentTheme = localStorage.getItem('theme');
        const icon = toggleBtn.querySelector('i');

        // Apply saved theme
        if (currentTheme === 'dark') {
            document.body.classList.add('dark-mode');
            icon.classList.replace('fa-moon', 'fa-sun');
        }

        toggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            
            let theme = 'light';
            if (document.body.classList.contains('dark-mode')) {
                theme = 'dark';
                icon.classList.replace('fa-moon', 'fa-sun');
            } else {
                icon.classList.replace('fa-sun', 'fa-moon');
            }
            
            localStorage.setItem('theme', theme);
        });
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
