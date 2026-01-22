<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id <= 0) die("Vui lòng chọn dự án để in phiếu lương.");

$project = db_fetch_row("SELECT * FROM projects WHERE id = ?", [$project_id]);

$payroll_data = db_fetch_all("
    SELECT p.*, e.fullname, e.code, e.position, d.name as dept_name, s.basic_salary, s.insurance_salary
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN employee_salaries s ON e.id = s.employee_id
    WHERE p.month = ? AND p.year = ? AND e.current_project_id = ?
    ORDER BY e.fullname ASC
", [$month, $year, $project_id]);

if (empty($payroll_data)) die("Chưa có dữ liệu lương của tháng này cho dự án.");

$pairs = array_chunk($payroll_data, 2);
$standard_days_dynamic = get_standard_working_days($month, $year);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Phiếu Lương - <?php echo $project['name']; ?></title>
    <style>
        @page { size: A5 landscape; margin: 0; }
        body { font-family: "Times New Roman", serif; font-size: 10px; margin: 0; padding: 0; background: #fff; color: #000; }
        
        .a5-page {
            display: flex;
            width: 210mm;
            height: 148mm;
            page-break-after: always;
            position: relative;
            box-sizing: border-box;
        }
        .a5-page:last-child { page-break-after: avoid; }
        
        .cut-line {
            position: absolute;
            left: 50%;
            top: 10mm;
            bottom: 10mm;
            border-left: 1px dashed #000;
            z-index: 100;
        }

        .payslip-half {
            width: 50%;
            padding: 3mm 5mm;
            box-sizing: border-box;
        }
        
        .payslip-container { 
            width: 100%; 
            border: 1.5px solid #000; 
            padding: 1px; 
            box-sizing: border-box;
        }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px 6px; line-height: 1.2; color: #000; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .bg-subtle { background-color: #f2f2f2; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="background: #fdf6e3; padding: 10px; text-align: center; border-bottom: 1px solid #eee;">
        <button onclick="window.print()" style="padding: 10px 30px; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 4px; font-weight: bold;">
            <i class="fas fa-print"></i> NHẤN ĐỂ IN PHIẾU LƯƠNG (MÀU ĐEN)
        </button>
    </div>

    <?php foreach ($pairs as $pair): ?>
    <div class="a5-page">
        <div class="cut-line"></div>
        
        <?php foreach ($pair as $p): 
            $current_std_days = $p['standard_days'] ?: $standard_days_dynamic;
            $day_rate = $p['basic_salary'] / $current_std_days;
            $salary_holiday_work = $p['holiday_work_days'] * ($day_rate * 3);
            $salary_holiday_paid = $p['holiday_paid_days'] * $day_rate;
            $salary_paid_leave = $p['paid_leave_days'] * $day_rate;
            
            $unpaid_att = db_fetch_row("SELECT COUNT(*) as c FROM attendance WHERE employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ? AND UPPER(timekeeper_symbol) IN ('Ô', 'TS', 'VR')", [$p['employee_id'], $month, $year]);
            $sick_leave_days = (int)($unpaid_att['c'] ?? 0);

            $total_income = $p['salary_actual'] + $salary_paid_leave + $salary_holiday_paid + $salary_holiday_work + $p['ot_amount'] + $p['total_allowance'] + $p['bonus_amount'];
            $total_deduction = $p['bhxh_amount'] + $p['salary_advances'] + $p['union_fee'] + $p['income_tax'];
        ?>
        <div class="payslip-half">
            <div class="payslip-container">
                <table>
                    <thead>
                        <tr class="bg-subtle">
                            <th colspan="3" class="text-center uppercase py-2" style="font-size: 10px;">
                                PHIẾU LƯƠNG CB, NV CC <?php echo $project['name']; ?> T<?php echo $month; ?>/<?php echo $year; ?>
                            </th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-center" style="font-size: 16px; padding: 8px 0; border-bottom: none;">
                                <?php echo $p['fullname']; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">Phòng ban - Chức vụ:</td>
                            <td colspan="2" class="text-center"><?php echo $p['dept_name']; ?> - <?php echo $p['position'] ?: 'Nhân viên'; ?></td>
                        </tr>
                        <tr>
                            <td class="font-bold">Lương khoán/tháng</td>
                            <td colspan="2" class="text-right font-bold"><?php echo number_format($p['basic_salary']); ?></td>
                        </tr>
                        <tr>
                            <td>Công chuẩn trong tháng</td>
                            <td colspan="2" class="text-right"><?php echo $current_std_days; ?></td>
                        </tr>
                        <tr>
                            <td>Số ngày nghỉ ốm/việc riêng/thai sản:</td>
                            <td colspan="2" class="text-right"><?php echo $sick_leave_days; ?></td>
                        </tr>

                        <tr class="bg-subtle font-bold">
                            <td style="width: 60%;">I. Các khoản thu nhập thực tế:</td>
                            <td class="text-center" style="width: 20%;">Công/Giờ</td>
                            <td class="text-center" style="width: 20%;">Lương</td>
                        </tr>
                        <tr>
                            <td>1. Lương của công làm việc thực tế (không tính lễ, nghỉ tuần)</td>
                            <td class="text-center"><?php echo $p['total_work_days']; ?></td>
                            <td class="text-right"><?php echo number_format($p['salary_actual']); ?></td>
                        </tr>
                        <tr>
                            <td>2. Lương làm ngày lễ, tết:</td>
                            <td class="text-center"><?php echo $p['holiday_work_days'] ?: '-'; ?></td>
                            <td class="text-right"><?php echo number_format($salary_holiday_work); ?></td>
                        </tr>
                        <tr>
                            <td>3. Lương nghỉ ngày lễ, tết:</td>
                            <td class="text-center"><?php echo $p['holiday_paid_days'] ?: '0'; ?></td>
                            <td class="text-right"><?php echo number_format($salary_holiday_paid); ?></td>
                        </tr>
                        <tr>
                            <td>4. Lương Phép/Chế độ Cty chi trả</td>
                            <td class="text-center"><?php echo $p['paid_leave_days'] ?: '0'; ?></td>
                            <td class="text-right"><?php echo number_format($salary_paid_leave); ?></td>
                        </tr>
                        <tr>
                            <td>5. Phụ cấp:</td>
                            <td class="text-center">-</td>
                            <td class="text-right"><?php echo number_format($p['total_allowance']); ?></td>
                        </tr>
                        <tr>
                            <td>6. Lương làm thêm giờ:</td>
                            <td class="text-center"><?php echo $p['total_ot_hours'] ?: '0'; ?></td>
                            <td class="text-right"><?php echo number_format($p['ot_amount']); ?></td>
                        </tr>
                        <tr>
                            <td>7. Bồi dưỡng trực tết:</td>
                            <td class="text-center">-</td>
                            <td class="text-right"><?php echo number_format($p['bonus_amount']); ?></td>
                        </tr>
                        <tr class="font-bold">
                            <td colspan="2" class="text-center">Tổng thu nhập:</td>
                            <td class="text-right"><?php echo number_format($total_income); ?></td>
                        </tr>

                        <tr class="bg-subtle font-bold">
                            <td colspan="2">II. Các khoản giảm trừ:</td>
                            <td class="text-right"><?php echo number_format($total_deduction); ?></td>
                        </tr>
                        <tr>
                            <td rowspan="3" style="vertical-align: top;">
                                1. Bảo hiểm xã hội: Người lao động đóng 10,5% trên mức lương tham gia đóng BHXH.
                            </td>
                            <td style="font-style: italic; font-size: 9px;" class="text-center">BHXH 8%</td>
                            <td class="text-right"><?php echo number_format($p['insurance_salary'] * 0.08); ?></td>
                        </tr>
                        <tr>
                            <td style="font-style: italic; font-size: 9px;" class="text-center">BHYT 1,5%</td>
                            <td class="text-right"><?php echo number_format($p['insurance_salary'] * 0.015); ?></td>
                        </tr>
                        <tr>
                            <td style="font-style: italic; font-size: 9px;" class="text-center">BHTN: 1%</td>
                            <td class="text-right"><?php echo number_format($p['insurance_salary'] * 0.01); ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">2. Tiền tạm ứng:</td>
                            <td class="text-right"><?php echo number_format($p['salary_advances']); ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">3. Đoàn phí:</td>
                            <td class="text-right"><?php echo number_format($p['union_fee']); ?></td>
                        </tr>
                        <tr>
                            <td colspan="2">4. Thuế TNCN:</td>
                            <td class="text-right"><?php echo number_format($p['income_tax']); ?></td>
                        </tr>

                        <tr class="font-bold" style="background: #000; color: #fff;">
                            <td colspan="2" class="py-2" style="font-weight: normal; font-size: 11px;">III. Thực lĩnh = Tổng thu nhập - các khoản giảm trừ</td>
                            <td class="text-right" style="font-size: 14px; font-weight: bold;"><?php echo number_format($p['net_salary']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</body>
</html>
