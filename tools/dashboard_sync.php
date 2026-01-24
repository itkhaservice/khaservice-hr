<?php
// UNIFIED SYNC DASHBOARD (DATABASE & FILES)
// Chạy trên máy Local

$HOSTING_API_DB = "http://khaservice.free.nf/api/sync.php";
$HOSTING_API_FILE = "http://khaservice.free.nf/api/sync_files_server.php";
$API_KEY = "KHA_SERVICE_SECURE_SYNC_2026";
$MASTER_KEY = "KHA_SERVICE_FILE_SYNC_MASTER_KEY_2026";

// Config & Setup
$_SERVER['SERVER_NAME'] = 'localhost';
require_once '../config/db.php';
$CONFIG_FILE = 'agent_config.json';

// --- HELPERS ---
function load_config() {
    global $CONFIG_FILE;
    return file_exists($CONFIG_FILE) ? json_decode(file_get_contents($CONFIG_FILE), true) : null;
}
function save_config($data) {
    global $CONFIG_FILE;
    file_put_contents($CONFIG_FILE, json_encode($data));
}
$config = load_config();
$isAdmin = function_exists('is_admin') ? is_admin() : false;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Khaservice Sync Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #fff;
            --text-main: #000;
            --text-sub: #64748b;
            --card-bg: #fff;
            --border-color: #e2e8f0;
            --log-bg: #1e293b;
        }
        body.dark-mode {
            --bg-body: #1e293b; /* Match admin dark bg */
            --text-main: #f1f5f9;
            --text-sub: #94a3b8;
            --card-bg: #1e293b;
            --border-color: #334155;
            --log-bg: #0f172a;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg-body); color: var(--text-main); margin: 0; padding: 0; display: block; height: auto; }
        .dashboard { background: var(--bg-body); width: 100%; border-radius: 0; box-shadow: none; overflow: hidden; display: flex; flex-direction: column; height: 100vh; }
        .header { background: #24a25c; color: #fff; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; font-size: 1.1rem; font-weight: 600; }
        .content { flex: 1; padding: 25px; overflow-y: auto; }
        .card { border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 20px; background: var(--card-bg); transition: 0.2s; }
        .card:hover { border-color: #24a25c; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; color: var(--text-main); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; font-size: 1rem; display: flex; align-items: center; gap: 10px; }
        .text-muted { color: var(--text-sub); }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; font-family: 'Inter', sans-serif; }
        .btn-primary { background: #24a25c; color: #fff; } .btn-primary:hover { background: #1b7a43; }
        .btn-success { background: #3b82f6; color: #fff; } .btn-success:hover { background: #2563eb; }
        .btn-secondary { background: #f1f5f9; color: #475569; } .btn-secondary:hover { background: #e2e8f0; }
        body.dark-mode .btn-secondary { background: #334155; color: #f1f5f9; border: 1px solid #475569; }
        body.dark-mode .btn-secondary:hover { background: #475569; }
        .log-box { background: var(--log-bg); color: #4ade80; font-family: 'Consolas', monospace; padding: 15px; border-radius: 6px; height: 250px; overflow-y: auto; font-size: 0.85rem; margin-top: 15px; line-height: 1.5; border: 1px solid var(--border-color); }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-body); }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        body.dark-mode ::-webkit-scrollbar-track { background: #0f172a; }
        body.dark-mode ::-webkit-scrollbar-thumb { background: #475569; }

        /* Toast */
        #toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { display: flex; align-items: center; background: #fff; border-left: 4px solid; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 15px 20px; margin-bottom: 10px; border-radius: 4px; min-width: 300px; transform: translateX(100%); animation: slideIn 0.3s ease forwards; }
        .toast.success { border-color: #24a25c; } .toast.success i { color: #24a25c; }
        .toast.error { border-color: #dc2626; } .toast.error i { color: #dc2626; }
        .toast.info { border-color: #3b82f6; } .toast.info i { color: #3b82f6; }
        .toast-content { margin-left: 15px; flex: 1; }
        .toast-title { font-weight: 600; margin-bottom: 2px; color: #333; }
        .toast-message { font-size: 0.9rem; color: #666; }
        body.dark-mode .toast { background: #1e293b; color: #fff; }
        body.dark-mode .toast-title { color: #fff; }
        body.dark-mode .toast-message { color: #ccc; }
        @keyframes slideIn { to { transform: translateX(0); } }
        @keyframes slideOut { to { transform: translateX(120%); opacity: 0; } }
        
        /* Help Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); z-index: 10000; align-items: center; justify-content: center; }
        .modal-content { background: var(--card-bg); padding: 30px; border-radius: 12px; width: 600px; max-width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: relative; }
        /* Progress Bar */
        .progress-container { width: 100%; background-color: #e2e8f0; border-radius: 4px; height: 10px; margin-top: 15px; overflow: hidden; display: none; }
        .progress-bar { width: 0%; height: 100%; background-color: #24a25c; transition: width 0.3s ease; }
        .progress-text { font-size: 0.85rem; color: var(--text-sub); text-align: center; margin-top: 5px; display: none; }
    </style>
    <script>
        // Apply theme from URL param
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
            document.body.classList.add('dark-mode');
        }
        
        // Listen for messages from parent
        window.addEventListener('message', function(event) {
            if (event.data === 'theme-dark') {
                document.body.classList.add('dark-mode');
            } else if (event.data === 'theme-light') {
                document.body.classList.remove('dark-mode');
            }
        });
    </script>
</head>
<body>

<div class="dashboard">
    <div class="header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <h2><i class="fas fa-sync-alt"></i> Trung tâm Đồng bộ Dữ liệu</h2>
            <button onclick="document.getElementById('helpModal').style.display='flex'" style="background: none; border: none; color: #fff; cursor: pointer; font-size: 1.1rem; opacity: 0.8;" title="Hướng dẫn sử dụng"><i class="fas fa-question-circle"></i></button>
        </div>
        <?php if ($config): ?>
            <span style="font-size: 0.9rem; background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 20px;">
                <i class="fas fa-desktop"></i> <?php echo htmlspecialchars($config['node_name']); ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- Help Modal -->
    <div id="helpModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Hướng dẫn sử dụng Hybrid Sync</h3>
                <button class="close-modal" onclick="document.getElementById('helpModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <div class="guide-step">
                    <h4>1. Hybrid Storage là gì?</h4>
                    <p>Đây là giải pháp lưu trữ kết hợp: Dữ liệu chính nằm trên Cloud (Hosting) để truy cập mọi lúc mọi nơi, nhưng các Tệp tin nặng (Hồ sơ, Ảnh) sẽ được tự động tải về lưu trữ vĩnh viễn tại Máy chủ nội bộ (Local) của công ty.</p>
                </div>
                <div class="guide-step">
                    <h4>2. Cách hoạt động</h4>
                    <p>- <strong>Hosting:</strong> Chỉ lưu file tạm thời trong 60 ngày. Sau đó file sẽ bị xóa để tiết kiệm dung lượng.<br>
                    - <strong>Máy Local:</strong> Cần chạy tool này hàng ngày để tải file mới về. File đã tải sẽ được lưu mãi mãi.</p>
                </div>
                <div class="guide-step">
                    <h4>3. Quy trình kết nối (Quan trọng)</h4>
                    <p>- <strong>Admin (Quản trị viên):</strong> Hệ thống tự động nhận diện và điền sẵn mã khóa. Bạn chỉ cần đặt tên máy và bấm Kết nối.<br>
                    - <strong>Nhân viên:</strong> Bạn cần liên hệ Admin để xin "Mã khóa Master" mới có thể đăng ký máy tính này vào hệ thống.</p>
                </div>
                <div class="guide-step">
                    <h4>4. Khôi phục dữ liệu</h4>
                    <p>Chức năng "Đồng bộ Database" giúp bạn sao chép toàn bộ dữ liệu từ Cloud về máy này. Rất hữu ích để Backup dữ liệu an toàn.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <?php if (!$config): ?>
            <!-- REGISTER FORM -->
            <div class="card">
                <h3><i class="fas fa-plug"></i> Kết nối Máy chủ này</h3>
                <p class="text-muted">Lần đầu sử dụng? Hãy đặt tên cho máy tính này để server nhận diện.</p>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="text" id="nodeName" placeholder="Tên máy (VD: MAY_VAN_PHONG_01)" style="padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                    
                    <div style="position: relative;">
                        <?php 
                            // Nếu là Admin, để mặc định là text (hiện), nếu không là password (ẩn)
                            $inputType = $isAdmin ? 'text' : 'password'; 
                            $iconClass = $isAdmin ? 'fa-eye-slash' : 'fa-eye';
                        ?>
                        <input type="<?php echo $inputType; ?>" id="masterKey" value="<?php echo $isAdmin ? $MASTER_KEY : ''; ?>" placeholder="Nhập Mã khóa Master (Hỏi Admin)" autocomplete="new-password" style="padding: 10px; border: 1px solid #ccc; border-radius: 6px; width: 100%; padding-right: 40px; box-sizing: border-box;">
                        <i class="fas <?php echo $iconClass; ?>" id="toggleKeyIcon" onclick="toggleKey()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #64748b;"></i>
                    </div>
                    
                    <div class="manual-setup" style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #eee; display:none;">
                        <p style="font-size: 0.85rem; color: #d97706; margin-bottom: 5px;"><i class="fas fa-exclamation-triangle"></i> Nếu tự động kết nối thất bại (do Hosting chặn), hãy làm thủ công:</p>
                        <ol style="font-size: 0.8rem; padding-left: 20px; color: #64748b; margin-top: 0;">
                            <li>Truy cập link này trên trình duyệt: <a href="<?php echo $HOSTING_API_FILE; ?>?action=register&name=MAY_LOCAL&master_key=<?php echo $MASTER_KEY; ?>" target="_blank" style="color:#2563eb;">Bấm vào đây để lấy Token</a></li>
                            <li>Copy đoạn mã <code>auth_token</code> nhận được.</li>
                            <li>Dán vào ô bên dưới và bấm Lưu.</li>
                        </ol>
                        <input type="text" id="manualToken" placeholder="Dán Auth Token vào đây" style="padding: 8px; border: 1px solid #ccc; border-radius: 6px; width: 100%;">
                        <button class="btn btn-secondary" onclick="saveManualToken()" style="margin-top: 5px;">Lưu Token Thủ công</button>
                    </div>
                    
                    <div style="margin-top: 10px; text-align: center;">
                        <a href="javascript:void(0)" onclick="$('.manual-setup').slideToggle()" style="font-size: 0.8rem; color: #64748b; text-decoration: underline;">Gặp sự cố kết nối?</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- SYNC ACTIONS -->
            <div class="card">
                <h3><i class="fas fa-database"></i> 1. Dữ liệu Hệ thống (Database)</h3>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <p style="margin: 0; color: #64748b;">Đồng bộ Nhân viên, Chấm công, Lương...</p>
                    <button class="btn btn-primary" id="btnSyncDB" onclick="startSync('db')">
                        <i class="fas fa-play"></i> Chạy Đồng bộ
                    </button>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-file-archive"></i> 2. Tệp tin đính kèm (Files)</h3>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <p style="margin: 0; color: #64748b;">Tải về các bản scan hồ sơ, hình ảnh...</p>
                    <button class="btn btn-success" id="btnSyncFile" onclick="startSync('file')">
                        <i class="fas fa-download"></i> Tải File mới
                    </button>
                </div>
            </div>

            <div class="card" style="background: #f8fafc; border: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <strong>Tiến trình xử lý:</strong>
                    <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;" onclick="clearLog()">Xóa log</button>
                </div>
                
                <div class="progress-container" id="progressBarContainer">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div class="progress-text" id="progressText">Đang chuẩn bị...</div>

                <div class="log-box" id="consoleLog">Sẵn sàng...</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="toast-container"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function showToast(type, title, message) {
        const iconMap = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
        const toast = $(`
            <div class="toast ${type}">
                <i class="fas ${iconMap[type]}"></i>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
            </div>
        `);
        $('#toast-container').append(toast);
        setTimeout(() => {
            toast.css('animation', 'slideOut 0.3s ease-in forwards');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    function log(msg, type='info') {
        const color = type === 'error' ? '#f87171' : (type === 'success' ? '#4ade80' : '#cbd5e1');
        const time = new Date().toLocaleTimeString();
        $('#consoleLog').append(`<div style="color:${color}">[${time}] ${msg}</div>`);
        $('#consoleLog').scrollTop($('#consoleLog')[0].scrollHeight);
    }
    function clearLog() { $('#consoleLog').html(''); }

    function registerNode() {
        const name = $('#nodeName').val();
        const key = $('#masterKey').val() || ''; // Get key if input exists
        
        if (!name) return showToast('error', 'Lỗi', 'Vui lòng nhập tên máy!');
        
        const btn = $('button[onclick="registerNode()"]');
        btn.prop('disabled', true).text('Đang kết nối...');

        $.post('file_sync_agent.php', { register: 1, node_name: name, master_key: key }, function(res) {
            // Parse HTML response to find the message div
            const parser = new DOMParser();
            const doc = parser.parseFromString(res, 'text/html');
            const errorMsg = doc.querySelector("div[style*='color:red']");
            const successMsg = doc.querySelector("div[style*='color:green']");

            if (successMsg) {
                showToast('success', 'Thành công', 'Kết nối thành công! Đang tải lại...');
                setTimeout(() => location.reload(), 1500);
            } else if (errorMsg) {
                showToast('error', 'Lỗi đăng ký', errorMsg.innerText);
                btn.prop('disabled', false).text('Kết nối');
            } else {
                showToast('error', 'Lỗi mạng', 'Không nhận được phản hồi từ server.');
                btn.prop('disabled', false).text('Kết nối');
            }
        }).fail(function() {
            showToast('error', 'Lỗi hệ thống', 'Không thể gọi script agent.');
            btn.prop('disabled', false).text('Kết nối');
        });
    }

    function toggleKey() {
        const input = document.getElementById('masterKey');
        const icon = document.getElementById('toggleKeyIcon');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function saveManualToken() {
        const name = $('#nodeName').val();
        const token = $('#manualToken').val();
        
        if (!name || !token) return showToast('error', 'Lỗi', 'Vui lòng nhập Tên máy và Token!');
        
        $.post('file_sync_agent.php', { save_manual: 1, node_name: name, auth_token: token }, function(res) {
             alert('Cấu hình đã được lưu! Trang sẽ tải lại.');
             location.reload();
        });
    }

    async function startSync(type) {
        const btn = type === 'db' ? $('#btnSyncDB') : $('#btnSyncFile');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang chạy...');
        
        // Reset UI
        $('#progressBarContainer').show();
        $('#progressBar').css('width', '0%');
        $('#progressText').show().text('Đang kiểm tra...');
        
        try {
            if (type === 'db') {
                log('--> Bắt đầu đồng bộ Database...', 'info');
                $('#progressBar').css('width', '50%');
                const response = await fetch('sync_from_cloud.php');
                const text = await response.text();
                
                $('#progressBar').css('width', '100%');
                if (text.includes('Done')) log('Đã hoàn tất đồng bộ Database!', 'success');
                else log('Có lỗi xảy ra hoặc không có dữ liệu mới.', 'warning');
                
            } else {
                // FILE SYNC LOGIC WITH BATCHING
                log('--> Kết nối Server lấy danh sách file...', 'info');
                
                // 1. Check Count
                const countRes = await $.getJSON('file_sync_agent.php?check_count=1');
                if (!countRes || countRes.status !== 'success') {
                    throw new Error(countRes.message || 'Lỗi kiểm tra số lượng file.');
                }
                
                const total = countRes.total;
                if (total === 0) {
                    $('#progressBar').css('width', '100%');
                    $('#progressText').text('Hoàn tất (0/0)');
                    log('Không có file mới cần tải.', 'success');
                    btn.prop('disabled', false).html('<i class="fas fa-download"></i> Tải File mới');
                    return;
                }
                
                log(`Tìm thấy ${total} file cần tải. Bắt đầu batch...`, 'info');
                let processed = 0;
                
                // 2. Loop Batches
                while (processed < total) {
                    const batchRes = await $.getJSON('file_sync_agent.php?run_batch=1&limit=5');
                    
                    if (batchRes.status !== 'success') {
                        log('Lỗi Batch: ' + batchRes.message, 'error');
                        break;
                    }
                    
                    // Log successes
                    if (batchRes.logs) {
                        batchRes.logs.forEach(l => log(l, 'info'));
                    }
                    // Log errors
                    if (batchRes.errors) {
                        batchRes.errors.forEach(e => log(e, 'error'));
                    }
                    
                    if (batchRes.synced === 0 && batchRes.errors.length === 0) {
                        break; // Stop if nothing happened (safety)
                    }
                    
                    processed += batchRes.synced;
                    const percent = Math.round((processed / total) * 100);
                    $('#progressBar').css('width', percent + '%');
                    $('#progressText').text(`Đã tải ${processed}/${total} file (${percent}%)`);
                }
                
                log('Hoàn tất quá trình tải file.', 'success');
            }
        } catch (e) {
            log('Lỗi kết nối: ' + e.message, 'error');
            $('#progressText').text('Lỗi!');
        }
        
        btn.prop('disabled', false).html(type === 'db' ? '<i class="fas fa-play"></i> Chạy Đồng bộ' : '<i class="fas fa-download"></i> Tải File mới');
    }
</script>

</body>
</html>
