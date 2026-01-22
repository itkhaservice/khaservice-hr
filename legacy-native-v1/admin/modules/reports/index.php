<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
include '../../../includes/header.php';
include '../../../includes/sidebar.php';

// Filter Input
$filter_project = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Fetch Projects for Filter (Restricted)
$allowed_projs = get_allowed_projects();
$where_proj_list = "1=1";
$params_proj_list = [];

if ($allowed_projs !== 'ALL') {
    if (empty($allowed_projs)) $where_proj_list = "1=0";
    else {
        $in_placeholder = implode(',', array_fill(0, count($allowed_projs), '?'));
        $where_proj_list = "id IN ($in_placeholder)";
        $params_proj_list = $allowed_projs;
    }
}
$projects = db_fetch_all("SELECT id, name, headcount_required FROM projects WHERE $where_proj_list ORDER BY name ASC", $params_proj_list);

// 1. Overview Statistics Logic
$where_clause = "e.status = 'working'";
$params = [];
$proj_info = null;

// Apply Permission Filter to Data Queries
if ($allowed_projs !== 'ALL') {
    if ($filter_project) {
        if (in_array($filter_project, $allowed_projs)) {
            $where_clause .= " AND e.current_project_id = ?";
            $params[] = $filter_project;
            $proj_info = db_fetch_row("SELECT * FROM projects WHERE id = ?", [$filter_project]);
        } else {
            $where_clause .= " AND 1=0";
        }
    } else {
        if (!empty($allowed_projs)) {
            $in_placeholder = implode(',', array_fill(0, count($allowed_projs), '?'));
            $where_clause .= " AND e.current_project_id IN ($in_placeholder)";
            $params = array_merge($params, $allowed_projs);
        } else {
            $where_clause .= " AND 1=0";
        }
    }
} else {
    if ($filter_project) {
        $where_clause .= " AND e.current_project_id = ?";
        $params[] = $filter_project;
        $proj_info = db_fetch_row("SELECT * FROM projects WHERE id = ?", [$filter_project]);
    }
}

// Total Employees in Scope
$total_emps = db_fetch_row("SELECT COUNT(*) as count FROM employees e WHERE $where_clause", $params)['count'];

// Recalculate Required Headcount
if ($proj_info) {
    $sum_req = db_fetch_row("SELECT SUM(count_required) as total FROM project_positions WHERE project_id = ?", [$proj_info['id']]);
    if ($sum_req && $sum_req['total'] > 0) $proj_info['headcount_required'] = $sum_req['total'];
}

// 2. Department & Position Matrix
$sql_matrix = "
    SELECT d.id as dept_id, d.name as dept_name, e.position, COUNT(e.id) as emp_count
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE $where_clause
    GROUP BY d.id, d.name, e.position
    ORDER BY d.name ASC, e.position ASC
";
$raw_matrix = db_fetch_all($sql_matrix, $params);

$required_data = []; 
if ($proj_info) {
    $req_rows = db_fetch_all("SELECT department_id, position_name, count_required FROM project_positions WHERE project_id = ?", [$proj_info['id']]);
    foreach ($req_rows as $r) {
        $required_data[$r['department_id']][$r['position_name']] = $r['count_required'];
    }
}

$matrix_display = [];
$has_shortage = false;

foreach ($raw_matrix as $row) {
    $d_id = $row['dept_id'] ?? 0;
    $d_name = $row['dept_name'] ?? 'Chưa phân loại';
    $pos = $row['position'] ?? 'Chưa có chức vụ';
    if (!isset($matrix_display[$d_name])) $matrix_display[$d_name] = [];
    $req = $required_data[$d_id][$pos] ?? 0;
    $act = $row['emp_count'];
    if ($req > 0 && $act < $req) $has_shortage = true;
    $matrix_display[$d_name][$pos] = ['actual' => $act, 'required' => $req];
}

if ($proj_info) {
    $all_depts = db_fetch_all("SELECT id, name FROM departments");
    $dept_names_map = []; foreach($all_depts as $ad) $dept_names_map[$ad['id']] = $ad['name'];
    foreach ($required_data as $d_id => $positions) {
        $d_name = $dept_names_map[$d_id] ?? 'Chưa phân loại';
        foreach ($positions as $pos_name => $count_req) {
            if (!isset($matrix_display[$d_name][$pos_name])) {
                $has_shortage = true;
                $matrix_display[$d_name][$pos_name] = ['actual' => 0, 'required' => $count_req];
            }
        }
    }
}

// 3. Missing Documents Report (FIXED Logic)
$required_docs_db = db_fetch_all("SELECT code, name FROM document_settings WHERE is_required = 1");
$mandatory_docs = []; $doc_names = [];
foreach ($required_docs_db as $rd) {
    $mandatory_docs[] = $rd['code'];
    $doc_names[$rd['code']] = $rd['name'];
}

$missing_sql = "
    SELECT e.id, e.code, e.fullname, p.name as proj_name,
    (SELECT GROUP_CONCAT(doc_type) FROM documents d WHERE d.employee_id = e.id AND d.is_submitted = 1) as submitted_types
    FROM employees e
    LEFT JOIN projects p ON e.current_project_id = p.id
    WHERE $where_clause
";
$missing_docs_list = db_fetch_all($missing_sql, $params);

$final_missing_report = [];
foreach ($missing_docs_list as $row) {
    $submitted_str = $row['submitted_types'] ?? '';
    $submitted = !empty($submitted_str) ? explode(',', $submitted_str) : [];
    $submitted = array_map('trim', $submitted);
    $missing = array_diff($mandatory_docs, $submitted);
    
    if (!empty($missing)) {
        $row['missing_count'] = count($missing);
        $row['missing_labels'] = implode(', ', $missing);
        $final_missing_report[] = $row;
    }
}
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Báo cáo Nhân sự Thông minh</h1>
        </div>

        <form method="GET" class="filter-section">
            <div style="flex: 1; display: flex; align-items: center; gap: 10px;">
                <label style="font-weight: 600; white-space: nowrap;">Dự án:</label>
                <select name="project_id" onchange="this.form.submit()" style="flex: 1; max-width: 400px;">
                    <option value="0">-- Toàn bộ Công ty --</option>
                    <?php foreach($projects as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $filter_project == $p['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="export.php?project_id=<?php echo $filter_project; ?>&type=structure" class="btn btn-success" style="background: #107c41; color: white; border: none;">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </a>
                <?php if($filter_project): ?>
                    <a href="index.php" class="btn btn-secondary">Xem Toàn bộ</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Project Overview -->
        <?php if ($proj_info): 
            $required = (int)$proj_info['headcount_required']; $actual = $total_emps;
            $percent = $required > 0 ? round(($actual / $required) * 100) : 0;
            $is_staffing_ok = ($actual >= $required && !$has_shortage);
            $status_color = $is_staffing_ok ? '#24a25c' : ($percent >= 80 ? '#f59e0b' : '#dc2626');
        ?>
            <div class="card" style="border-top: 4px solid <?php echo $status_color; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0; font-size: 1.2rem; color: var(--text-main);"><i class="fas fa-chart-pie"></i> Tổng quan</h2>
                    <span class="badge" style="background: <?php echo $status_color; ?>; color: #fff; font-size: 0.9rem;">
                        <?php echo $is_staffing_ok ? 'Nhân sự đảm bảo' : 'Thiếu nhân sự / Sai vị trí'; ?>
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 30px;">
                    <div style="text-align: center;"><div style="font-size: 0.85rem; color: #64748b; text-transform: uppercase;">Thực tế</div><div style="font-size: 2rem; font-weight: 700; color: <?php echo $status_color; ?>"><?php echo $actual; ?></div></div>
                    <div style="font-size: 2rem; color: #cbd5e1; font-weight: 300;">/</div>
                    <div style="text-align: center;"><div style="font-size: 0.85rem; color: #64748b; text-transform: uppercase;">Định biên</div><div style="font-size: 2rem; font-weight: 700; color: #334155;"><?php echo $required; ?></div></div>
                    <div style="flex: 1;">
                        <div style="height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin-bottom: 5px;"><div style="height: 100%; width: <?php echo min(100, $percent); ?>%; background: <?php echo $status_color; ?>; transition: width 0.5s;"></div></div>
                        <div style="text-align: right; font-weight: 600; color: #64748b;"><?php echo $percent; ?>%</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Matrix -->
        <h3 class="section-title" style="margin: 30px 0 15px 0; color: #334155; font-size: 1.1rem; border-left: 4px solid var(--primary-color); padding-left: 10px;"><i class="fas fa-sitemap"></i> Cơ cấu Nhân sự chi tiết</h3>
        <div class="dept-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
            <?php foreach ($matrix_display as $dept_name => $positions): 
                $dept_total_actual = array_sum(array_column($positions, 'actual'));
            ?>
                <div class="card" style="padding: 0; overflow: hidden; border: 1px solid #e2e8f0;">
                    <div style="padding: 12px 15px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;"><h4 style="margin: 0; font-size: 0.95rem; color: #334155; font-weight: 700;"><?php echo $dept_name; ?></h4><span class="badge badge-secondary"><?php echo $dept_total_actual; ?> NS</span></div>
                    <div style="padding: 10px 15px;">
                        <table class="table table-sm" style="width: 100%; margin: 0;"><tbody>
                            <?php foreach ($positions as $pos_name => $data): 
                                $act = $data['actual']; $req = $data['required']; $is_short = ($req > 0 && $act < $req);
                            ?>
                                <tr <?php echo $is_short ? 'style="background: #fff1f2;"' : ''; ?>>
                                    <td style="padding: 10px 5px; border-bottom: 1px solid #f1f5f9;"><div style="font-weight: 600; color: #1e293b;"><?php echo $pos_name; ?></div><?php if($is_short): ?><small style="color: #dc2626; font-weight: 700;">Thiếu <?php echo ($req-$act); ?> NS</small><?php elseif($req > 0): ?><small style="color: #166534;">Đủ định biên</small><?php endif; ?></td>
                                    <td style="text-align: right; width: 90px; vertical-align: middle; border-bottom: 1px solid #f1f5f9;"><span style="font-size: 1.1rem; font-weight: 700; color: <?php echo $is_short ? '#dc2626' : ($req > 0 ? '#166534' : '#475569'); ?>"><?php echo $act; ?></span><?php if($req > 0): ?><span style="color: #94a3b8; font-size: 0.85rem; font-weight:400;">/ <?php echo $req; ?></span><?php endif; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody></table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Missing Documents -->
        <div style="margin-top: 30px;">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #dc2626;"><i class="fas fa-exclamation-triangle"></i> Cảnh báo hồ sơ thiếu</h3>
                    <span class="badge badge-danger"><?php echo count($final_missing_report); ?> trường hợp</span>
                </div>
                <div class="note-box" style="background: #fffbeb; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #fcd34d; font-size: 0.9rem;">
                    <div style="font-weight: 600; margin-bottom: 5px; color: #92400e;">Giải thích viết tắt:</div>
                    <ul style="display: flex; flex-wrap: wrap; gap: 20px; margin: 0; padding-left: 0; list-style: none; color: #4b5563;">
                        <?php foreach($doc_names as $code => $name): ?><li><span style="font-weight:700; color:#dc2626;"><?php echo $code; ?>:</span> <?php echo $name; ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                    <table class="table"><thead style="position: sticky; top: 0; background: #fff; z-index: 10;"><tr><th>Mã NV</th><th>Họ tên</th><th>Dự án</th><th>Thiếu</th><th width="100">Xử lý</th></tr></thead><tbody>
                        <?php if (empty($final_missing_report)): ?><tr><td colspan="5" style="text-align:center; padding: 20px;">Không có nhân viên thiếu hồ sơ.</td></tr><?php else: ?>
                            <?php foreach ($final_missing_report as $m): ?>
                                <tr><td><?php echo $m['code']; ?></td><td><strong><?php echo $m['fullname']; ?></strong></td><td><?php echo $m['proj_name']; ?></td><td style="color: #dc2626; font-size: 0.85rem;"><?php echo $m['missing_labels']; ?></td><td><a href="../employees/documents.php?id=<?php echo $m['id']; ?>" class="btn btn-secondary btn-sm">Nộp</a></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
    </div>
<?php include '../../../includes/footer.php'; ?>