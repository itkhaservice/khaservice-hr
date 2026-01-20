<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$employee = null;
$docs_status = [];

// Handle Search
if (isset($_GET['check_fullname']) && isset($_GET['check_identity'])) {
    $fullname = clean_input($_GET['check_fullname']);
    $identity = clean_input($_GET['check_identity']);
    
    // Using LIKE for fullname to be more flexible (case insensitive usually in MySQL)
    $employee = db_fetch_row("SELECT * FROM employees WHERE fullname LIKE ? AND identity_card = ?", [$fullname, $identity]);
    
    if (!$employee) {
        $error = "Không tìm thấy thông tin nhân viên! Vui lòng kiểm tra lại Họ tên và CCCD.";
    } else {
        // Fetch Docs
        $all_docs = db_fetch_all("SELECT * FROM documents WHERE employee_id = ?", [$employee['id']]);
        $docs_map = [];
        foreach ($all_docs as $d) {
            $docs_map[$d['doc_type']][] = $d;
        }
        
        // Fetch Settings
        $doc_settings = db_fetch_all("SELECT * FROM document_settings ORDER BY id ASC");
    }
}

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_doc']) && $employee) {
    $type_code = clean_input($_POST['doc_type']);
    $expiry = clean_input($_POST['expiry_date']);
    
    // Create Folder Name
    $folder_name = get_emp_folder_name($employee['fullname'], $employee['identity_card']);
    $target_dir = "upload/documents/" . $folder_name . "/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $filename = $type_code . "_" . time() . "." . $ext;
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            // Check multiple setting
            $setting = db_fetch_row("SELECT is_multiple FROM document_settings WHERE code = ?", [$type_code]);
            $is_multiple = $setting ? $setting['is_multiple'] : 0;
            
            // Insert as PENDING
            db_query("INSERT INTO documents (employee_id, doc_type, file_path, is_submitted, expiry_date, approval_status) VALUES (?, ?, ?, 1, ?, 'pending')", 
                     [$employee['id'], $type_code, $target_file, $expiry]);
            
            $success = "Tải lên thành công! Hồ sơ đang chờ nhân sự duyệt.";
            // Refresh with search params to keep view
            $redirect_url = "index.php?check_fullname=" . urlencode($employee['fullname']) . "&check_identity=" . urlencode($employee['identity_card']);
            echo "<script>setTimeout(() => window.location.href = '$redirect_url', 2000);</script>";
        } else {
            $error = "Lỗi khi lưu file.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng thông tin Nhân sự - Khaservice</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #334155; margin: 0; }
        .container { max-width: 800px; margin: 0 auto; padding: 15px; }
        .header { text-align: center; padding: 30px 0; }
        .header h1 { color: #24a25c; margin: 0; font-size: 1.8rem; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn { padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #24a25c; color: #fff; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; }
        
        .doc-item { border-bottom: 1px solid #e2e8f0; padding: 15px 0; display: flex; justify-content: space-between; align-items: center; }
        .doc-item:last-child { border-bottom: none; }
        .badge { padding: 4px 8px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef9c3; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>KHASERVICE HR PORTAL</h1>
        <p>Tra cứu và cập nhật hồ sơ nhân sự trực tuyến</p>
    </div>

    <?php if ($error): ?> <div class="alert alert-error"><?php echo $error; ?></div> <?php endif; ?>
    <?php if ($success): ?> <div class="alert alert-success"><?php echo $success; ?></div> <?php endif; ?>

    <div class="card">
        <h3 style="margin-top: 0;">Tra cứu hồ sơ</h3>
        <form method="GET" action="index.php">
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Họ và tên</label>
                    <input type="text" name="check_fullname" class="form-control" placeholder="Nhập họ và tên đầy đủ" value="<?php echo isset($_GET['check_fullname']) ? htmlspecialchars($_GET['check_fullname']) : ''; ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Số CCCD</label>
                    <input type="text" name="check_identity" class="form-control" placeholder="Nhập số CCCD" value="<?php echo isset($_GET['check_identity']) ? htmlspecialchars($_GET['check_identity']) : ''; ?>" required>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Tra cứu ngay</button>
                <?php if ($employee): ?>
                    <a href="index.php" class="btn" style="background: #e2e8f0; color: #334155;">Xóa / Tìm mới</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($employee): ?>
    <div class="card" style="border-top: 4px solid #24a25c;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
            <div style="width: 60px; height: 60px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b; font-size: 1.5rem;">
                <?php echo substr($employee['fullname'], 0, 1); ?>
            </div>
            <div>
                <h2 style="margin: 0;"><?php echo $employee['fullname']; ?></h2>
                <div style="color: #64748b;"><?php echo $employee['position']; ?> - <?php echo $employee['code']; ?></div>
            </div>
        </div>

        <h3 style="border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; font-size: 1.1rem; margin-top: 0;">Trạng thái hồ sơ chi tiết</h3>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead style="background: #f8fafc; text-align: left;">
                    <tr>
                        <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Loại hồ sơ</th>
                        <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Trạng thái / Ghi chú</th>
                        <th style="padding: 10px; border-bottom: 2px solid #e2e8f0; text-align: center; width: 80px;">Nộp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doc_settings as $s): 
                        $my_docs = $docs_map[$s['code']] ?? [];
                        
                        // Determine Overall Status
                        $status_html = '';
                        if (!empty($my_docs)) {
                            $count_approved = 0;
                            $count_pending = 0;
                            $count_rejected = 0;
                            $latest_date = '';
                            foreach($my_docs as $md) {
                                if ($md['approval_status'] == 'approved') $count_approved++;
                                if ($md['approval_status'] == 'pending') $count_pending++;
                                if ($md['approval_status'] == 'rejected') $count_rejected++;
                                $latest_date = date('d/m/y', strtotime($md['created_at']));
                            }
                            
                            if ($count_approved > 0) {
                                $status_html = '<span class="badge badge-success">Đã duyệt (' . $count_approved . ')</span>';
                            } elseif ($count_pending > 0) {
                                $status_html = '<span class="badge badge-info">Đang chờ duyệt (' . $count_pending . ')</span>';
                            } elseif ($count_rejected > 0) {
                                $status_html = '<span class="badge badge-danger">Bị từ chối</span>';
                            }
                            $status_html .= ' <small style="color:#94a3b8; display:block; margin-top:2px;">Cập nhật: ' . $latest_date . '</small>';
                        } else {
                            if ($s['is_required']) {
                                $status_html = '<span class="badge badge-danger">Cần bổ sung</span>';
                            } else {
                                $status_html = '<span style="color:#94a3b8; font-style:italic;">Không bắt buộc</span>';
                            }
                        }
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px; vertical-align: top;">
                            <div style="font-weight: 600;"><?php echo $s['name']; ?></div>
                            <?php if($s['is_required']): ?><small style="color: #ef4444;">* Bắt buộc</small><?php endif; ?>
                        </td>
                        <td style="padding: 10px; vertical-align: top;">
                            <?php echo $status_html; ?>
                        </td>
                        <td style="padding: 10px; vertical-align: top; text-align: center;">
                            <button class="btn" style="padding: 5px 10px; font-size: 0.75rem; background: #fff; border: 1px solid #24a25c; color: #24a25c;" onclick="openUpload('<?php echo $s['code']; ?>', '<?php echo $s['name']; ?>')">
                                <i class="fas fa-upload"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <div id="uploadModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div class="card" style="width: 400px; margin: 0;">
            <h3 id="modalTitle">Nộp hồ sơ</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="doc_type" id="modalDocType">
                <div class="form-group">
                    <label>Chọn tệp tin (Hình ảnh/PDF)</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Ngày hết hạn (nếu có)</label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="upload_doc" class="btn btn-primary" style="flex:1;">Gửi hồ sơ</button>
                    <button type="button" class="btn" style="background:#f1f5f9;" onclick="document.getElementById('uploadModal').style.display='none'">Đóng</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUpload(code, name) {
            document.getElementById('modalDocType').value = code;
            document.getElementById('modalTitle').innerText = 'Nộp: ' + name;
            document.getElementById('uploadModal').style.display = 'flex';
            
            // Auto-remove required for DXV or others
            const expiryInput = document.querySelector('input[name="expiry_date"]');
            if (code === 'DXV' || code === 'SYLL' || code === 'CK') {
                expiryInput.removeAttribute('required');
                expiryInput.parentElement.style.display = 'none'; // Hide if not needed
            } else {
                // expiryInput.setAttribute('required', 'true'); // Only if really required
                expiryInput.parentElement.style.display = 'block';
            }
        }
    </script>
    <?php endif; ?>
</div>

</body>
</html>
