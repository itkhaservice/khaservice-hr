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

if ($project_id == 0 && !empty($project_options)) $project_id = $project_options[0]['id'];

$is_locked = db_fetch_row("SELECT is_locked FROM attendance_locks WHERE month = ? AND year = ? AND (project_id = 0 OR project_id = ?) AND is_locked = 1", [$month, $year, $project_id]) ? true : false;

$employees = ($project_id > 0) ? db_fetch_all("SELECT e.id, e.fullname, e.code, e.position, d.name as dept_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.current_project_id = ? AND e.status = 'working' ORDER BY e.fullname ASC", [$project_id]) : [];

$att_data = [];
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
if ($project_id > 0 && !empty($employees)) {
    $start_date = "$year-$month-01"; $end_date = "$year-$month-$days_in_month";
    $emp_ids = array_column($employees, 'id');
    if (!empty($emp_ids)) {
        $raw_att = db_fetch_all("SELECT employee_id, DAY(date) as day, timekeeper_symbol, overtime_hours FROM attendance WHERE date BETWEEN ? AND ? AND employee_id IN (".implode(',',$emp_ids).")", [$start_date, $end_date]);
        foreach ($raw_att as $r) $att_data[$r['employee_id']][$r['day']] = ['symbol' => $r['timekeeper_symbol'], 'ot' => $r['overtime_hours']];
    }
}

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>
    <div class="content-wrapper" style="overflow: hidden; display: flex; flex-direction: column; height: calc(100vh - 65px);">
        <div class="action-header" style="flex-shrink: 0;">
            <div>
                <h1 class="page-title">Bảng Chấm Công - <?php echo "$month/$year"; ?> <span style="font-size:0.5em; color:#ccc;">(v4.0 Stable)</span></h1>
                <span class="badge <?php echo $is_locked?'badge-danger':'badge-success'; ?>"><i class="fas <?php echo $is_locked?'fa-lock':'fa-pen'; ?>"></i> <?php echo $is_locked?'Đã Khóa':'Đang mở'; ?></span>
            </div>
            <div class="header-actions">
                <form method="GET" style="display: flex; gap: 10px;">
                    <select name="project_id" class="form-control" style="min-width: 200px;" onchange="this.form.submit()">
                        <?php foreach($project_options as $p) echo "<option value='{$p['id']}' ".($p['id']==$project_id?'selected':'').">{$p['name']}</option>"; ?>
                    </select>
                    <select name="month" class="form-control" style="width: 80px;" onchange="this.form.submit()"><?php for($i=1;$i<=12;$i++) echo "<option value='$i' ".($i==$month?'selected':'').">T$i</option>"; ?></select>
                    <select name="year" class="form-control" style="width: 100px;" onchange="this.form.submit()"><?php for($y=2023;$y<=2030;$y++) echo "<option value='$y' ".($y==$year?'selected':'').">$y</option>"; ?></select>
                </form>
                <?php if (!$is_locked): ?>
                    <button type="button" class="btn btn-primary" onclick="saveAttendance()"><i class="fas fa-save"></i> Lưu</button>
                    <a href="import.php" class="btn btn-secondary"><i class="fas fa-file-import"></i> Import</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($project_id == 0 || empty($employees)): ?>
            <div class="alert alert-info">Vui lòng chọn dự án có nhân viên để chấm công.</div>
        <?php else: ?>
            <div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border-color); flex: 1; display: flex; flex-direction: column;">
                <!-- Legend -->
                <div style="padding: 8px 15px; border-bottom: 1px solid var(--border-color); background: var(--bg-main); font-size: 0.8rem; flex-shrink: 0;">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                        <span><b>Ký hiệu:</b></span>
                        <span style="color:#24a25c; font-weight:700;">X</span>: Đi làm &nbsp;
                        <span style="color:#3b82f6; font-weight:700;">P</span>: Phép &nbsp;
                        <span style="background:#64748b; color:#fff; font-weight:700; padding:0 3px;">OF</span>: Nghỉ tuần &nbsp;
                        <span style="color:#ef4444; font-weight:700;">L,T</span>: Lễ tết
                        <a href="javascript:void(0)" onclick="$('#moreLegend').slideToggle();" style="margin-left: auto;"><i class="fas fa-info-circle"></i> Thêm</a>
                    </div>
                    <div id="moreLegend" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border-color);">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; font-size: 0.8rem;">
                            <span><b>1/2</b>: Làm nửa ngày</span>
                            <span><b>1/p</b>: Nửa phép, nửa làm</span>
                            <span><b>Ô</b>: Nghỉ ốm</span>
                            <span><b>R</b>: Nghỉ việc riêng</span>
                            <span><b>CĐ</b>: Chế độ (Hiếu, hỉ...)</span>
                            <span><b>ĐH</b>: Đi học/Họp</span>
                            <span><b>Ts</b>: Thai sản</span>
                            <span><b>Nb</b>: Nghỉ bù</span>
                            <span><b>F</b>: Làm lễ, tết</span>
                            <span><b>F1</b>: Làm chủ nhật/ngày nghỉ</span>
                            <span><b>1/F1</b>: Làm nửa ngày CN/nghỉ</span>
                            <span><b>1/lt</b>: Làm nửa ngày lễ/tết</span>
                        </div>
                        <div style="margin-top: 10px; color: var(--text-sub); font-style: italic;">
                            * <b>Ô dưới:</b> Nhập số giờ làm thêm (tăng ca). Nhấp đúp vào ô ký hiệu để đánh dấu nhanh 'X'.
                        </div>
                    </div>
                </div>

                <div class="table-container" style="flex: 1; overflow: auto; position: relative; width: 100%;">
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
                                <th rowspan="2" class="fix-r" style="right: 255px; width: 40px; font-size: 0.7rem; border-left: 2px solid #cbd5e1;">P/CĐ</th>
                                <th rowspan="2" class="fix-r" style="right: 215px; width: 40px; font-size: 0.7rem;">Khác</th>
                                <th rowspan="2" class="fix-r" style="right: 175px; width: 40px; font-size: 0.7rem;">Lễ</th>
                                <th rowspan="2" class="fix-r" style="right: 135px; width: 40px; font-size: 0.7rem; border-left: 1px solid #cbd5e1;">TC</th>
                                <th rowspan="2" class="fix-r" style="right: 95px; width: 40px; font-size: 0.7rem;">CN</th>
                                <th rowspan="2" class="fix-r" style="right: 55px; width: 40px; font-size: 0.7rem;">Lễ</th>
                            </tr>
                            <tr style="height: 25px;">
                                <?php for($d=1; $d<=$days_in_month; $d++): 
                                    $ts = strtotime("$year-$month-$d"); $dow = date('N', $ts);
                                    echo "<th class='text-center ".($dow==7?'is-sunday':'')."' style='font-size:0.7rem; font-weight:400;'>" . ['','T2','T3','T4','T5','T6','T7','CN'][$dow] . "</th>";
                                endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $stt = 0; foreach($employees as $emp): $stt++; $s = ['p_cd'=>0, 'other'=>0, 'holiday'=>0, 'ot_norm'=>0, 'ot_sun'=>0, 'ot_hol'=>0, 'total'=>0]; ?>
                                <tr data-emp-id="<?php echo $emp['id']; ?>">
                                    <td class="fix-l" style="left: 0; font-weight: 600; background: #fff; text-align: center;"><?php echo $stt; ?></td>
                                    <td class="fix-l" style="left: 50px; border-right: 2px solid #cbd5e1; background: #fff; padding: 4px 8px;">
                                        <div style="color: var(--primary-dark); font-weight: 700; font-size: 0.85rem;"><?php echo $emp['fullname']; ?></div>
                                        <div style="font-size: 0.65rem; color: #64748b;"><?php echo $emp['dept_name'] ?? '-'; ?> - <?php echo $emp['position']; ?></div>
                                    </td>
                                    <?php for($d=1; $d<=$days_in_month; $d++): 
                                        $cell = $att_data[$emp['id']][$d] ?? ['symbol'=>'','ot'=>0]; $sym = strtoupper($cell['symbol']); $ot = (float)$cell['ot'];
                                        $is_sun = (date('N', strtotime("$year-$month-$d")) == 7);
                                        if (in_array($sym, ['X','L','T','L,T'])) $s['total'] += 1; elseif ($sym == '1/2') $s['total'] += 0.5;
                                        elseif ($sym == 'P' || $sym == 'CĐ') { $s['p_cd']++; $s['total'] += 1; } elseif ($sym == '1/P') { $s['p_cd'] += 0.5; $s['total'] += 1; }
                                        elseif (in_array($sym, ['Ô', 'TS', 'R', 'VR'])) $s['other']++;
                                        if ($ot > 0) { if ($sym == 'F' || $sym == 'L,T') $s['ot_hol'] += $ot; elseif ($sym == 'F1' || $is_sun) $s['ot_sun'] += $ot; else $s['ot_norm'] += $ot; }
                                    ?>
                                        <td class="<?php echo $is_sun?'is-sunday':''; ?>">
                                            <?php if($is_locked): ?>
                                                <div class="text-center"><b><?php echo $sym; ?></b><br><small><?php echo $ot?:''; ?></small></div>
                                            <?php else: ?>
                                                <input type="text" class="att-input symbol" data-day="<?php echo $d; ?>" data-is-sunday="<?php echo $is_sun?'1':'0'; ?>" value="<?php echo $sym; ?>" maxlength="4" ondblclick="toggleSymbol(this)" onfocus="this.select()">
                                                <input type="number" class="att-input ot" data-day="<?php echo $d; ?>" value="<?php echo $ot?:''; ?>" min="0" step="0.5" onfocus="this.select()">
                                            <?php endif; ?>
                                        </td>
                                    <?php endfor; ?>
                                    <td class="fix-r sum-p-cd" style="right: 255px; border-left: 2px solid #cbd5e1; background: #fff;"><?php echo $s['p_cd'] ?: ''; ?></td>
                                    <td class="fix-r sum-other" style="right: 215px; background: #fff;"><?php echo $s['other'] ?: ''; ?></td>
                                    <td class="fix-r sum-holiday" style="right: 175px; background: #fff;"><?php echo $s['holiday'] ?: ''; ?></td>
                                    <td class="fix-r sum-ot-normal" style="right: 135px; border-left: 1px solid #cbd5e1; color: #dc2626; background: #fff;"><?php echo $s['ot_norm'] ?: ''; ?></td>
                                    <td class="fix-r sum-ot-sun" style="right: 95px; color: #dc2626; background: #fff;"><?php echo $s['ot_sun'] ?: ''; ?></td>
                                    <td class="fix-r sum-ot-hol" style="right: 55px; color: #dc2626; background: #fff;"><?php echo $s['ot_hol'] ?: ''; ?></td>
                                    <td class="fix-r sum-total" style="right: 0; font-weight:700; color:var(--primary-color); border-left: 2px solid #cbd5e1; background: #fff;"><?php echo $s['total']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* CSS RESET FOR STABILITY */
.attendance-table { border-collapse: separate; border-spacing: 0; width: max-content; margin: 0; }
.attendance-table th, .attendance-table td { 
    border-right: 1px solid #e2e8f0; 
    border-bottom: 1px solid #e2e8f0; 
    padding: 0; 
    box-sizing: border-box; 
    vertical-align: middle; /* Căn giữa trên dưới */
}

/* HEADER STICKY */
.attendance-table thead th { position: sticky; top: 0; background-color: #f8fafc; z-index: 800; } /* High z-index for top header */
.attendance-table thead tr:nth-child(2) th { top: 30px; }
.attendance-table thead tr:nth-child(3) th { top: 55px; }

/* LEFT STICKY */
.fix-l { position: sticky; z-index: 900 !important; background-color: #fff; } /* Body Left > Standard Body (0) */
.attendance-table thead .fix-l { z-index: 1000 !important; background-color: #f8fafc; } /* Header Left > Header Standard (800) */

/* RIGHT STICKY */
.fix-r { position: sticky; z-index: 900 !important; background-color: #fff; text-align: center; } /* Body Right > Standard Body */
.attendance-table thead .fix-r { z-index: 1000 !important; background-color: #f8fafc; } /* Header Right > Header Standard */

/* UTILS */
.is-sunday { background-color: #dcfce7 !important; }
.att-input { width: 100%; border: none; text-align: center; background: transparent; display: block; cursor: pointer; }
.att-input.symbol { font-weight: 700; text-transform: uppercase; color: var(--text-main); height: 24px; }
.att-input.ot { font-size: 0.7rem; color: #dc2626; height: 18px; }
.att-input:focus { background-color: #e0f2fe; outline: none; }
.att-input.changed { background-color: #fffbeb !important; }

/* DARK MODE */
body.dark-mode .attendance-table thead th, 
body.dark-mode .fix-l, body.dark-mode .fix-r { background-color: #1e293b !important; color: #94a3b8; border-color: #334155; }
body.dark-mode .attendance-table td { background-color: #1e293b; border-color: #334155; }
body.dark-mode .is-sunday { background-color: rgba(22, 101, 52, 0.2) !important; }
</style>

<script>
let changedData = {}; 
$(document).on('change', '.att-input', function() {
    $(this).addClass('changed');
    let tr = $(this).closest('tr'); let day = $(this).data('day');
    changedData[`${tr.data('emp-id')}_${day}`] = { emp_id: tr.data('emp-id'), day: day, symbol: tr.find(`.symbol[data-day="${day}"]`).val(), ot: tr.find(`.ot[data-day="${day}"]`).val() || 0 };
    calculateRow(tr);
});
function calculateRow(tr) {
    let s = {p_cd:0, other:0, holiday:0, ot_norm:0, ot_sun:0, ot_hol:0, total:0};
    tr.find('.symbol').each(function() {
        let d = $(this).data('day'); let sym = $(this).val().toUpperCase().trim();
        let ot = parseFloat(tr.find(`.ot[data-day="${d}"]`).val()) || 0;
        let isSun = $(this).data('is-sunday') == '1';
        if (['X','L','T','L,T'].includes(sym)) s.total += 1; else if (sym == '1/2') s.total += 0.5;
        else if (sym == 'P' || sym == 'CĐ') { s.p_cd++; s.total += 1; } else if (sym == '1/P') { s.p_cd += 0.5; s.total += 1; }
        else if (['Ô', 'TS', 'R', 'VR'].includes(sym)) s.other++;
        if (ot > 0) { if (sym == 'F' || sym == 'L,T') s.ot_hol += ot; elseif (sym == 'F1' || isSun) s.ot_sun += ot; else s.ot_norm += ot; }
    });
    tr.find('.sum-p-cd').text(s.p_cd || ''); tr.find('.sum-other').text(s.other || ''); tr.find('.sum-holiday').text(s.holiday || '');
    tr.find('.sum-ot-normal').text(s.ot_norm || ''); tr.find('.sum-ot-sun').text(s.ot_sun || ''); tr.find('.sum-ot-hol').text(s.ot_hol || '');
    tr.find('.sum-total').text(s.total || '0');
}
function toggleSymbol(input) { let $i = $(input); let cur = $i.val().toUpperCase(); $i.val((cur===''||cur=='OF')?'X':'').trigger('change'); }
function saveAttendance() {
    let changes = Object.values(changedData); if (!changes.length) return showToast('info', 'Không có thay đổi.');
    let $btn = $('button[onclick="saveAttendance()"]'); $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    fetch('save.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ month: <?php echo $month; ?>, year: <?php echo $year; ?>, project_id: <?php echo $project_id; ?>, changes: changes })
    }).then(r => r.json()).then(data => { if (data.status === 'success') { showToast('success', data.message); $('.att-input.changed').removeClass('changed'); changedData = {}; } else showToast('error', data.message);
    }).finally(() => $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Lưu dữ liệu'));
}
</script>
