<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id <= 0) die("Vui lòng chọn dự án để in bảng công.");

// Fetch Company Info
$settings = [];
$raw_settings = db_fetch_all("SELECT setting_key, setting_value FROM settings");
foreach ($raw_settings as $s) $settings[$s['setting_key']] = $s['setting_value'];

// Fetch Project Info
$project = db_fetch_row("SELECT * FROM projects WHERE id = ?", [$project_id]);

// Fetch Employees
$employees = db_fetch_all("SELECT e.id, e.fullname, e.code, e.position, d.name as dept_name, p.name as pos_name 
                           FROM employees e 
                           LEFT JOIN departments d ON e.department_id = d.id 
                           LEFT JOIN positions p ON e.position_id = p.id
                           WHERE e.current_project_id = ? AND e.status = 'working' 
                           ORDER BY d.stt ASC, p.stt ASC, e.fullname ASC", [$project_id]);

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$att_data = [];
if (!empty($employees)) {
    $start_date = sprintf("%04d-%02d-01", $year, $month);
    $end_date = sprintf("%04d-%02d-%02d", $year, $month, $days_in_month);
    $emp_ids = array_column($employees, 'id');
    $raw_att = db_fetch_all("SELECT employee_id, DAY(date) as day, timekeeper_symbol, overtime_hours FROM attendance WHERE date BETWEEN ? AND ? AND employee_id IN (".implode(',',$emp_ids).")", [$start_date, $end_date]);
    foreach ($raw_att as $r) $att_data[$r['employee_id']][$r['day']] = ['symbol' => $r['timekeeper_symbol'], 'ot' => $r['overtime_hours']];
}

// Manual Pagination Settings
$rows_per_page = 15; // Number of employees per page
$chunks = array_chunk($employees, $rows_per_page);
$total_data_pages = count($chunks);

// Logic: If last page of data has > 8 people, signatures MUST go to a new page
$last_chunk_count = $total_data_pages > 0 ? count($chunks[$total_data_pages - 1]) : 0;
$need_extra_page_for_sig = ($last_chunk_count > 8); 

$total_pages = $total_data_pages;
if ($need_extra_page_for_sig) $total_pages++;
if ($total_pages == 0) $total_pages = 1;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Bảng Chấm Công - <?php echo "$month/$year - " . ($project['name'] ?? ''); ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 5mm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .page-container {
            width: 100%;
            height: 185mm;
            position: relative;
            page-break-after: always;
            box-sizing: border-box;
            overflow: hidden;
        }
        .page-container:last-child {
            page-break-after: avoid !important;
        }
        
        /* Table styles */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            border: 1px solid #000;
        }
        .attendance-table th, .attendance-table td {
            border: 1px solid #000;
            padding: 4px 2px;
            text-align: center;
        }
        .attendance-table thead th {
            background-color: #f2f2f2 !important;
            -webkit-print-color-adjust: exact;
        }
        .text-left { text-align: left !important; padding-left: 5px !important; }
        .is-sunday { background-color: #eee !important; -webkit-print-color-adjust: exact; }
        
        /* Header & Footer */
        .header-table { width: 100%; margin-bottom: 10px; }
        .company-name { font-weight: bold; text-transform: uppercase; font-size: 11pt; }
        .report-title { text-align: center; margin-bottom: 10px; }
        .report-title h2 { margin: 0; text-transform: uppercase; font-size: 14pt; }
        
        .page-footer-info {
            position: absolute;
            bottom: 0;
            right: 0;
            font-size: 9pt;
            font-style: italic;
        }
        
        /* Signature Area */
        .signature-table {
            width: 100%;
            margin-top: 15px;
            text-align: center;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 25%;
            vertical-align: top;
            border: none !important;
        }
        .signature-space { height: 160px; }

        .legend-container {
            border: 1px solid #000;
            padding: 6px;
            margin-top: 5px;
        }
        .legend-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2px 10px;
            font-size: 7.5pt;
        }

        @media print {
            .no-print { display: none; }
            .page-container { page-break-after: always; }
            .page-container:last-child { page-break-after: avoid !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="background: #fdf6e3; padding: 10px; text-align: center; border-bottom: 1px solid #eee;">
        <button onclick="window.print()" style="padding: 8px 20px; cursor: pointer; background: #24a25c; color: #fff; border: none; border-radius: 4px;">
            <i class="fas fa-print"></i> NHẤN ĐỂ IN BẢNG CÔNG
        </button>
    </div>

    <?php 
    $signatures_printed = false;
    foreach ($chunks as $page_index => $page_employees): 
        $current_page = $page_index + 1;
    ?>
    <div class="page-container">
        <!-- Header (Only on page 1) -->
        <?php if ($current_page == 1): ?>
            <table class="header-table">
                <tr>
                    <td style="width: 50%;">
                        <div class="company-name"><?php echo $settings['company_name']; ?></div>
                        <div style="font-size: 8pt;"><?php echo $settings['company_address']; ?></div>
                    </td>
                    <td style="text-align: right; vertical-align: top; font-size: 9pt;">
                        Dự án/Bộ phận: <strong><?php echo $project['name']; ?></strong>
                    </td>
                </tr>
            </table>

            <div class="report-title">
                <h2>BẢNG CHẤM CÔNG NHÂN VIÊN</h2>
                <div style="font-style: italic; font-size: 10pt;">Tháng <?php echo $month; ?> năm <?php echo $year; ?></div>
            </div>
        <?php else: ?>
            <div style="height: 10px;"></div>
        <?php endif; ?>

        <!-- Table Content -->
        <table class="attendance-table">
            <thead>
                <tr>
                    <th rowspan="2" width="30">STT</th>
                    <th rowspan="2" width="180">Họ và tên</th>
                    <th colspan="<?php echo $days_in_month; ?>">Ngày trong tháng</th>
                    <th colspan="3">Ngày nghỉ</th>
                    <th colspan="3">Tăng ca</th>
                    <th rowspan="2" width="40">Tổng</th>
                </tr>
                <tr>
                    <?php for($d=1; $d<=$days_in_month; $d++): 
                        $ts = strtotime("$year-$month-$d"); $dow = date('N', $ts);
                    ?>
                        <th width="20" class="<?php echo $dow==7?'is-sunday':''; ?>"><?php echo $d; ?></th>
                    <?php endfor; ?>
                    <th width="20">P</th><th width="20">K</th><th width="20">L</th>
                    <th width="20">TC</th><th width="20">CN</th><th width="20">L</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $row_stt = $page_index * $rows_per_page; 
                foreach($page_employees as $emp): 
                    $row_stt++; 
                    $s = ['p_cd'=>0, 'other'=>0, 'holiday'=>0, 'ot_norm'=>0, 'ot_sun'=>0, 'ot_hol'=>0, 'total'=>0];
                ?>
                <tr>
                    <td><?php echo $row_stt; ?></td>
                    <td class="text-left">
                        <div style="font-weight: bold; font-size: 9pt;"><?php echo $emp['fullname']; ?></div>
                        <div style="font-size: 7.5pt; font-style: italic;"><?php echo $emp['dept_name']; ?> - <?php echo $emp['pos_name'] ?: $emp['position']; ?></div>
                    </td>
                    <?php for($d=1; $d<=$days_in_month; $d++): 
                        $cell = $att_data[$emp['id']][$d] ?? ['symbol'=>'','ot'=>0]; 
                        $sym = strtoupper($cell['symbol']); $ot = (float)$cell['ot'];
                        $is_sun = (date('N', strtotime("$year-$month-$d")) == 7);
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
                    ?>
                        <td class="<?php echo $is_sun?'is-sunday':''; ?>">
                            <div style="font-weight: bold;"><?php echo $sym; ?></div>
                            <div style="font-size: 7pt; color: #d00;"><?php echo $ot > 0 ? $ot : ''; ?></div>
                        </td>
                    <?php endfor; ?>
                    <td><?php echo $s['p_cd'] ?: ''; ?></td><td><?php echo $s['other'] ?: ''; ?></td><td><?php echo $s['holiday'] ?: ''; ?></td>
                    <td><?php echo $s['ot_norm'] ?: ''; ?></td><td><?php echo $s['ot_sun'] ?: ''; ?></td><td><?php echo $s['ot_hol'] ?: ''; ?></td>
                    <td style="font-weight: bold;"><?php echo $s['total']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Signature Block (Only if last data page AND fits) -->
        <?php if ($page_index === $total_data_pages - 1 && !$need_extra_page_for_sig): ?>
            <div class="footer-content">
                <div style="margin-top: 8px; font-weight: bold; font-size: 8pt;">* Ghi chú ký hiệu công:</div>
                <div class="legend-container">
                    <div class="legend-grid">
                        <div class="legend-item"><strong>X</strong>: Hưởng lương</div><div class="legend-item"><strong>P</strong>: Nghỉ phép</div><div class="legend-item"><strong>Ô</strong>: Ốm</div><div class="legend-item"><strong>VR</strong>: Việc riêng</div>
                        <div class="legend-item"><strong>CĐ</strong>: Nghỉ chế độ</div><div class="legend-item"><strong>ĐH</strong>: Đi học/họp</div><div class="legend-item"><strong>Ts</strong>: Thai sản</div><div class="legend-item"><strong>L,T</strong>: Nghỉ lễ, tết</div>
                        <div class="legend-item"><strong>1/2</strong>: Nửa ngày</div><div class="legend-item"><strong>1/p</strong>: Nửa phép, làm</div><div class="legend-item"><strong>F</strong>: Làm ngày lễ</div><div class="legend-item"><strong>F1</strong>: Làm ngày CN</div>
                        <div class="legend-item"><strong>1/F1</strong>: Nửa ngày CN</div><div class="legend-item"><strong>1/lt</strong>: Nửa lễ, tết</div><div class="legend-item"><strong>Nb</strong>: Nghỉ bù</div><div class="legend-item"><strong>OF</strong>: Nghỉ tuần</div>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 8px; padding-right: 50px; font-size: 9pt;">Ngày lập biểu: <?php echo date('d/m/Y'); ?></div>
                <table class="signature-table">
                    <tr>
                        <td><strong>NGƯỜI LẬP BIỂU</strong><br>(Ký, ghi rõ họ tên)<div class="signature-space"></div></td>
                        <td><strong>TRƯỞNG BỘ PHẬN</strong><br>(Ký, ghi rõ họ tên)<div class="signature-space"></div></td>
                        <td><strong>PHÒNG NHÂN SỰ</strong><br>(Ký, ghi rõ họ tên)<div class="signature-space"></div></td>
                        <td><strong>GIÁM ĐỐC</strong><br>(Ký, đóng dấu)<div class="signature-space"></div></td>
                    </tr>
                </table>
            </div>
            <?php $signatures_printed = true; ?>
        <?php endif; ?>

        <div class="page-footer-info">Trang <?php echo $current_page; ?>/<?php echo $total_pages; ?></div>
    </div>
    <?php endforeach; ?>

    <!-- Signature Extra Page -->
    <?php if ($need_extra_page_for_sig && !$signatures_printed): 
        $current_page = $total_pages;
    ?>
    <div class="page-container">
        <div style="height: 20px;"></div>
        <div class="footer-content">
            <div style="margin-bottom: 8px; font-weight: bold; font-size: 9pt;">* Ghi chú ký hiệu công:</div>
            <div class="legend-container">
                <div class="legend-grid">
                    <div class="legend-item"><strong>X</strong>: Hưởng lương</div><div class="legend-item"><strong>P</strong>: Nghỉ phép</div><div class="legend-item"><strong>Ô</strong>: Ốm</div><div class="legend-item"><strong>VR</strong>: Việc riêng</div>
                    <div class="legend-item"><strong>CĐ</strong>: Nghỉ chế độ</div><div class="legend-item"><strong>ĐH</strong>: Đi học/họp</div><div class="legend-item"><strong>Ts</strong>: Thai sản</div><div class="legend-item"><strong>L,T</strong>: Nghỉ lễ, tết</div>
                    <div class="legend-item"><strong>1/2</strong>: Nửa ngày</div><div class="legend-item"><strong>1/p</strong>: Nửa phép, làm</div><div class="legend-item"><strong>F</strong>: Làm ngày lễ</div><div class="legend-item"><strong>F1</strong>: Làm ngày CN</div>
                    <div class="legend-item"><strong>1/F1</strong>: Nửa ngày CN</div><div class="legend-item"><strong>1/lt</strong>: Nửa lễ, tết</div><div class="legend-item"><strong>Nb</strong>: Nghỉ bù</div><div class="legend-item"><strong>OF</strong>: Nghỉ tuần</div>
                </div>
            </div>
            <div style="text-align: right; margin-top: 20px; padding-right: 50px; font-size: 10pt;">Ngày lập biểu: <?php echo date('d/m/Y'); ?></div>
            <table class="signature-table">
                <tr>
                    <td><strong>NGƯỜI LẬP BIỂU</strong><br>(Ký, ghi rõ họ tên)<div class="signature-space"></div></td>
                    <td><strong>TRƯỞNG BỘ PHẬN</strong><br>(Ký, ghi rõ họ tên)<div class="signature-space"></div></td>
                    <td><strong>PHÒNG NHÂN SỰ</strong><br>(Ký, ghi rõ họ tên)<div class="signature-space"></div></td>
                    <td><strong>GIÁM ĐỐC</strong><br>(Ký, đóng dấu)<div class="signature-space"></div></td>
                </tr>
            </table>
        </div>
        <div class="page-footer-info">Trang <?php echo $current_page; ?>/<?php echo $total_pages; ?></div>
    </div>
    <?php endif; ?>
</body>
</html>