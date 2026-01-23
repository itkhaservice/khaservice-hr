/**
 * main.js - UX Enhancements for Khaservice HR
 * Sử dụng jQuery với Event Delegation để đảm bảo hoạt động trên mọi trang
 */

// 1. Toast Notification System
window.Toast = {
    init() {
        if ($('#toast-container').length === 0) {
            $('body').append('<div id="toast-container"></div>');
        }
    },

    show(type, title, message) {
        this.init();
        const iconMap = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
        const toast = $(`
            <div class="toast ${type}">
                <i class="fas ${iconMap[type]}"></i>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <i class="fas fa-times btn-close-toast" style="cursor:pointer; opacity:0.5;"></i>
            </div>
        `);
        $('#toast-container').append(toast);
        toast.find('.btn-close-toast').on('click', function() { $(this).parent().remove(); });
        setTimeout(() => {
            toast.css('animation', 'slideOut 0.3s ease-in forwards');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
};

// 2. Custom Confirm Modal
window.Modal = {
    currentCallback: null,
    init() {
        if ($('#confirmModal').length === 0) {
            const html = `
                <div id="confirmModal" class="modal-overlay">
                    <div class="modal-box">
                        <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="modal-title">Xác nhận hành động</div>
                        <div class="modal-desc"></div>
                        <div class="modal-actions">
                            <button class="btn btn-secondary btn-modal-cancel">Hủy bỏ</button>
                            <button class="btn btn-danger btn-modal-confirm">Xác nhận</button>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(html);
            $('#confirmModal .btn-modal-cancel').on('click', () => this.close());
        }
    },
    confirm(message, callback) {
        this.init();
        $('#confirmModal').find('.modal-desc').text(message);
        this.currentCallback = callback;
        $('#confirmModal').find('.btn-modal-confirm').off('click').on('click', () => {
            if (typeof this.currentCallback === 'function') this.currentCallback();
            this.close();
        });
        $('#confirmModal').css('display', 'flex');
    },
    close() { $('#confirmModal').hide(); }
};

// 3. Theme Manager
const Theme = {
    apply() {
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            $('body').addClass('dark-mode');
            $('#theme-toggle i').removeClass('fa-moon').addClass('fa-sun');
        }
    },
    toggle() {
        $('body').toggleClass('dark-mode');
        const isDark = $('body').hasClass('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        $('#theme-toggle i').toggleClass('fa-moon fa-sun');
    }
};

// Global Init
$(document).ready(function() {
    // Initial Theme Apply
    Theme.apply();

    // Sidebar State
    if (window.innerWidth > 768) {
        if (localStorage.getItem('sidebarState') === 'collapsed') {
            $('.sidebar').addClass('collapsed');
            $('.main-content').addClass('expanded');
        }
    }

    // EVENT DELEGATION - Bám vào document để đảm bảo nút luôn bấm được
    $(document).on('click', '#theme-toggle', function(e) {
        e.preventDefault();
        Theme.toggle();
    });

    $(document).on('click', '#sidebarToggle', function(e) {
        e.preventDefault();
        const isMobile = window.innerWidth <= 768;
        if (isMobile) {
            $('.sidebar').toggleClass('active');
            $('.sidebar-overlay').toggleClass('active');
        } else {
            $('.sidebar').toggleClass('collapsed');
            $('.main-content').toggleClass('expanded');
            localStorage.setItem('sidebarState', $('.sidebar').hasClass('collapsed') ? 'collapsed' : 'expanded');
        }
    });

    $(document).on('click', '.sidebar-overlay', function() {
        $('.sidebar').removeClass('active');
        $(this).removeClass('active');
    });

    $(document).on('click', '.user-info', function(e) {
        e.stopPropagation();
        $(this).find('.user-dropdown').toggleClass('show');
    });

    $(document).on('click', function() {
        $('.user-dropdown').removeClass('show');
    });

    // Password Toggles
    $(document).on('click', '.password-toggle-btn', function() {
        const $input = $('#' + $(this).data('target'));
        const isPass = $input.attr('type') === 'password';
        $input.attr('type', isPass ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    // Sidebar Auto Overlay
    if ($('.sidebar-overlay').length === 0) {
        $('body').append('<div class="sidebar-overlay"></div>');
    }
});