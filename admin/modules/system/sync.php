<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// Bảo mật: Chỉ admin hoặc người có quyền mới được vào
require_permission('manage_system');

include '../../../includes/header.php';
include '../../../includes/sidebar.php';

$isAdmin = is_admin();
$MASTER_KEY = "KHA_SERVICE_FILE_SYNC_MASTER_KEY_2026";
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title"><i class="fas fa-sync-alt"></i> Hệ thống Đồng bộ Hybrid</h1>
            <div class="header-actions">
                <button class="btn btn-secondary btn-sm" onclick="showHelp()"><i class="fas fa-question-circle"></i> Hướng dẫn</button>
            </div>
        </div>

        <div class="dashboard-grid" style="grid-template-columns: 1fr 2fr;">
            <!-- Cấu hình -->
            <div class="card">
                <h3><i class="fas fa-cog"></i> Cấu hình Đồng bộ</h3>
                <p class="text-muted" style="font-size: 0.85rem;">Thiết lập nơi lưu trữ trên máy tính này.</p>
                
                <div class="form-group">
                    <label>1. Tên máy tính này</label>
                    <input type="text" id="nodeName" class="form-control" placeholder="VD: MAY_TINH_VĂN_PHÒNG" value="<?php echo $_SESSION['user_fullname'] ?? 'PC_ADMIN'; ?>">
                </div>

                <div class="form-group">
                    <label>2. Mã khóa Master</label>
                    <div style="position: relative;">
                        <input type="password" id="masterKey" class="form-control" 
                               value="<?php echo $isAdmin ? $MASTER_KEY : ''; ?>" 
                               autocomplete="new-password" 
                               placeholder="Nhập mã khóa để xác thực">
                        <i class="fas fa-eye" id="toggleKeyIcon" onclick="toggleKey()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #64748b;"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>3. Thư mục lưu trữ trên PC</label>
                    <div id="folderStatus" style="padding: 10px; border: 1px dashed #cbd5e1; border-radius: 6px; margin-bottom: 10px; background: #f8fafc; font-size: 0.85rem; color: #64748b;">
                        Chưa chọn thư mục...
                    </div>
                    <button class="btn btn-primary btn-sm w-100" onclick="pickDirectory()">
                        <i class="fas fa-folder-open"></i> Chọn thư mục lưu trên máy này
                    </button>
                </div>

                <hr>

                <div class="sync-actions">
                    <button class="btn btn-success w-100 mb-2" id="btnSyncFile" disabled onclick="startFileSync()">
                        <i class="fas fa-download"></i> Bắt đầu Tải File về máy
                    </button>
                    <button class="btn btn-info w-100" onclick="exportDB()">
                        <i class="fas fa-database"></i> Sao lưu Dữ liệu Hệ thống (.sql)
                    </button>
                </div>
            </div>

            <!-- Tiến trình -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3><i class="fas fa-tasks"></i> Nhật ký Xử lý</h3>
                    <button class="btn btn-secondary btn-xs" onclick="document.getElementById('logBox').innerHTML=''">Xóa trắng</button>
                </div>

                <div class="progress-section" style="margin-bottom: 20px;">
                    <div class="progress-container" style="width: 100%; background: #e2e8f0; height: 12px; border-radius: 10px; overflow: hidden; display: none;" id="progCont">
                        <div id="progBar" style="width: 0%; height: 100%; background: #24a25c; transition: 0.3s;"></div>
                    </div>
                    <div id="progText" style="text-align: center; font-size: 0.85rem; margin-top: 5px; font-weight: 600; color: #24a25c;"></div>
                </div>

                <div id="logBox" style="background: #1e293b; color: #4ade80; padding: 15px; border-radius: 8px; height: 400px; overflow-y: auto; font-family: 'Consolas', monospace; font-size: 0.85rem; line-height: 1.6;">
                    Hệ thống sẵn sàng... vui lòng Chọn thư mục để bắt đầu.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hướng dẫn -->
<div id="helpModal" class="modal-overlay">
    <div class="modal-box" style="width: 600px; text-align: left;">
        <div class="modal-header">
            <h3 class="modal-title">Hướng dẫn Đồng bộ Trực tiếp</h3>
            <button class="btn-close" onclick="closeModal('helpModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px 0;">
            <p>Tính năng này giúp bạn tải toàn bộ hồ sơ từ Hosting về ổ cứng máy tính cá nhân mà không cần cài đặt phần mềm.</p>
            <div style="margin-bottom: 15px;">
                <strong>Bước 1:</strong> Nhập tên máy để hệ thống ghi nhận máy nào đã giữ file.<br>
                <strong>Bước 2:</strong> Nhấn "Chọn thư mục". Bạn nên chọn một thư mục trống (VD: <code>D:\KHASERVICE_BACKUP</code>).<br>
                <strong>Bước 3:</strong> Trình duyệt sẽ hỏi quyền "Xem và Sửa tệp", hãy nhấn <strong>Allow/Cho phép</strong>.<br>
                <strong>Bước 4:</strong> Nhấn "Bắt đầu tải". Giữ trình duyệt mở cho đến khi hoàn tất.
            </div>
            <p style="color: #dc2626; font-size: 0.85rem; background: #fee2e2; padding: 10px; border-radius: 4px;">
                <i class="fas fa-exclamation-triangle"></i> Lưu ý: Chỉ hoạt động trên trình duyệt Chrome, Edge, Opera. Không hỗ trợ trên điện thoại hoặc trình duyệt Safari (iPhone).
            </p>
        </div>
        <div class="modal-actions">
            <button class="btn btn-primary btn-sm" onclick="closeModal('helpModal')">Đã hiểu</button>
        </div>
    </div>
</div>

<script>
let directoryHandle = null;
const API_URL = "../../../api/sync_files_server.php";

function log(msg, type = 'info') {
    const box = document.getElementById('logBox');
    const time = new Date().toLocaleTimeString();
    const color = type === 'error' ? '#f87171' : (type === 'success' ? '#4ade80' : '#cbd5e1');
    box.innerHTML += `<div style="color:${color}">[${time}] ${msg}</div>`;
    box.scrollTop = box.scrollHeight;
}

function toggleKey() {
    const input = document.getElementById('masterKey');
    const icon = document.getElementById('toggleKeyIcon');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function showHelp() { document.getElementById('helpModal').style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// 1. Chọn thư mục trên PC
async function pickDirectory() {
    try {
        directoryHandle = await window.showDirectoryPicker({
            mode: 'readwrite'
        });
        document.getElementById('folderStatus').innerHTML = `<i class="fas fa-check-circle text-success"></i> Đã kết nối thư mục: <strong>${directoryHandle.name}</strong>`;
        document.getElementById('btnSyncFile').disabled = false;
        log(`Đã kết nối với thư mục "${directoryHandle.name}" trên máy tính.`, 'success');
    } catch (err) {
        log('Bạn đã hủy chọn thư mục hoặc trình duyệt không hỗ trợ.', 'error');
    }
}

// 2. Bắt đầu tải file
async function startFileSync() {
    const name = document.getElementById('nodeName').value;
    const key = document.getElementById('masterKey').value;
    
    if (!name || !key) return alert('Vui lòng nhập Tên máy và Mã khóa!');
    
    const btn = document.getElementById('btnSyncFile');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    
    document.getElementById('progCont').style.display = 'block';
    
    try {
        log('Đang kiểm tra danh sách file mới từ Server...');
        
        // A. Đăng ký/Lấy Token (Dùng AJAX nội bộ trên Hosting luôn)
        const regForm = new FormData();
        regForm.append('action', 'register');
        regForm.append('name', name);
        regForm.append('master_key', key);
        
        const regRes = await fetch(API_URL, { 
            method: 'POST', 
            body: regForm,
            credentials: 'include' // Quan trọng: Gửi kèm Cookie
        }).then(r => r.json());
        
        if (regRes.status !== 'success') throw new Error(regRes.message);
        const token = regRes.auth_token;
        log('Xác thực thành công. Bắt đầu quét file...', 'success');

        // B. Lấy số lượng
        const countRes = await fetch(`${API_URL}?action=count_pending`, {
            headers: { 'X-Node-Token': token },
            credentials: 'include'
        }).then(r => r.json());
        
        const total = countRes.total;
        if (total === 0) {
            log('Tuyệt vời! Toàn bộ file đã được sao lưu trước đó.', 'success');
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-download"></i> Tải File về máy';
            return;
        }

        log(`Tìm thấy ${total} file mới. Bắt đầu tải...`, 'info');
        let processed = 0;

        // C. Tải từng file (Dùng vòng lặp Batch để mượt)
        while (processed < total) {
            const listRes = await fetch(`${API_URL}?action=get_pending&limit=5`, {
                headers: { 'X-Node-Token': token },
                credentials: 'include'
            }).then(r => r.json());

            if (!listRes.files || listRes.files.length === 0) break;

            for (const file of listRes.files) {
                try {
                    log(`Đang tải: ${file.file_path.split('/').pop()}...`);
                    
                    // 1. Fetch file content
                    const fileBlob = await fetch(`../../../${file.file_path}`, { credentials: 'include' }).then(r => r.blob());
                    
                    // 2. Ghi vào ổ cứng Local
                    const fileHandle = await directoryHandle.getFileHandle(file.file_path.split('/').pop(), { create: true });
                    const writable = await fileHandle.createWritable();
                    await writable.write(fileBlob);
                    await writable.close();

                    // 3. Xác nhận với Hosting
                    const confirmForm = new FormData();
                    confirmForm.append('action', 'confirm');
                    confirmForm.append('file_id', file.id);
                    confirmForm.append('file_hash', 'js_verified'); // Đơn giản hóa hash trên browser
                    confirmForm.append('auth_token', token);

                    await fetch(API_URL, { method: 'POST', body: confirmForm, credentials: 'include' });

                    processed++;
                    const pct = Math.round((processed / total) * 100);
                    document.getElementById('progBar').style.width = pct + '%';
                    document.getElementById('progText').innerText = `Đang tải: ${processed}/${total} file (${pct}%)`;
                } catch (e) {
                    log(`Lỗi khi lưu file ID ${file.id}: ${e.message}`, 'error');
                }
            }
        }

        log('CHÚC MỪNG! Toàn bộ file đã được lưu an toàn vào máy tính của bạn.', 'success');
        showToast('success', 'Hoàn tất', 'Đã đồng bộ xong dữ liệu hồ sơ.');

    } catch (err) {
        log('LỖI: ' + err.message, 'error');
        alert('Lỗi: ' + err.message);
    }
    
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-download"></i> Tải File về máy';
}

function exportDB() {
    log('Đang chuẩn bị bản sao lưu Database (.sql)...');
    window.location.href = 'backup_db.php';
}
</script>

<style>
.btn-xs { padding: 2px 8px; font-size: 0.75rem; }
.mb-2 { margin-bottom: 10px; }
.w-100 { width: 100%; }
</style>
</div>
<?php include '../../../includes/footer.php'; ?>
