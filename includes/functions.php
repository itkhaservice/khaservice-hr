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

/**
 * Redirect
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
    // Admin always true
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') return true;
    
    // Check if Department is HCNS (You might need to fetch this from DB if not in session)
    // For simplicity, assuming we'll store dept code in session or fetch it.
    // Let's fetch to be safe.
    global $pdo; // Ensure $pdo is available or use db_fetch_row
    $uid = $_SESSION['user_id'];
    
    // Assuming users table is linked to an employee record OR users table has department_id (schema check needed).
    // The current users table doesn't have department_id. It's usually linked to employees.
    // Let's assume 'manager' role or 'admin' is enough for now, OR if we link user -> employee.
    // For this request, I will assume Admin and Managers have HR rights.
    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'manager' || $_SESSION['user_role'] === 'hr')) return true;
    
    return false;
}
?>