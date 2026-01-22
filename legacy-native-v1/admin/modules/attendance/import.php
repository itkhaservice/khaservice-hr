<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

// Handle Upload
$message = '';
$import_result = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if ($ext != 'xlsx') {
        $message = '<div class="alert alert-danger">Chỉ chấp nhận file Excel (.xlsx)</div>';
    } else {
        $upload_path = '../../../upload/temp/' . time() . '_' . $file['name'];
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Call Python Script
            $python_script = __DIR__ . '/parse_excel.py';
            $cmd = "python \"$python_script\" \"$upload_path\"";
            
            // Execute and get output
            // Use shell_exec
            // Need to set encoding to UTF-8 for python output handling if needed
            putenv("PYTHONIOENCODING=utf-8");
            $output = shell_exec($cmd);
            
            // Delete temp file
            // unlink($upload_path); // Keep for debug if needed, or delete later
            
            $data = json_decode($output, true);
            
            if (isset($data['error'])) {
                $message = '<div class="alert alert-danger">Lỗi xử lý file: ' . $data['error'] . '</div>';
            } elseif (is_array($data)) {
                // Process Data
                $count_success = 0;
                $count_fail = 0;
                $log_details = [];
                
                // Get month/year from input or default to current (User should select month)
                $month = (int)$_POST['month'];
                $year = (int)$_POST['year'];
                
                foreach ($data as $emp) {
                    $name = trim($emp['name']);
                    // Find employee by name (Case insensitive)
                    $db_emp = db_fetch_row("SELECT id FROM employees WHERE LOWER(fullname) = LOWER(?)", [$name]);
                    
                    if ($db_emp) {
                        $emp_id = $db_emp['id'];
                        $attendance_list = $emp['attendance'];
                        
                        foreach ($attendance_list as $day => $att) {
                            $date = "$year-$month-$day";
                            $symbol = $att['symbol'];
                            $ot = $att['ot'];
                            
                            // Check if record exists
                            $existing = db_fetch_row("SELECT id FROM attendance WHERE employee_id = ? AND date = ?", [$emp_id, $date]);
                            
                            if ($existing) {
                                // Update
                                db_query("UPDATE attendance SET timekeeper_symbol = ?, overtime_hours = ?, is_manual_import = 1 WHERE id = ?", 
                                         [$symbol, $ot, $existing['id']]);
                            } else {
                                // Insert
                                db_query("INSERT INTO attendance (employee_id, date, timekeeper_symbol, overtime_hours, is_manual_import) VALUES (?, ?, ?, ?, 1)", 
                                         [$emp_id, $date, $symbol, $ot]);
                            }
                        }
                        $count_success++;
                        $log_details[] = "OK: $name";
                    } else {
                        $count_fail++;
                        $log_details[] = "FAIL: Không tìm thấy nhân viên '$name'";
                    }
                }
                
                $message = "<div class='alert alert-success'>Đã import thành công: $count_success nhân viên. Thất bại: $count_fail.</div>";
                if($count_fail > 0) {
                     $message .= "<div class='alert alert-warning' style='max-height: 200px; overflow-y: auto;'>" . implode('<br>', $log_details) . "</div>";
                }
                
            } else {
                $message = '<div class="alert alert-danger">Không đọc được dữ liệu JSON từ Python script. Output: ' . htmlspecialchars(substr($output, 0, 500)) . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Lỗi upload file.</div>';
        }
    }
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Import Bảng Chấm Công</h1>
        </div>

        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <?php echo $message; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Chọn tháng chấm công</label>
                    <div style="display: flex; gap: 10px;">
                        <select name="month" class="form-control">
                            <?php for($i=1; $i<=12; $i++) echo "<option value='$i' ".($i==date('n')?'selected':'').">Tháng $i</option>"; ?>
                        </select>
                        <select name="year" class="form-control">
                            <?php for($y=2023; $y<=2030; $y++) echo "<option value='$y' ".($y==date('Y')?'selected':'').">$y</option>"; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>File Excel (.xlsx)</label>
                    <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
                    <small class="text-muted">Hỗ trợ file BCC chuẩn (Sheet C.C VP, C.C KT...). Hệ thống sẽ tự động đọc 2 dòng (Ký hiệu + Tăng ca).</small>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload & Import</button>
            </form>
        </div>
    </div>
    
<?php include '../../../includes/footer.php'; ?>
