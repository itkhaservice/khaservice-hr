<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$employee = db_fetch_row("SELECT * FROM employees WHERE id = ?", [$id]);

if (!$employee) {
    redirect('index.php');
}

// Mandatory documents list
$doc_types = [
    'CCCD' => 'Căn cước công dân',
    'HK' => 'Hộ khẩu / Xác nhận cư trú',
    'SYLL' => 'Sơ yếu lý lịch',
    'BC' => 'Bằng cấp / Chứng chỉ',
    'GKSK' => 'Giấy khám sức khỏe',
    'HDLD' => 'Hợp đồng lao động',
    'BH' => 'Hồ sơ bảo hiểm'
];

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_doc'])) {
    $type = clean_input($_POST['doc_type']);
    $expiry = clean_input($_POST['expiry_date']);
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $filename = $type . "_" . $id . "_" . time() . "." . $ext;
        $target = "../../../upload/documents/" . $filename;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            // DB relative path: upload/documents/filename
            $db_path = "upload/documents/" . $filename;
            
            // Check if exists
            $existing = db_fetch_row("SELECT id FROM documents WHERE employee_id = ? AND doc_type = ?", [$id, $type]);
            
            if ($existing) {
                db_query("UPDATE documents SET file_path = ?, is_submitted = 1, expiry_date = ?, created_at = NOW() WHERE id = ?", 
                         [$db_path, $expiry, $existing['id']]);
            } else {
                db_query("INSERT INTO documents (employee_id, doc_type, file_path, is_submitted, expiry_date) VALUES (?, ?, ?, 1, ?)", 
                         [$id, $type, $db_path, $expiry]);
            }
            echo "<script>alert('Tải lên thành công!');</script>";
        }
    }
}

// Get submitted documents
$docs = db_fetch_all("SELECT * FROM documents WHERE employee_id = ?", [$id]);
$submitted_docs = [];
foreach ($docs as $d) {
    $submitted_docs[$d['doc_type']] = $d;
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <header class="main-header">
        <div class="toggle-sidebar" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="user-info">
            <span>Admin</span>
            <div class="user-avatar">A</div>
        </div>
    </header>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Hồ sơ nhân viên: <?php echo $employee['fullname']; ?> (<?php echo $employee['code']; ?>)</h1>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>

        <div class="card">
            <h3>Danh mục hồ sơ bắt buộc</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Loại hồ sơ</th>
                            <th>Trạng thái</th>
                            <th>Ngày hết hạn</th>
                            <th>Tệp tin</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doc_types as $key => $label): ?>
                            <?php $doc = $submitted_docs[$key] ?? null; ?>
                            <tr>
                                <td><strong><?php echo $label; ?></strong></td>
                                <td>
                                    <?php if ($doc && $doc['is_submitted']): ?>
                                        <span class="badge badge-success">✅ Đã nộp</span>
                                        <?php 
                                            if ($doc['expiry_date'] && strtotime($doc['expiry_date']) < time()) {
                                                echo ' <span class="badge badge-danger">⛔ Hết hạn</span>';
                                            }
                                        ?>
                                    <?php else: ?>
                                        <span class="badge badge-warning">⚠️ Thiếu</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo ($doc && $doc['expiry_date']) ? date('d/m/Y', strtotime($doc['expiry_date'])) : '-'; ?></td>
                                <td>
                                    <?php if ($doc && $doc['file_path']): ?>
                                        <a href="/khaservice-hr/<?php echo $doc['file_path']; ?>" target="_blank" class="text-info"><i class="fas fa-download"></i> Xem file</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="openUploadModal('<?php echo $key; ?>', '<?php echo $label; ?>')">
                                        <i class="fas fa-upload"></i> <?php echo $doc ? 'Cập nhật' : 'Tải lên'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Simple Upload Modal (Simulation with JS) -->
    <div id="uploadModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
        <div class="card" style="width:400px;">
            <h3 id="modalTitle">Tải lên hồ sơ</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="doc_type" id="modalDocType">
                <div class="form-group">
                    <label>Chọn tệp tin</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Ngày hết hạn (nếu có)</label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>
                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" name="upload_doc" class="btn btn-primary">Lưu hồ sơ</button>
                    <button type="button" class="btn btn-secondary" onclick="$('#uploadModal').hide()">Đóng</button>
                </div>
            </form>
        </div>
    </div>

<script>
function openUploadModal(type, label) {
    $('#modalDocType').val(type);
    $('#modalTitle').text('Tải lên: ' + label);
    $('#uploadModal').css('display', 'flex');
}
</script>

<style>
.btn-sm { padding: 4px 8px; font-size: 12px; }
.badge-success { background-color: #28a745; }
.badge-warning { background-color: #ffc107; color: #333; }
.badge-danger { background-color: #dc3545; }
.text-info { color: #17a2b8; }
</style>

<?php include '../../../includes/footer.php'; ?>
