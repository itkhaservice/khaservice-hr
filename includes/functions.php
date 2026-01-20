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
?>