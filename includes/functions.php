<?php
/**
 * Pagination helper
 * @param int $total_records
 * @param int $current_page
 * @param int $limit
 * @param string $link_template
 * @return string HTML
 */
function paginate($total_records, $current_page, $limit, $link_template) {
    $total_pages = ceil($total_records / $limit);
    if ($total_pages <= 1) return '';

    $html = '<ul class="pagination">';
    
    // First & Previous
    if ($current_page > 1) {
        $html .= '<li><a href="' . str_replace('{page}', 1, $link_template) . '"><i class="fas fa-angle-double-left"></i></a></li>';
        $html .= '<li><a href="' . str_replace('{page}', $current_page - 1, $link_template) . '"><i class="fas fa-angle-left"></i></a></li>';
    }

    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $html .= '<li><a href="' . str_replace('{page}', $i, $link_template) . '" class="' . $active . '">' . $i . '</a></li>';
    }

    // Next & Last
    if ($current_page < $total_pages) {
        $html .= '<li><a href="' . str_replace('{page}', $current_page + 1, $link_template) . '"><i class="fas fa-angle-right"></i></a></li>';
        $html .= '<li><a href="' . str_replace('{page}', $total_pages, $link_template) . '"><i class="fas fa-angle-double-right"></i></a></li>';
    }

    $html .= '</ul>';
    return $html;
}

/**
 * Clean input
 */
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// --- HỆ THỐNG PHÂN QUYỀN ---

/**
 * Kiểm tra xem người dùng hiện tại có quyền cụ thể hay không
 */
function has_permission($permission_code) {
    // 1. Admin mặc định có mọi quyền
    if (is_admin()) return true;
    
    // 2. Kiểm tra role_name trong session (Viết hoa thường đều được)
    if (isset($_SESSION['role_name']) && strtoupper($_SESSION['role_name']) === 'ADMIN') return true;
    
    // Nếu không có role_id trong session, không thể kiểm tra quyền chi tiết
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) return false;
    
    static $user_permissions = null;
    
    // Cache quyền trong 1 lần thực thi trang
    if ($user_permissions === null) {
        $role_id = (int)$_SESSION['role_id'];
        $rows = db_fetch_all("
            SELECT p.code 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = ?
        ", [$role_id]);
        $user_permissions = array_column($rows ?? [], 'code');
    }
    
    return in_array($permission_code, $user_permissions);
}

/**
 * Chặn truy cập nếu không có quyền
 */
function require_permission($permission_code) {
    if (!has_permission($permission_code)) {
        header("Location: " . BASE_URL . "404.php?error=no_permission&code=" . $permission_code);
        exit;
    }
}

/**
 * Đồng bộ số ngày phép đã dùng từ bảng chấm công
 */
function sync_leave_balance($employee_id, $year) {
    global $pdo;
    
    // 0. Get Leave Accrual Rate
    $setting = db_fetch_row("SELECT setting_value FROM settings WHERE setting_key = 'leave_monthly_accrual'");
    $monthly_rate = ($setting && $setting['setting_value'] > 0) ? (float)$setting['setting_value'] : 1.0;
    $total_year = $monthly_rate * 12;

    // 1. Chắc chắn đã có dòng số dư phép cho năm này, nếu chưa có thì tạo mặc định
    db_query("INSERT IGNORE INTO employee_leave_balances (employee_id, year, total_days, used_days) VALUES (?, ?, ?, 0)", [$employee_id, $year, $total_year]);
    
    // Update total days if setting changed (optional, but good for consistency)
    db_query("UPDATE employee_leave_balances SET total_days = ? WHERE employee_id = ? AND year = ? AND total_days != ?", [$total_year, $employee_id, $year, $total_year]);

    // 2. Đếm số ngày có ký hiệu P (1 công) và 1/P (0.5 công)
    $sql = "SELECT 
                SUM(CASE 
                    WHEN UPPER(timekeeper_symbol) = 'P' THEN 1.0
                    WHEN UPPER(timekeeper_symbol) = '1/P' THEN 0.5
                    ELSE 0 
                END) as total_used
            FROM attendance 
            WHERE employee_id = ? AND YEAR(date) = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$employee_id, $year]);
    $used = (float)($stmt->fetch()['total_used'] ?? 0);
    
    // 3. Cập nhật chính xác số đã dùng
    db_query("UPDATE employee_leave_balances SET used_days = ? WHERE employee_id = ? AND year = ?", [$used, $employee_id, $year]);
}

/**
 * Redirect helpers
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Set Toast Message
 */
function set_toast($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['toast'] = ['type' => $type, 'message' => $message];
}
/**
 * Check active path for sidebar
 */
function is_active($path) {
    $current_uri = $_SERVER['REQUEST_URI'];
    return strpos($current_uri, $path) !== false ? 'active' : '';
}

/**
 * Remove Vietnamese Accents for Folder Naming
 */
function remove_accents($str) {
    $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
    $str = preg_replace("/(đ)/", 'd', $str);
    $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
    $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
    $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
    $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
    $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
    $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
    $str = preg_replace("/(Đ)/", 'D', $str);
    return $str;
}

function get_emp_folder_name($fullname, $identity_card) {
    $clean_name = strtoupper(remove_accents($fullname));
    $clean_name = preg_replace('/[^A-Z0-9]/', '_', $clean_name); // Replace non-alphanum with _
    $clean_name = preg_replace('/_+/', '_', $clean_name); // Dedupe _
    $last4 = substr($identity_card, -4);
    return trim($clean_name, '_') . '_' . $last4;
}

function is_hr_staff() {
    if (!isset($_SESSION['user_id'])) return false;
    if (is_admin()) return true;
    
    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'manager' || $_SESSION['user_role'] === 'hr')) return true;
    
    return false;
}

/**
 * Check if user is Admin
 */
function is_admin() {
    if (!isset($_SESSION['user_id'])) return false;
    
    // 1. Tuyệt đối tin tưởng ID = 1
    if ($_SESSION['user_id'] == 1) return true;
    
    // 2. Tuyệt đối tin tưởng tên đăng nhập gốc
    if (isset($_SESSION['user_login']) && strtolower($_SESSION['user_login']) === 'admin') return true;
    
    // 3. Kiểm tra các vai trò được gán (Legacy hoặc RBAC)
    $role = isset($_SESSION['user_role']) ? strtoupper($_SESSION['user_role']) : '';
    $role_name = isset($_SESSION['role_name']) ? strtoupper($_SESSION['role_name']) : '';
    
    if ($role === 'ADMIN' || $role_name === 'ADMIN') return true;
    
    return false;
}

/**
 * Get list of Project IDs that the current user manages.
 * Returns: array of IDs, or 'ALL' if admin.
 */
function get_allowed_projects() {
    if (is_admin()) {
        return 'ALL';
    }

    $user_id = $_SESSION['user_id'];
    $rows = db_fetch_all("SELECT id FROM projects WHERE manager_id = ?", [$user_id]);
    
    $ids = [];
    foreach ($rows as $r) {
        $ids[] = $r['id'];
    }
    return $ids;
}

/**
 * Tính công chuẩn trong tháng (Tổng ngày - Ngày nghỉ tuần)
 */
function get_standard_working_days($month, $year) {
    // 1. Get weekly off days from settings
    $setting = db_fetch_row("SELECT setting_value FROM settings WHERE setting_key = 'attendance_weekly_off'");
    $off_days = [7]; // Default Sunday (7 in format 'N')
    
    if ($setting && !empty($setting['setting_value'])) {
        $saved_days = explode(',', $setting['setting_value']);
        foreach($saved_days as $d) {
            // Convert JS/Human day (0=Sun, 6=Sat) to PHP 'N' format (7=Sun, 6=Sat)
            if ($d == '6') $off_days[] = 6; // Saturday
            if ($d == '0') $off_days[] = 7; // Sunday (though 0 usually means Sun in JS)
        }
        $off_days = array_unique($off_days);
    } else {
        // Default only Sunday
        $off_days = [7];
    }

    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $standard_days = 0;
    
    for ($d = 1; $d <= $days_in_month; $d++) {
        $ts = strtotime("$year-$month-$d");
        $day_of_week = date('N', $ts); // 1 (Mon) to 7 (Sun)
        
        if (!in_array($day_of_week, $off_days)) {
            $standard_days++;
        }
    }
    return $standard_days;
}

/**
 * Safe fetch count helper
 */
function get_count($sql, $params = []) {
    $row = db_fetch_row($sql, $params);
    return ($row && isset($row['count'])) ? (int)$row['count'] : 0;
}
?>
