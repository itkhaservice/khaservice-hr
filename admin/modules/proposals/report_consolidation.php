<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

require_permission('manage_system');

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$proj_id = isset($_GET['proj_id']) ? (int)$_GET['proj_id'] : 0;
$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;

$where = "WHERE p.month = ? AND p.year = ? AND p.status IN ('approved', 'purchasing', 'completed')";
$params = [$month, $year];

if ($proj_id > 0) {
    $where .= " AND p.project_id = ?";
    $params[] = $proj_id;
}
if ($cat_id > 0) {
    $where .= " AND i.category_id = ?";
    $params[] = $cat_id;
}

// 1. Lấy dữ liệu vật tư tổng hợp
$summary = db_fetch_all(
    "SELECT 
        i.item_name, 
        i.unit, 
        c.name as category_name,
        SUM(i.quantity_appr) as total_qty,
        AVG(i.price_final) as avg_price
    FROM material_proposal_items i
    JOIN material_proposals p ON i.proposal_id = p.id
    LEFT JOIN supply_categories c ON i.category_id = c.id
    $where
    GROUP BY i.item_name, i.unit, c.name
    ORDER BY c.name ASC, i.item_name ASC
", $params);

// 2. Lấy dữ liệu ghi chú dự án
$notes_data = db_fetch_all(
    "SELECT DISTINCT pr.name as project_name, p.notes
    FROM material_proposals p
    JOIN projects pr ON p.project_id = pr.id
    JOIN material_proposal_items i ON p.id = i.proposal_id
    $where AND p.notes IS NOT NULL AND TRIM(p.notes) != ''
    ORDER BY pr.name ASC
", $params);

$grand_total = 0;
foreach ($summary as $s) {
    $grand_total += ($s['total_qty'] * $s['avg_price']);
}

$projects = db_fetch_all("SELECT id, name FROM projects WHERE status = 'active' ORDER BY name ASC");
$categories = db_fetch_all("SELECT id, name FROM supply_categories ORDER BY name ASC");

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header no-print">
            <h1 class="page-title">Tổng hợp Mua hàng: Tháng <?php echo "$month/$year"; ?></h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                <button onclick="window.print()" class="btn btn-info"><i class="fas fa-print"></i> In báo cáo</button>
            </div>
        </div>

        <form method="GET" class="filter-section no-print">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%;">
                <select name="month" class="form-control" style="flex: 0 0 100px;">
                    <?php for($m=1; $m<=12; $m++): ?><option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>Tháng <?php echo $m; ?></option><?php endfor; ?>
                </select>
                <select name="year" class="form-control" style="flex: 0 0 120px;">
                    <?php for($y=date('Y')-1; $y<=date('Y')+1; $y++): ?><option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>Năm <?php echo $y; ?></option><?php endfor; ?>
                </select>
                <select name="proj_id" class="form-control" style="flex: 1; min-width: 180px;">
                    <option value="0">-- Tất cả Dự án --</option>
                    <?php foreach ($projects as $p): ?><option value="<?php echo $p['id']; ?>" <?php echo $proj_id == $p['id'] ? 'selected' : ''; ?>><?php echo $p['name']; ?></option><?php endforeach; ?>
                </select>
                <select name="cat_id" class="form-control" style="flex: 1; min-width: 180px;">
                    <option value="0">-- Tất cả Loại vật tư --</option>
                    <?php foreach ($categories as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo $cat_id == $c['id'] ? 'selected' : ''; ?>><?php echo $c['name']; ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            </div>
        </form>

        <!-- Web View -->
        <div class="card no-print">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin:0;">Bảng kê tổng hợp vật tư cần mua</h3>
                <div style="font-weight: 800; font-size: 1.1rem; color: var(--primary-color);">TỔNG DỰ TOÁN: <?php echo number_format($grand_total); ?> đ</div>
            </div>

            <div class="table-container" style="border: none;">
                <table class="table">
                    <thead>
                        <tr><th width="50">STT</th><th>Tên vật tư</th><th>ĐVT</th><th class="text-center">Tổng SL</th><th class="text-right">Giá</th><th class="text-right">Thành tiền</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($summary)): ?>
                            <tr><td colspan="6" class="text-center" style="padding: 40px; color: #94a3b8;">Không có dữ liệu đề xuất phù hợp.</td></tr>
                        <?php else: ?>
                            <?php $stt = 1; foreach ($summary as $s): ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td><strong><?php echo $s['item_name']; ?></strong><br><small class="text-sub"><?php echo $s['category_name']; ?></small></td>
                                    <td><?php echo $s['unit']; ?></td>
                                    <td class="text-center" style="font-weight:700; color:var(--primary-color);"><?php echo (float)$s['total_qty']; ?></td>
                                    <td class="text-right"><?php echo number_format($s['avg_price']); ?></td>
                                    <td class="text-right" style="font-weight:700;"><?php echo number_format($s['total_qty'] * $s['avg_price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($notes_data)): ?>
            <div style="margin-top: 25px; border: 1px solid #dbeafe; border-radius: 8px; overflow: hidden;">
                <div id="toggleNotes" style="padding: 12px 20px; background: #f0f9ff; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                    <div style="font-weight: 700; color: #1e40af;">
                        <i class="fas fa-info-circle"></i> GHI CHÚ TỪ CÁC DỰ ÁN 
                        <span class="badge" style="background: #3b82f6; color: #fff; margin-left: 5px;"><?php echo count($notes_data); ?></span>
                    </div>
                    <div style="color: #3b82f6; font-size: 0.9rem;">
                        <span id="toggleText">Xem chi tiết</span> <i class="fas fa-chevron-down" id="toggleIcon"></i>
                    </div>
                </div>
                <div id="notesContent" style="display: none; padding: 20px; background: #fff; border-top: 1px solid #dbeafe;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 15px;">
                        <?php foreach ($notes_data as $nd): ?>
                            <div style="background: #f8fafc; padding: 12px 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                <strong style="color: #0369a1; display: block; margin-bottom: 5px;">+ <?php echo $nd['project_name']; ?>:</strong>
                                <div style="color: #475569; font-size: 0.85rem; font-style: italic; line-height: 1.5;"><?php echo nl2br($nd['notes']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ================= START PRINT TEMPLATE ================= -->
        <div class="print-content">
            <div class="print-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <div style="text-align: left; line-height: 1.2;">
                    <div style="font-size: 16pt; font-weight: bold; text-transform: uppercase;">KhaService</div>
                    <div style="font-size: 10pt; font-style: italic;">Building - Management - Service</div>
                </div>
                <div style="text-align: center; line-height: 1.3;">
                    <div style="font-size: 12pt; font-weight: bold;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                    <div style="font-size: 12pt; font-weight: bold;">Độc lập - Tự do - Hạnh phúc</div>
                    <div style="margin-top: 5px; font-weight: bold;">-------***-------</div>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0 20px;">
                <div style="font-size: 16pt; font-weight: bold;">BẢNG KÊ TỔNG HỢP VẬT TƯ CẦN MUA <?php echo sprintf('%02d', $month) . "/" . $year; ?></div>
            </div>

            <!-- Table aligned with view.php style and optimized widths -->
            <table class="print-table" style="width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 12pt;">
                <thead>
                    <tr>
                        <th style="border: 1px solid black; padding: 8px; width: 40px; text-align: center;">STT</th>
                        <th style="border: 1px solid black; padding: 8px; text-align: center;">Tên vật tư</th>
                        <th style="border: 1px solid black; padding: 8px; width: 70px; text-align: center;">ĐVT</th>
                        <th style="border: 1px solid black; padding: 8px; width: 50px; text-align: center;">SL</th>
                        <th style="border: 1px solid black; padding: 8px; width: 90px; text-align: center;">Đơn giá</th>
                        <th style="border: 1px solid black; padding: 8px; width: 110px; text-align: center;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summary)): ?>
                        <tr><td colspan="6" style="border: 1px solid black; padding: 20px; text-align: center;">Không có dữ liệu đề xuất phù hợp.</td></tr>
                    <?php else: 
                        $stt = 1;
                        foreach ($summary as $s): 
                            $row_total = $s['total_qty'] * $s['avg_price'];
                    ?>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; text-align: center;"><?php echo $stt++; ?></td>
                            <td style="border: 1px solid black; padding: 8px; text-align: left;"><?php echo $s['item_name']; ?></td>
                            <td style="border: 1px solid black; padding: 8px; text-align: center;"><?php echo $s['unit']; ?></td>
                            <td style="border: 1px solid black; padding: 8px; text-align: center;"><?php echo (float)$s['total_qty']; ?></td>
                            <td style="border: 1px solid black; padding: 8px; text-align: right;"><?php echo number_format($s['avg_price']); ?></td>
                            <td style="border: 1px solid black; padding: 8px; text-align: right; font-weight: bold;"><?php echo number_format($row_total); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="border: 1px solid black; padding: 10px; text-align: center; font-weight: bold;">TỔNG DỰ TOÁN MUA HÀNG</td>
                        <td style="border: 1px solid black; padding: 10px; text-align: right; font-weight: bold; font-size: 12pt;"><?php echo number_format($grand_total); ?></td>
                    </tr>
                </tfoot>
            </table>

            <?php if (!empty($notes_data)): ?>
            <div style="margin-top: 20px; line-height: 1.6; font-size: 11pt;">
                <strong>Ghi chú:</strong>
                <?php foreach ($notes_data as $nd): ?>
                    <div style="margin-top: 8px; padding-left: 5px;">
                        <strong>+ <?php echo $nd['project_name']; ?>:</strong>
                        <?php 
                            $lines = explode("\n", str_replace("\r", "", trim($nd['notes'])));
                            foreach($lines as $l): 
                                $line_text = trim($l);
                                if(empty($line_text)) continue;
                                $prefix = (strpos($line_text, '-') === 0) ? '' : '- ';
                        ?>
                            <div style="padding-left: 20px;"><?php echo $prefix . $line_text; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#toggleNotes').on('click', function() {
        $('#notesContent').slideToggle(300, function() {
            const isVisible = $(this).is(':visible');
            $('#toggleText').text(isVisible ? 'Thu nhỏ' : 'Xem chi tiết');
            $('#toggleIcon').toggleClass('fa-chevron-down fa-chevron-up');
        });
    });
});
</script>

<style>
    .print-content { display: none; }
    #toggleNotes:hover { background: #e0f2fe !important; }
    
    @media print {
        @page { size: A4 portrait; margin: 2cm 2cm 2cm 3cm; }
        body { background: #fff !important; font-family: "Times New Roman", Times, serif !important; font-size: 12pt !important; color: #000 !important; }
        .no-print, .sidebar, .main-header, .main-footer, .action-header, .filter-section, #toast-container, .alert, .card { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .content-wrapper { padding: 0 !important; }
        .wrapper { display: block !important; }
        .print-content { display: block !important; width: 100%; }
        .print-table { border-collapse: collapse !important; width: 100% !important; }
        .print-table th, .print-table td { border: 1px solid black !important; padding: 6px !important; }
        .print-table th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
        strong, b { font-weight: bold !important; }
    }
</style>
                            </div>
<?php include '../../../includes/footer.php'; ?>
