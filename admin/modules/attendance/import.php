<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/SimpleXLSX.php';

// --- PHP EXCEL PARSER LOGIC ---
function parse_excel_php($file_path) {
    if (!class_exists('SimpleXLSX')) return ['error' => 'Thiếu thư viện SimpleXLSX'];
    
    $xlsx = SimpleXLSX::parse($file_path);
    if (!$xlsx) return ['error' => SimpleXLSX::parseError()];

    $all_data = [];
    $ignored_sheets = ['MỨC LƯƠNG KHOÁN', 'phieuung', 'QUA', 'Sheet3', 'The bao hiem'];

    // Scan all sheets (limited to first 10 to avoid memory issues if too large)
    // Note: SimpleXLSX loads sheet by index.
    // Since SimpleXLSX mini wrapper might not list sheet names easily, we try reading index 0,1,2...
    // The mini wrapper I wrote supports `rows($index)`. 
    
    // For this mini implementation, we assume Data is on Sheet 0 or iterate until fail
    for ($sheet_idx = 0; $sheet_idx < 5; $sheet_idx++) {
        $rows = $xlsx->rows($sheet_idx);
        if (!$rows) break; // Stop if no more sheets

        // 1. Find Header Row (containing 1, 2... 31)
        $header_row_idx = -1;
        $day_cols = []; // map col_index => day_number

        foreach ($rows as $r_idx => $row) {
            if ($r_idx > 20) break; // Scan top 20 rows
            
            $count_days = 0;
            $temp_day_cols = [];
            foreach ($row as $c_idx => $val) {
                if (is_numeric($val) && $val >= 1 && $val <= 31) {
                    $count_days++;
                    $temp_day_cols[$c_idx] = (int)$val;
                }
            }
            
            if ($count_days > 20) {
                $header_row_idx = $r_idx;
                $day_cols = $temp_day_cols;
                break;
            }
        }

        if ($header_row_idx == -1) continue;

        // 2. Find Name Column
        $name_col_idx = 1; // Default
        if ($header_row_idx > 0) {
            $title_row = $rows[$header_row_idx - 1];
            foreach ($title_row as $idx => $val) {
                $v_upper = mb_strtoupper((string)$val, 'UTF-8');
                if (strpos($v_upper, 'HỌ VÀ TÊN') !== false || strpos($v_upper, 'HỌ TÊN') !== false) {
                    $name_col_idx = $idx;
                    break;
                }
            }
        }

        // 3. Iterate Data
        $total_rows = count($rows);
        $i = $header_row_idx + 1;
        
        while ($i < $total_rows) {
            $row = $rows[$i];
            
            // Safe get name
            $name_val = isset($row[$name_col_idx]) ? trim((string)$row[$name_col_idx]) : '';
            
            // Check valid employee row
            if (mb_strlen($name_val) > 2) {
                $v_upper = mb_strtoupper($name_val, 'UTF-8');
                if (strpos($v_upper, 'CỘNG') !== false || strpos($v_upper, 'KÝ TÊN') !== false) {
                    $i++; continue;
                }

                $emp_data = [
                    'name' => $name_val,
                    'attendance' => []
                ];

                // Get Symbols
                foreach ($day_cols as $c_idx => $day_num) {
                    $symbol = isset($row[$c_idx]) ? trim((string)$row[$c_idx]) : '';
                    if ($symbol !== '') {
                        $emp_data['attendance'][$day_num] = [
                            'symbol' => $symbol,
                            'ot' => 0
                        ];
                    }
                }

                // Check Next Row for OT (if name is empty)
                $next_idx = $i + 1;
                if ($next_idx < $total_rows) {
                    $next_row = $rows[$next_idx];
                    $next_name = isset($next_row[$name_col_idx]) ? trim((string)$next_row[$name_col_idx]) : '';
                    
                    if ($next_name === '') {
                        // This is OT row
                        foreach ($day_cols as $c_idx => $day_num) {
                            $ot_val = isset($next_row[$c_idx]) ? $next_row[$c_idx] : 0;
                            if (is_numeric($ot_val) && $ot_val > 0) {
                                if (!isset($emp_data['attendance'][$day_num])) {
                                    $emp_data['attendance'][$day_num] = ['symbol' => '', 'ot' => 0];
                                }
                                $emp_data['attendance'][$day_num]['ot'] = (float)$ot_val;
                            }
                        }
                        $i++; // Skip OT row
                    }
                }

                $all_data[] = $emp_data;
            }
            $i++;
        }
    }
    return $all_data;
}

// Handle Upload
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if ($ext != 'xlsx') {
        $message = '<div class="alert alert-danger">Chỉ chấp nhận file Excel (.xlsx)</div>';
    } else {
        $upload_path = '../../../upload/temp/' . time() . '_' . $file['name'];
        // Ensure folder exists
        if (!file_exists('../../../upload/temp')) mkdir('../../../upload/temp', 0777, true);

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            
            // --- USE NEW PHP PARSER ---
            $data = parse_excel_php($upload_path);
            
            // Delete temp file
            unlink($upload_path);
            
            if (isset($data['error'])) {
                $message = '<div class="alert alert-danger">Lỗi xử lý file: ' . $data['error'] . '</div>';
            } elseif (is_array($data) && !empty($data)) {
                // Process Data
                $count_success = 0;
                $count_fail = 0;
                $log_details = [];
                
                $month = (int)$_POST['month'];
                $year = (int)$_POST['year'];
                
                foreach ($data as $emp) {
                    $name = trim($emp['name']);
                    // Find employee by name (Simple Normalize)
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
                        $log_details[] = "FAIL: Không tìm thấy nhân viên '$name' trong DB";
                    }
                }
                
                $message = "<div class='alert alert-success'>Đã import thành công dữ liệu chấm công cho: $count_success nhân viên.</div>";
                if($count_fail > 0) {
                     $message .= "<div class='alert alert-warning' style='max-height: 200px; overflow-y: auto;'>Không tìm thấy: " . $count_fail . " nhân viên<br>" . implode('<br>', $log_details) . "</div>";
                }
                
            } else {
                $message = '<div class="alert alert-warning">Không tìm thấy dữ liệu hợp lệ trong file Excel. Vui lòng kiểm tra lại mẫu file (Sheet đầu tiên, có dòng ngày 1-31).</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Lỗi upload file lên server.</div>';
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
            <h1 class="page-title">Import Bảng Chấm Công (Excel)</h1>
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
                            <?php for($y=2024; $y<=2030; $y++) echo "<option value='$y' ".($y==date('Y')?'selected':'').">$y</option>"; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>File Excel (.xlsx)</label>
                    <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
                    <small class="text-muted">
                        <strong>Lưu ý cấu trúc:</strong><br>
                        - File phải có đuôi .xlsx<br>
                        - Phải có dòng chứa ngày 1, 2, ... 31.<br>
                        - Cột tên nhân viên phải chứa chữ "HỌ TÊN".<br>
                        - Hệ thống tự động đọc 2 dòng (Dòng trên: Ký hiệu, Dòng dưới: Tăng ca nếu cột tên rỗng).
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload & Import</button>
            </form>
        </div>
    </div>
    
<?php include '../../../includes/footer.php'; ?>
