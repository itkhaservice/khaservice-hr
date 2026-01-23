<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

$allowed_projs = get_allowed_projects();
$project_options = ($allowed_projs === 'ALL') 
    ? db_fetch_all("SELECT id, name FROM projects WHERE status = 'active' ORDER BY name ASC")
    : (!empty($allowed_projs) ? db_fetch_all("SELECT id, name FROM projects WHERE id IN (".implode(',',$allowed_projs).") AND status = 'active' ORDER BY name ASC") : []);

// BỎ AUTO-PICK DỰ ÁN ĐẦU TIÊN
// if ($project_id == 0 && !empty($project_options)) $project_id = $project_options[0]['id'];

$is_locked = db_fetch_row("SELECT is_locked FROM attendance_locks WHERE month = ? AND year = ? AND (project_id = 0 OR project_id = ?) AND is_locked = 1", [$month, $year, $project_id]) ? true : false;

$employees = ($project_id > 0) ? db_fetch_all("SELECT e.id, e.fullname, e.code, e.position, d.name as dept_name 
                               FROM employees e 
                               LEFT JOIN departments d ON e.department_id = d.id 
                               LEFT JOIN positions p ON e.position_id = p.id
                               WHERE e.current_project_id = ? AND e.status = 'working' 
                               ORDER BY d.stt ASC, p.stt ASC, e.fullname ASC", [$project_id]) : [];

$att_data = [];
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
if ($project_id > 0 && !empty($employees)) {
    $start_date = sprintf("%04d-%02d-01", $year, $month);
    $end_date = sprintf("%04d-%02d-%02d", $year, $month, $days_in_month);
    $emp_ids = array_column($employees, 'id');
    if (!empty($emp_ids)) {
        $raw_att = db_fetch_all("SELECT employee_id, DAY(date) as day, timekeeper_symbol, overtime_hours, target_project_id FROM attendance WHERE date BETWEEN ? AND ? AND employee_id IN (".implode(',',$emp_ids).")", [$start_date, $end_date]);
        foreach ($raw_att as $r) $att_data[$r['employee_id']][$r['day']] = ['symbol' => $r['timekeeper_symbol'], 'ot' => $r['overtime_hours'], 'target_proj' => $r['target_project_id']];
    }
}

// Map Project Names for Quick Lookup
$proj_map = []; foreach($project_options as $p) $proj_map[$p['id']] = $p['name'];
$cross_project_notes = [];

// Xử lý Khóa/Mở khóa bảng công (Chỉ dành cho Admin có quyền ALL)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_lock']) && $allowed_projs === 'ALL') {
    $lock_action = $_POST['toggle_lock'] == 'lock' ? 1 : 0;
    db_query("INSERT INTO attendance_locks (project_id, month, year, is_locked, locked_by, locked_at) 
              VALUES (?, ?, ?, ?, ?, NOW()) 
              ON DUPLICATE KEY UPDATE is_locked = ?, locked_by = ?, locked_at = NOW()", 
              [$project_id, $month, $year, $lock_action, $_SESSION['user_id'], $lock_action, $_SESSION['user_id']]);
    
    set_toast('success', ($lock_action ? 'Đã khóa' : 'Đã mở khóa') . ' bảng chấm công thành công!');
    redirect("index.php?month=$month&year=$year&project_id=$project_id");
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<!-- Context Menu for Cross-Project Selection -->
<ul id="projectContextMenu" class="dropdown-menu" style="display:none; position:absolute; z-index:10000; min-width:200px; padding: 5px 0;">
    <li class="dropdown-header" style="padding: 5px 15px; font-weight:700; background:#f1f5f9;">Gán dự án tăng cường:</li>
    <li><a href="javascript:void(0)" class="dropdown-item" onclick="assignProject(0)" style="padding: 8px 15px; display:block; color:#333;"><i class="fas fa-undo"></i> Xóa (Về dự án gốc)</a></li>
    <li class="dropdown-divider" style="height:1px; margin:5px 0; background:#e2e8f0;"></li>
    <?php foreach($project_options as $p): if($p['id'] == $project_id) continue; ?>
        <li><a href="javascript:void(0)" class="dropdown-item" onclick="assignProject(<?php echo $p['id']; ?>)" style="padding: 8px 15px; display:block; color:#333;"><?php echo htmlspecialchars($p['name']); ?></a></li>
    <?php endforeach; ?>
</ul>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>
    <div class="content-wrapper" style="overflow: hidden; display: flex; flex-direction: column; height: calc(100vh - 65px);">
        <div class="action-header">
            <div style="display:flex; align-items:center; gap:10px;">
                <h1 class="page-title">Bảng Chấm Công - <?php echo "$month/$year"; ?></h1>
                <span class="badge <?php echo $is_locked?'badge-danger':'badge-success'; ?>"><i class="fas <?php echo $is_locked?'fa-lock':'fa-pen'; ?>"></i> <?php echo $is_locked?'Đã Khóa':'Đang mở'; ?></span>
            </div>
            <div class="header-actions">
                <?php if (!$is_locked): ?>
                    <button type="button" class="btn btn-primary" onclick="saveAttendance()"><i class="fas fa-save"></i> Lưu dữ liệu</button>
                <?php endif; ?>
                
                <?php if ($allowed_projs === 'ALL' && $project_id > 0): ?>
                    <form method="POST" id="lockForm" style="display:inline;">
                        <input type="hidden" name="toggle_lock" value="<?php echo $is_locked ? 'unlock' : 'lock'; ?>">
                        <button type="button" class="btn <?php echo $is_locked ? 'btn-warning' : 'btn-danger'; ?>" onclick="confirmLock()">
                            <i class="fas <?php echo $is_locked ? 'fa-lock-open' : 'fa-lock'; ?>"></i> <?php echo $is_locked ? 'Mở khóa' : 'Khóa sổ'; ?>
                        </button>
                    </form>
                <?php endif; ?>

                <button type="button" class="btn btn-secondary" onclick="window.open('print.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&project_id=<?php echo $project_id; ?>', '_blank')"><i class="fas fa-print"></i> In</button>
                <button type="button" class="btn btn-info"><i class="fas fa-file-excel"></i> Xuất Excel</button>
                <a href="import.php" class="btn btn-success"><i class="fas fa-file-import"></i> Import</a>
            </div>
        </div>

        <form method="GET" class="filter-section">
            <select name="project_id">
                <option value="0">-- Chọn Dự án --</option>
                <?php foreach($project_options as $p) echo "<option value='{$p['id']}' ".($p['id']==$project_id?'selected':'').">{$p['name']}</option>"; ?>
            </select>
            <select name="month">
                <?php for($i=1;$i<=12;$i++) echo "<option value='$i' ".($i==$month?'selected':'').">Tháng $i</option>"; ?>
            </select>
            <select name="year">
                <?php for($y=2023;$y<=2030;$y++) echo "<option value='$y' ".($y==$year?'selected':'').">$y</option>"; ?>
            </select>
            <div style="display: flex; gap: 5px;">
                <button type="submit" class="btn btn-secondary" style="flex: 1;"><i class="fas fa-filter"></i> Lọc</button>
                <?php if ($project_id > 0 || $month != date('n') || $year != date('Y')): ?>
                    <a href="index.php" class="btn btn-danger" title="Xóa lọc" style="min-width: 45px;"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($project_id == 0): ?>
            <div class="card" style="text-align: center; padding: 50px; color: #94a3b8; border: 2px dashed #e2e8f0;">
                <i class="fas fa-city" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                <h3>Vui lòng chọn Dự án</h3>
                <p>Hãy chọn dự án từ danh sách bên trên để thực hiện chấm công.</p>
            </div>
        <?php elseif (empty($employees)): ?>
            <div class="alert alert-info">Dự án này chưa có nhân viên để chấm công.</div>
        <?php else: ?>
            <div id="attendance-card" class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border-color); flex: 1; display: flex; flex-direction: column; margin-bottom: 0;">
                <!-- Legend -->
                <div style="padding: 8px 15px; border-bottom: 1px solid var(--border-color); background: var(--bg-main); font-size: 0.8rem; flex-shrink: 0;">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                        <span><b>Ký hiệu:</b></span>
                        <span class="legend-item"><span class="symbol-sample" style="color:#166534;">X</span>: Đi làm</span>
                        <span class="legend-item"><span class="symbol-sample" style="color:#1e40af;">P</span>: Phép</span>
                        <span class="legend-item"><span class="symbol-sample" style="background:#64748b; color:#fff;">OF</span>: Nghỉ tuần</span>
                        <span class="legend-item"><span class="symbol-sample" style="color:#991b1b;">L,T</span>: Lễ tết</span>
                        
                        <div style="margin-left: auto; display: flex; gap: 10px; align-items: center;">
                            <a href="javascript:void(0)" class="btn-fullscreen" onclick="toggleFullScreen()" title="Toàn màn hình">
                                <i class="fas fa-expand"></i> Toàn màn hình
                            </a>
                            <a href="javascript:void(0)" onclick="$('#moreLegend').slideToggle();" style="font-weight: 500;">
                                <i class="fas fa-info-circle"></i> Thêm
                            </a>
                        </div>
                    </div>
                    
                    <!-- Lớp phủ thông báo Esc -->
                    <div id="fullscreen-hint">Nhấn <b>Esc</b> để thoát chế độ toàn màn hình</div>
                    <div id="moreLegend" style="display: none; margin-top: 5px; padding-top: 5px; border-top: 1px dashed var(--border-color);">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; font-size: 0.8rem;">
                            <span><b>1/2</b>: Làm nửa ngày</span>
                            <span><b>1/p</b>: Nửa phép, nửa làm</span>
                            <span><b>Ô</b>: Nghỉ ốm</span>
                            <span><b>R</b>: Nghỉ việc riêng</span>
                            <span><b>CĐ</b>: Chế độ</span>
                            <span><b>ĐH</b>: Đi học/Họp</span>
                            <span><b>Ts</b>: Thai sản</span>
                            <span><b>Nb</b>: Nghỉ bù</span>
                            <span><b>F</b>: Làm lễ, tết</span>
                            <span><b>F1</b>: Làm chủ nhật/ngày nghỉ</span>
                            <span><b>1/F1</b>: Làm nửa ngày CN/nghỉ</span>
                            <span><b>1/lt</b>: Làm nửa ngày lễ/tết</span>
                        </div>
                        <div style="margin-top: 10px; color: var(--text-sub); font-style: italic;">
                            * <b>Ô dưới:</b> Nhập số giờ làm thêm (tăng ca). Nhấp đúp vào ô ký hiệu để đánh dấu nhanh 'X'.<br>
                            * <b>Gán dự án:</b> Chuột phải vào ô TC để chọn dự án hỗ trợ.
                        </div>
                    </div>
                </div>

                <div class="table-container" style="flex: 1; overflow: auto; position: relative; width: 100%; height: 100%;">
                    <table class="attendance-table table-bordered">
                        <colgroup>
                            <col style="width: 50px; min-width: 50px;">
                            <col style="width: 220px; min-width: 220px;">
                            <?php for($d=1; $d<=$days_in_month; $d++) echo '<col style="width: 45px; min-width: 45px;">'; ?>
                            <col style="width: 40px; min-width: 40px;">
                            <col style="width: 40px; min-width: 40px;">
                            <col style="width: 40px; min-width: 40px;">
                            <col style="width: 40px; min-width: 40px;">
                            <col style="width: 40px; min-width: 40px;">
                            <col style="width: 40px; min-width: 40px;">
                            <col style="width: 55px; min-width: 55px;">
                        </colgroup>
                        <thead>
                            <tr style="height: 30px;">
                                <th rowspan="3" class="fix-l" style="left: 0; z-index: 20; text-align: center;">STT</th>
                                <th rowspan="3" class="fix-l" style="left: 50px; z-index: 20; border-right: 2px solid #cbd5e1;">Nhân viên</th>
                                <th colspan="<?php echo $days_in_month; ?>" class="text-center">Ngày trong tháng</th>
                                <th colspan="3" class="fix-r" style="right: 175px; width: 120px; z-index: 20; border-left: 2px solid #cbd5e1;">Ngày nghỉ</th>
                                <th colspan="3" class="fix-r" style="right: 55px; width: 120px; z-index: 20; border-left: 1px solid #cbd5e1;">Tăng ca</th>
                                <th rowspan="3" class="fix-r" style="right: 0; width: 55px; min-width: 55px; z-index: 20; border-left: 2px solid #cbd5e1;">Tổng</th>
                            </tr>
                            <tr style="height: 25px;">
                                <?php for($d=1; $d<=$days_in_month; $d++): ?><th class="text-center" style="min-width: 45px;"><?php echo $d; ?></th><?php endfor; ?>
                                <th rowspan="2" class="fix-r shadow-left" style="right: 255px; width: 40px; font-size: 0.7rem; border-left: 2px solid #cbd5e1;">P/CĐ</th>
                                <th rowspan="2" class="fix-r" style="right: 215px; width: 40px; font-size: 0.7rem;">Khác</th>
                                <th rowspan="2" class="fix-r" style="right: 175px; width: 40px; font-size: 0.7rem;">Lễ</th>
                                <th rowspan="2" class="fix-r" style="right: 135px; width: 40px; font-size: 0.7rem; border-left: 1px solid #cbd5e1;">TC</th>
                                <th rowspan="2" class="fix-r" style="right: 95px; width: 40px; font-size: 0.7rem;">CN</th>
                                <th rowspan="2" class="fix-r" style="right: 55px; width: 40px; font-size: 0.7rem;">Lễ</th>
                            </tr>
                            <tr style="height: 25px;">
                                <?php for($d=1; $d<=$days_in_month; $d++): 
                                    $ts = strtotime("$year-$month-$d"); $dow = date('N', $ts);
                                    echo "<th class='text-center ".($dow==7?'is-sunday':'')."' style='font-size:0.7rem; font-weight:400; height:25px; vertical-align: middle;'>" . ['','T2','T3','T4','T5','T6','T7','CN'][$dow] . "</th>";
                                endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $stt = 0; foreach($employees as $emp): $stt++; $s = ['p_cd'=>0, 'other'=>0, 'holiday'=>0, 'ot_norm'=>0, 'ot_sun'=>0, 'ot_hol'=>0, 'total'=>0]; ?>
                                <tr data-emp-id="<?php echo $emp['id']; ?>">
                                    <td class="fix-l" style="left: 0; font-weight: 600; text-align: center;"><?php echo $stt; ?></td>
                                    <td class="fix-l" style="left: 50px; border-right: 2px solid #cbd5e1; padding: 4px 8px;">
                                        <div style="color: var(--primary-dark); font-weight: 700; font-size: 0.85rem;"><?php echo $emp['fullname']; ?></div>
                                        <div style="font-size: 0.65rem; color: #64748b;"><?php echo $emp['dept_name'] ?? '-'; ?> - <?php echo $emp['position']; ?></div>
                                    </td>
                                    <?php for($d=1; $d<=$days_in_month; $d++): 
                                        $cell = $att_data[$emp['id']][$d] ?? ['symbol'=>'','ot'=>0, 'target_proj'=>0]; 
                                        $sym = strtoupper($cell['symbol']); $ot = (float)$cell['ot']; $t_proj = (int)($cell['target_proj'] ?? 0);
                                        $is_sun = (date('N', strtotime("$year-$month-$d")) == 7);
                                        
                                        // Collect Cross-Project Notes
                                        if ($t_proj > 0 && $t_proj != $project_id) {
                                            $proj_name = $proj_map[$t_proj] ?? "Dự án #$t_proj";
                                            $cross_project_notes[] = [
                                                'date' => "$d/$month/$year",
                                                'emp' => $emp['fullname'],
                                                'proj' => $proj_name,
                                                'ot' => $ot
                                            ];
                                        }

                                        if (in_array($sym, ['X','ĐH','DH'])) { $s['total'] += 1; }
                                        elseif (in_array($sym, ['1/2', '1/P', '1/CĐ', '1/CD'])) { $s['total'] += 0.5; }
                                        if (in_array($sym, ['P','CĐ','CD'])) { $s['p_cd'] += 1; }
                                        elseif (in_array($sym, ['1/P','1/CĐ','1/CD'])) { $s['p_cd'] += 0.5; }
                                        elseif (in_array($sym, ['Ô','O','TS','R','VR','CO','NB'])) { $s['other'] += 1; }
                                        elseif (in_array($sym, ['L','T','L,T','L, T'])) { $s['holiday'] += 1; }

                                        if ($ot > 0) {
                                            if (in_array($sym, ['F','L','T','L,T','L, T'])) $s['ot_hol'] += $ot;
                                            elseif (in_array($sym, ['F1']) || $is_sun) $s['ot_sun'] += $ot;
                                            else $s['ot_norm'] += $ot;
                                        }
                                        
                                        // Visual Class for Cross-Project
                                        $ot_class = ($t_proj > 0 && $t_proj != $project_id) ? 'has-cross-proj' : '';
                                    ?>
                                        <td class="<?php echo $is_sun?'is-sunday':''; ?>">
                                            <?php if($is_locked): ?>
                                                <div class="locked-cell-view <?php echo $ot_class; ?>">
                                                    <div class="sym symbol" data-day="<?php echo $d; ?>" data-is-sunday="<?php echo $is_sun ? '1' : '0'; ?>"><?php echo $sym; ?></div>
                                                    <div class="ot" data-day="<?php echo $d; ?>"><?php echo $ot > 0 ? $ot : ''; ?></div>
                                                </div>
                                            <?php else: ?>
                                                <input type="text" class="att-input symbol" data-day="<?php echo $d; ?>" data-is-sunday="<?php echo $is_sun?'1':'0'; ?>" value="<?php echo $sym; ?>" maxlength="4" autocomplete="off" oninput="this.value = this.value.toUpperCase();" ondblclick="toggleSymbol(this)" onfocus="this.select()">
                                                <input type="text" class="att-input ot <?php echo $ot_class; ?>" data-day="<?php echo $d; ?>" data-target-proj-id="<?php echo $t_proj; ?>" value="<?php echo $ot?:''; ?>" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" autocomplete="off" onfocus="this.select()" oncontextmenu="showProjectMenu(event, this); return false;">
                                            <?php endif; ?>
                                        </td>
                                    <?php endfor; ?>
                                    <td class="fix-r sum-p-cd" style="right: 255px; border-left: 2px solid #cbd5e1;"><?php echo $s['p_cd'] ?: ''; ?></td>
                                    <td class="fix-r sum-other" style="right: 215px;"><?php echo $s['other'] ?: ''; ?></td>
                                    <td class="fix-r sum-holiday" style="right: 175px;"><?php echo $s['holiday'] ?: ''; ?></td>
                                    <td class="fix-r sum-ot-normal" style="right: 135px; border-left: 1px solid #cbd5e1; color: #dc2626;"><?php echo $s['ot_norm'] ?: ''; ?></td>
                                    <td class="fix-r sum-ot-sun" style="right: 95px; color: #dc2626;"><?php echo $s['ot_sun'] ?: ''; ?></td>
                                    <td class="fix-r sum-ot-hol" style="right: 55px; color: #dc2626;"><?php echo $s['ot_hol'] ?: ''; ?></td>
                                    <td class="fix-r sum-total" style="right: 0; font-weight:700; color:var(--primary-color); border-left: 2px solid #cbd5e1;"><?php echo $s['total']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footnote for Cross-Project OT -->
            <?php if (!empty($cross_project_notes)): ?>
                <div class="card" style="margin-top: 20px; border-left: 4px solid #3b82f6;">
                    <h3 style="font-size: 1rem; color: #1e40af; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Ghi chú chi tiết tăng ca liên dự án</h3>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.9rem; color: #334155;">
                        <?php foreach($cross_project_notes as $note): ?>
                            <li style="margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px dashed #e2e8f0;">
                                <strong>Ngày <?php echo $note['date']; ?>:</strong> 
                                NV <strong><?php echo $note['emp']; ?></strong> 
                                tăng ca <strong><?php echo $note['ot']; ?>h</strong> 
                                tại dự án <span class="badge badge-info"><?php echo $note['proj']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

<style>
/* CSS RESET FOR STABILITY */
.attendance-table { border-collapse: separate; border-spacing: 0; width: max-content; margin: 0; height: auto !important; }
.attendance-table th, .attendance-table td { border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 0; box-sizing: border-box; vertical-align: middle; }
.attendance-table tbody tr { height: 42px !important; }
.attendance-table tbody tr td { height: 42px !important; }
.attendance-table thead th { position: sticky; top: 0; background-color: #f8fafc; z-index: 800; }
.attendance-table thead tr:nth-child(2) th { top: 30px; }
.attendance-table thead tr:nth-child(3) th { top: 55px; }
.fix-l { position: sticky; z-index: 900 !important; background-color: #fff; }
.attendance-table thead .fix-l { z-index: 1000 !important; background-color: #f8fafc; }
.fix-r { position: sticky; z-index: 900 !important; background-color: #fff; text-align: center; }
.attendance-table thead .fix-r { z-index: 1000 !important; background-color: #f8fafc; }
.is-sunday { background-color: #fef9c3 !important; }
.attendance-table tbody tr:nth-child(even) td.is-sunday { background-color: #fef08a !important; }

.shadow-left { box-shadow: -3px 0 5px -2px rgba(0,0,0,0.1); }
.att-input { width: 100%; border: none; text-align: center; background: transparent; display: block; cursor: pointer; font-family: 'Inter', sans-serif; outline: none; }
.att-input.symbol { font-weight: 800; text-transform: uppercase; height: 24px; font-size: 0.9rem; border-bottom: 1px solid #e2e8f0; }
.att-input.symbol[value="X"] { color: #166534; } .att-input.symbol[value="P"] { color: #1e40af; }
.att-input.symbol[value="L,T"], .att-input.symbol[value="L"], .att-input.symbol[value="T"] { color: #991b1b; }
.att-input.symbol[value="OF"] { color: #64748b; font-weight: 400; }
.att-input.ot { font-size: 0.75rem; color: #c2410c; font-weight: 600; height: 18px; }
.att-input:focus { background-color: rgba(0,0,0,0.05); }
.att-input.changed { background-color: #fef3c7 !important; border-radius: 2px; }

/* Cross Project Indicator */
.att-input.ot.has-cross-proj {
    background-color: #dbeafe; /* Blue-100 */
    color: #1e40af;
    font-weight: 700;
    border-radius: 2px;
}
.locked-cell-view.has-cross-proj {
    background-color: #dbeafe;
}

/* Locked View Styles */
.locked-cell-view {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 42px;
}
.locked-cell-view .sym {
    font-weight: 800;
    font-size: 0.9rem;
    line-height: 1.2;
}
.locked-cell-view .ot {
    font-size: 0.7rem;
    color: #c2410c;
    font-weight: 600;
}

.att-input.changed { background-color: #fef3c7 !important; border-radius: 2px; }

/* Fullscreen Styles */
.btn-fullscreen {
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--primary-color);
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}
.btn-fullscreen:hover { background: rgba(36, 162, 92, 0.1); }

.card.is-fullscreen {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 9999 !important;
    margin: 0 !important;
    border-radius: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    background: #fff !important;
}

#attendance-card:fullscreen {
    width: 100vw !important;
    height: 100vh !important;
    display: flex !important;
    flex-direction: column !important;
    background: #fff !important;
    margin: 0 !important;
}

.card.is-fullscreen .table-container,
#attendance-card:fullscreen .table-container {
    flex: 1 1 auto !important;
    width: 100% !important;
    height: 100% !important;
    max-height: none !important;
    overflow: auto !important;
    display: block !important;
}

.card.is-fullscreen .attendance-table,
#attendance-card:fullscreen .attendance-table {
    min-height: 100% !important;
}

#fullscreen-hint {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: #fff;
    padding: 10px 20px;
    border-radius: 30px;
    z-index: 10000;
    font-size: 0.9rem;
    display: none;
    pointer-events: none;
}

.attendance-table tbody tr:nth-child(even) td { background-color: #f1f5f9 !important; }
.attendance-table tbody tr:nth-child(even) .fix-l, 
.attendance-table tbody tr:nth-child(even) .fix-r { background-color: #f1f5f9 !important; }
.attendance-table tbody tr:hover td { background-color: #e2e8f0 !important; }
.attendance-table tbody tr:hover .fix-l, 
.attendance-table tbody tr:hover .fix-r { background-color: #e2e8f0 !important; }

/* DARK MODE */
body.dark-mode .attendance-table thead th, body.dark-mode .fix-l, body.dark-mode .fix-r { background-color: #1e293b !important; color: #94a3b8; border-color: #334155; }        
body.dark-mode .is-sunday { background-color: rgba(234, 179, 8, 0.15) !important; }
body.dark-mode .attendance-table tbody tr:nth-child(even) td.is-sunday { background-color: rgba(234, 179, 8, 0.25) !important; }
body.dark-mode .attendance-table td { background-color: #1e293b; border-color: #334155; }
body.dark-mode .att-input.symbol { color: #cbd5e1; } body.dark-mode .att-input.symbol[value="X"] { color: #4ade80; }
body.dark-mode .att-input.symbol[value="P"] { color: #60a5fa; }
body.dark-mode .att-input.symbol[value="L,T"], body.dark-mode .att-input.symbol[value="L"], body.dark-mode .att-input.symbol[value="T"] { color: #f87171; }
body.dark-mode .att-input.ot { color: #fb923c; }
body.dark-mode .attendance-table tbody tr:nth-child(even) td,
body.dark-mode .attendance-table tbody tr:nth-child(even) .fix-l,
body.dark-mode .attendance-table tbody tr:nth-child(even) .fix-r { background-color: #1e293b !important; }
body.dark-mode .attendance-table tbody tr:nth-child(odd) td,
body.dark-mode .attendance-table tbody tr:nth-child(odd) .fix-l,
body.dark-mode .attendance-table tbody tr:nth-child(odd) .fix-r { background-color: #0f172a !important; }
body.dark-mode .attendance-table tbody tr:hover td,
body.dark-mode .attendance-table tbody tr:hover .fix-l,
body.dark-mode .attendance-table tbody tr:hover .fix-r { background-color: #334155 !important; }

/* Dropdown Menu */
.dropdown-menu { background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
.dropdown-item:hover { background: #f1f5f9; color: var(--primary-color) !important; }

</style>

<?php include '../../../includes/footer.php'; ?>

<script>
// Context Menu Logic
let selectedOtInput = null;

function showProjectMenu(e, input) {
    e.preventDefault();
    selectedOtInput = input;
    const menu = document.getElementById('projectContextMenu');
    menu.style.display = 'block';
    menu.style.left = e.pageX + 'px';
    menu.style.top = e.pageY + 'px';
}

function assignProject(projId) {
    if (!selectedOtInput) return;
    
    const currentProjId = parseInt($(selectedOtInput).attr('data-target-proj-id')) || 0;
    if (currentProjId !== projId) {
        $(selectedOtInput).attr('data-target-proj-id', projId);
        $(selectedOtInput).addClass('changed');
        if (projId > 0) {
            $(selectedOtInput).addClass('has-cross-proj');
        } else {
            $(selectedOtInput).removeClass('has-cross-proj');
        }
    }
    
    document.getElementById('projectContextMenu').style.display = 'none';
}

// Hide menu when clicking elsewhere
document.addEventListener('click', function(e) {
    const menu = document.getElementById('projectContextMenu');
    if (menu.style.display === 'block') {
        menu.style.display = 'none';
    }
});

function toggleFullScreen() {
    const card = document.getElementById('attendance-card');
    if (!document.fullscreenElement) {
        if (card.requestFullscreen) {
            card.requestFullscreen();
        } else if (card.webkitRequestFullscreen) {
            card.webkitRequestFullscreen();
        } else if (card.msRequestFullscreen) {
            card.msRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

document.addEventListener('fullscreenchange', function() {
    const card = document.getElementById('attendance-card');
    const hint = document.getElementById('fullscreen-hint');
    if (document.fullscreenElement) {
        $(card).addClass('is-fullscreen');
        $(hint).fadeIn().delay(3000).fadeOut();
    } else {
        $(card).removeClass('is-fullscreen');
        $(hint).hide();
    }
});

let changedData = {}; 
$(document).ready(function() {
    $('.att-input').each(function() { $(this).data('original', $(this).val()); });
    // Also store original target proj id
    $('.att-input.ot').each(function() { 
        $(this).data('original-target', $(this).attr('data-target-proj-id')); 
    });
    
    $('tr[data-emp-id]').each(function() { calculateRow($(this)); });
});
$(document).on('change', '.att-input', function() {
    let currentVal = $(this).val(); let originalVal = $(this).data('original');
    let tr = $(this).closest('tr'); let day = $(this).data('day');
    
    // Check target proj change as well
    let isChanged = false;
    if (currentVal !== originalVal) isChanged = true;
    
    if ($(this).hasClass('ot')) {
        let currentTarget = $(this).attr('data-target-proj-id');
        let originalTarget = $(this).data('original-target');
        if (currentTarget != originalTarget) isChanged = true;
    }

    if (isChanged) { $(this).addClass('changed'); } else { $(this).removeClass('changed'); }
    calculateRow(tr);
});
$(document).on('keydown', '.att-input', function(e) {
    if (e.keyCode === 46 || e.keyCode === 8) { if ($(this).val() !== '') { $(this).val('').trigger('change'); } }
});
function calculateRow(tr) {
    let s = {p_cd:0, other:0, holiday:0, ot_norm:0, ot_sun:0, ot_hol:0, total:0};
    tr.find('.symbol').each(function() {
        let d = $(this).data('day'); 
        // Lấy giá trị: nếu là input dùng val(), nếu là div dùng text()
        let sym = ($(this).is('input') ? $(this).val() : $(this).text()).toUpperCase().trim();
        
        let otElem = tr.find(`.ot[data-day="${d}"]`);
        let ot = parseFloat(otElem.is('input') ? otElem.val() : otElem.text()) || 0;
        
        let isSun = $(this).data('is-sunday') == '1';
        if (['X', 'ĐH', 'DH'].includes(sym)) { s.total += 1; }
        else if (['1/2', '1/P', '1/CĐ', '1/CD'].includes(sym)) { s.total += 0.5; }
        if (['P', 'CĐ', 'CD'].includes(sym)) { s.p_cd += 1; }
        else if (['1/P', '1/CĐ', '1/CD'].includes(sym)) { s.p_cd += 0.5; }
        else if (['Ô', 'O', 'TS', 'R', 'VR', 'CO', 'NB'].includes(sym)) { s.other += 1; }
        else if (['L', 'T', 'L,T', 'L, T'].includes(sym)) { s.holiday += 1; }
        if (ot > 0) {
            if (['F', 'L', 'T', 'L,T', 'L, T'].includes(sym)) s.ot_hol += ot;
            else if (['F1'].includes(sym) || isSun) s.ot_sun += ot;
            else s.ot_norm += ot;
        }
    });
    tr.find('.sum-p-cd').text(s.p_cd || ''); tr.find('.sum-other').text(s.other || ''); tr.find('.sum-holiday').text(s.holiday || '');
    tr.find('.sum-ot-normal').text(s.ot_norm || ''); tr.find('.sum-ot-sun').text(s.ot_sun || ''); tr.find('.sum-ot-hol').text(s.ot_hol || '');
    tr.find('.sum-total').text(s.total || '0');
}
function toggleSymbol(input) { let $i = $(input); let cur = $i.val().toUpperCase(); $i.val((cur===''||cur=='OF')?'X':'').trigger('change'); }

function confirmLock() {
    let action = $('input[name="toggle_lock"]').val();
    let msg = action === 'lock' ? 'Bạn có chắc chắn muốn KHÓA bảng chấm công này? Sau khi khóa, các dự án sẽ không thể chỉnh sửa dữ liệu.' : 'Bạn có chắc chắn muốn MỞ KHÓA bảng chấm công này?';
    Modal.confirm(msg, () => {
        $('#lockForm').submit();
    });
}

function saveAttendance() {
    let payload = [];
    $('tr[data-emp-id]').each(function() {
        let tr = $(this); let empId = tr.data('emp-id');
        tr.find('.att-input.symbol').each(function() {
            let day = $(this).data('day');
            let symbolInput = $(this); let otInput = tr.find(`.ot[data-day="${day}"]`);
            
            let symVal = symbolInput.val(); 
            let otVal = otInput.val();
            let targetProjId = otInput.attr('data-target-proj-id') || 0;

            let symOrg = symbolInput.data('original'); 
            let otOrg = otInput.data('original');
            let targetOrg = otInput.data('original-target') || 0;

            // Check if ANY value changed
            if (symVal !== symOrg || otVal !== otOrg || targetProjId != targetOrg) { 
                payload.push({ 
                    emp_id: empId, 
                    day: day, 
                    symbol: symVal, 
                    ot: otVal || 0,
                    target_project_id: targetProjId 
                }); 
            }
        });
    });
    if (payload.length === 0) return Toast.info('Không có thay đổi nào để lưu.');
    let $btn = $('button[onclick="saveAttendance()"]'); $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
    fetch('save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ month: <?php echo $month; ?>, year: <?php echo $year; ?>, project_id: <?php echo $project_id; ?>, changes: payload })
    }).then(r => r.json()).then(data => { 
        if (data.status === 'success') { 
            Toast.success(data.message); 
            $('.att-input.changed').removeClass('changed'); 
            $('.att-input').each(function() { $(this).data('original', $(this).val()); }); 
            $('.att-input.ot').each(function() { $(this).data('original-target', $(this).attr('data-target-proj-id')); }); 
            // Reload page to show footnotes updated? No, just keep simple for now or ask user to reload.
            // Actually, for footnotes to appear, a reload is best or dynamic DOM. 
            // For now, let's keep it simple.
        } else { Toast.error(data.message); }
    }).catch(err => { Toast.error('Lỗi kết nối máy chủ.'); console.error(err); }).finally(() => { $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Lưu dữ liệu'); });
}
let isDragging = false; let startCell = null; let selectionRange = [];
$(document).on('dragstart', '.att-input', function(e) { e.preventDefault(); return false; });
$(document).on('mousedown', '.att-input', function(e) {
    if (e.button !== 0) return;
    isDragging = true; startCell = $(this); selectionRange = [$(this)];
    if ($(this).hasClass('symbol')) dragType = 'symbol'; else if ($(this).hasClass('ot')) dragType = 'ot';
    $('.att-input.drag-selected').removeClass('drag-selected'); $('.att-input.selected-cell').removeClass('selected-cell'); 
    $(this).addClass('selected-cell'); $(this).focus();
});
$(document).on('mousemove', 'td', function(e) {
    if (!isDragging || !startCell) return;
    if (e.buttons !== 1) { isDragging = false; return; }
    let input = $(this).find('.' + dragType);
    if (input.length === 0) return;
    let startRow = startCell.closest('tr')[0]; let currentRow = $(this).closest('tr')[0];
    if (startRow === currentRow) {
        $('.att-input.drag-selected').removeClass('drag-selected');
        let startIdx = startCell.parent().index(); let currentIdx = $(this).index();
        let minIdx = Math.min(startIdx, currentIdx); let maxIdx = Math.max(startIdx, currentIdx);
        let tr = $(startRow);
        tr.find('td').slice(minIdx, maxIdx + 1).each(function() {
            let item = $(this).find('.' + dragType);
            if (item.length) { item.addClass('drag-selected'); selectionRange.push(item); }
        });
    }
});
$(document).on('mouseup', function() {
    if (isDragging && startCell && selectionRange.length > 1) {
        let valToCopy = startCell.val();
        if (valToCopy !== '') {
            let tr = startCell.closest('tr'); let affected = false;
            selectionRange.forEach(function(el) { if (el[0] !== startCell[0]) { el.val(valToCopy).trigger('change'); affected = true; } });
            if (affected) calculateRow(tr);
        }
    }
    isDragging = false;
});
$(document).on('keydown', function(e) {
    if ((e.keyCode === 46 || e.keyCode === 8) && selectionRange.length > 0) {
        e.preventDefault();
        let tr = selectionRange[0].closest('tr'); let affected = false;
        selectionRange.forEach(function(el) { if (el.val() !== '') { el.val('').trigger('change'); affected = true; } });
        if (affected) calculateRow(tr);
        $('.att-input.drag-selected').removeClass('drag-selected'); $('.att-input.selected-cell').removeClass('selected-cell'); selectionRange = [];
    }
});
$(document).on('click', function(e) {
    if (!$(e.target).closest('.attendance-table').length) {
        $('.att-input.drag-selected').removeClass('drag-selected'); $('.att-input.selected-cell').removeClass('selected-cell'); selectionRange = [];
    }
});
$('head').append('<style>.drag-selected { background-color: #60a5fa !important; color: #fff !important; outline: 2px solid #2563eb !important; z-index: 9999 !important; position: relative; box-shadow: 0 0 5px rgba(37, 99, 235, 0.5); } .selected-cell { outline: 2px solid #2563eb !important; z-index: 9999; position: relative; }</style>');
</script>