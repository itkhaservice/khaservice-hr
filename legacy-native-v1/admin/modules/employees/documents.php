<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$employee = db_fetch_row("SELECT * FROM employees WHERE id = ?", [$id]);

if (!$employee) {
    redirect('index.php');
}

// Security: Check Permissions
$allowed_projs = get_allowed_projects();
if ($allowed_projs !== 'ALL') {
    $emp_proj_id = $employee['current_project_id'];
    if (!$emp_proj_id || !in_array($emp_proj_id, $allowed_projs)) {
        echo "<div class='main-content'><div class='content-wrapper'>
                <div class='alert alert-danger'>
                    <h3><i class='fas fa-lock'></i> Truy cập bị từ chối</h3>
                    <p>Nhân viên này không thuộc dự án bạn quản lý.</p>
                    <a href='index.php' class='btn btn-secondary'>Quay lại</a>
                </div>
              </div></div>";
        include '../../../includes/footer.php';
        exit;
    }
}

// Fetch Document Types
$doc_types_raw = db_fetch_all("SELECT * FROM document_settings ORDER BY id ASC");
$doc_types = [];
foreach ($doc_types_raw as $dt) {
    $doc_types[$dt['code']] = $dt;
}

// Handle Delete (Modified for specific ID)
if (isset($_GET['delete_id'])) {
    $doc_id_to_delete = (int)$_GET['delete_id'];
    $existing = db_fetch_row("SELECT * FROM documents WHERE id = ? AND employee_id = ?", [$doc_id_to_delete, $id]);
    
    if ($existing) {
        if ($existing['file_path'] && file_exists("../../../" . $existing['file_path'])) {
            unlink("../../../" . $existing['file_path']);
        }
        db_query("DELETE FROM documents WHERE id = ?", [$doc_id_to_delete]);
        set_toast('success', 'Đã xóa tài liệu!');
    } else {
        set_toast('error', 'Không tìm thấy tài liệu!');
    }
    redirect("documents.php?id=$id");
}

// Handle Approval
if (isset($_GET['approve_id']) && isset($_GET['status'])) {
    $doc_id = (int)$_GET['approve_id'];
    $status = clean_input($_GET['status']); // approved, rejected
    db_query("UPDATE documents SET approval_status = ? WHERE id = ?", [$status, $doc_id]);
    set_toast('success', 'Đã cập nhật trạng thái hồ sơ!');
    redirect("documents.php?id=$id");
}

// Handle Upload (Modified for Multiple Files & Folder Structure)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_doc'])) {
    $type_code = clean_input($_POST['doc_type']);
    $expiry = clean_input($_POST['expiry_date']);
    
    // Check settings
    $setting = db_fetch_row("SELECT * FROM document_settings WHERE code = ?", [$type_code]);
    $allow_multiple = $setting ? $setting['is_multiple'] : 0;

    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        
        // Use new folder structure
        $folder_name = get_emp_folder_name($employee['fullname'], $employee['identity_card']);
        $target_dir = "../../../upload/documents/" . $folder_name . "/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $filename = $type_code . "_" . time() . "_" . rand(100,999) . "." . $ext;
        $target = $target_dir . $filename;
        $db_path = "upload/documents/" . $folder_name . "/" . $filename;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            
            if ($allow_multiple) {
                // Just Insert (Auto-approve for Admin upload)
                db_query("INSERT INTO documents (employee_id, doc_type, file_path, is_submitted, expiry_date, approval_status) VALUES (?, ?, ?, 1, ?, 'approved')", 
                         [$id, $type_code, $db_path, $expiry]);
            } else {
                // Check existing to Replace
                $existing = db_fetch_row("SELECT id, file_path FROM documents WHERE employee_id = ? AND doc_type = ?", [$id, $type_code]);
                if ($existing) {
                    if ($existing['file_path'] && file_exists("../../../" . $existing['file_path'])) {
                        unlink("../../../" . $existing['file_path']);
                    }
                    db_query("UPDATE documents SET file_path = ?, is_submitted = 1, expiry_date = ?, created_at = NOW(), approval_status = 'approved' WHERE id = ?", 
                             [$db_path, $expiry, $existing['id']]);
                } else {
                    db_query("INSERT INTO documents (employee_id, doc_type, file_path, is_submitted, expiry_date, approval_status) VALUES (?, ?, ?, 1, ?, 'approved')", 
                             [$id, $type_code, $db_path, $expiry]);
                }
            }
            
            set_toast('success', 'Tải lên thành công!');
            redirect("documents.php?id=$id");
        }
    }
}

// Get submitted documents
$docs = db_fetch_all("SELECT * FROM documents WHERE employee_id = ?", [$id]);
// Group by type
$submitted_docs = [];
foreach ($docs as $d) {
    $submitted_docs[$d['doc_type']][] = $d;
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

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
                        <?php foreach ($doc_types as $code => $setting): ?>
                            <?php 
                                $file_list = $submitted_docs[$code] ?? []; 
                                $has_file = !empty($file_list);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo $setting['name']; ?></strong>
                                    <?php if($setting['is_required']): ?><span style="color:red">*</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($has_file): ?>
                                        <span class="badge badge-success">✅ Đã nộp (<?php echo count($file_list); ?>)</span>
                                    <?php else: ?>
                                        <?php if($setting['is_required']): ?>
                                            <span class="badge badge-warning">⚠️ Thiếu</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Không bắt buộc</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td colspan="2" style="padding:0;">
                                    <?php if ($has_file): ?>
                                        <table style="width:100%; margin:0; border:none;">
                                        <?php foreach($file_list as $f): ?>
                                            <tr>
                                                <td style="border:none; border-bottom:1px solid var(--border-color);">
                                                    <?php echo ($f['expiry_date']) ? date('d/m/Y', strtotime($f['expiry_date'])) : '-'; ?>
                                                    <?php if ($f['expiry_date'] && strtotime($f['expiry_date']) < time()) echo ' <span class="text-danger">(Hết hạn)</span>'; ?>
                                                </td>
                                                <td style="border:none; border-bottom:1px solid var(--border-color);">
                                                    <a href="/khaservice-hr/<?php echo $f['file_path']; ?>" target="_blank" class="text-info"><i class="fas fa-download"></i> Tải về</a>
                                                    <?php 
                                                        if ($f['approval_status'] == 'pending') echo ' <span class="badge badge-warning">Chờ duyệt</span>';
                                                        elseif ($f['approval_status'] == 'rejected') echo ' <span class="badge badge-danger">Từ chối</span>';
                                                        else echo ' <i class="fas fa-check-circle text-success" title="Đã duyệt"></i>';
                                                    ?>
                                                </td>
                                                <td style="width:100px; border:none; border-bottom:1px solid var(--border-color); text-align:right;">
                                                    <?php if ($f['approval_status'] == 'pending'): ?>
                                                        <a href="?id=<?php echo $id; ?>&approve_id=<?php echo $f['id']; ?>&status=approved" class="btn btn-success btn-sm" style="padding:2px 5px;" title="Duyệt"><i class="fas fa-check"></i></a>
                                                        <a href="?id=<?php echo $id; ?>&approve_id=<?php echo $f['id']; ?>&status=rejected" class="btn btn-danger btn-sm" style="padding:2px 5px;" title="Từ chối"><i class="fas fa-times"></i></a>
                                                    <?php else: ?>
                                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $f['id']; ?>)" class="text-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </table>
                                    <?php else: ?>
                                        <div style="padding:10px; color:#777; font-style:italic; text-align:center;">Chưa có tệp tin</div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center; vertical-align:middle;">
                                    <button class="btn btn-primary btn-sm" onclick="openUploadModal('<?php echo $code; ?>', '<?php echo $setting['name']; ?>')">
                                        <i class="fas fa-upload"></i> <?php echo ($has_file && !$setting['is_multiple']) ? 'Cập nhật' : 'Thêm file'; ?>
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

function confirmDelete(id) {
    Modal.confirm('Bạn có chắc muốn xóa file này không?', () => {
        window.location.href = `documents.php?id=<?php echo $id; ?>&delete_id=${id}`;
    });
}
</script>

<?php include '../../../includes/footer.php'; ?>
