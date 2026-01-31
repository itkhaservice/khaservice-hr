<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$proposal = db_fetch_row("
    SELECT p.*, pr.name as project_name, pr.budget_limit, e.fullname as creator_name, d.name as dept_name
    FROM material_proposals p
    JOIN projects pr ON p.project_id = pr.id
    LEFT JOIN employees e ON p.created_by = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE p.id = ?
", [$id]);

if (!$proposal) redirect('index.php');

$is_admin = has_permission('manage_system');
$is_owner = ($proposal['created_by'] == $_SESSION['user_id']);

if (isset($_POST['cancel_proposal']) && $is_owner && $proposal['status'] == 'pending') {
    db_query("UPDATE material_proposals SET status = 'cancelled' WHERE id = ?", [$id]);
    set_toast('success', 'Đã hủy đề xuất.');
    redirect("view.php?id=$id");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_proposal'])) {
    require_permission('manage_system'); 
    $new_status = $_POST['status'];
    $total_final = 0;
    if (isset($_POST['item_id']) && is_array($_POST['item_id'])) {
        foreach ($_POST['item_id'] as $i => $item_id) {
            $qty_appr = (float)$_POST['qty_appr'][$i];
            $price_final = (float)$_POST['price_final'][$i];
            $delivery = $_POST['delivery_status'][$i];
            db_query("UPDATE material_proposal_items SET quantity_appr = ?, price_final = ?, delivery_status = ?, delivered_at = ? WHERE id = ?", 
                     [$qty_appr, $price_final, $delivery, $delivery == 'delivered' ? date('Y-m-d H:i:s') : null, $item_id]);
            $total_final += ($qty_appr * $price_final);
        }
    }
    db_query("UPDATE material_proposals SET status = ?, total_amount_final = ? WHERE id = ?", [$new_status, $total_final, $id]);
    set_toast('success', 'Cập nhật đề xuất thành công!');
    redirect("view.php?id=$id");
}

$items = db_fetch_all("
    SELECT i.*, c.name as category_name 
    FROM material_proposal_items i 
    LEFT JOIN supply_categories c ON i.category_id = c.id 
    WHERE i.proposal_id = ?
    ORDER BY c.id ASC, i.item_name ASC
", [$id]);

// Tính toán tổng cộng trước để dùng cho Header
$calc_total = 0;
foreach($items as $it) {
    $qty = (float)($it['quantity_appr'] > 0 ? $it['quantity_appr'] : $it['quantity_req']);
    $price = (float)($it['price_final'] > 0 ? $it['price_final'] : $it['price_est']);
    $calc_total += ($qty * $price);
}
$display_total = $proposal['total_amount_final'] > 0 ? $proposal['total_amount_final'] : $calc_total;

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <form method="POST">
            <div class="action-header no-print">
                <div>
                    <h1 class="page-title">Chi tiết Đề xuất: <?php echo $proposal['code']; ?></h1>
                    <p style="color: var(--text-sub); margin-top: 5px;">Dự án: <strong><?php echo $proposal['project_name']; ?></strong></p>
                </div>
                <div class="header-actions">
                    <button type="button" onclick="window.print()" class="btn btn-info"><i class="fas fa-print"></i> In Phiếu</button>
                    <?php if ($is_owner && $proposal['status'] == 'pending'): ?>
                        <button type="submit" name="cancel_proposal" class="btn btn-danger" onclick="return confirm('Hủy gửi đề xuất này?')"><i class="fas fa-ban"></i> Hủy gửi</button>
                    <?php endif; ?>
                    <?php if ($is_admin): ?>
                        <button type="submit" name="update_proposal" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật & Duyệt</button>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary">Quay lại</a>
                </div>
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

                <div style="text-align: center; margin: 30px 0 20px;"><div style="font-size: 16pt; font-weight: bold;">PHIẾU ĐỀ XUẤT</div></div>

                <div style="margin-bottom: 20px; font-weight: bold; font-style: italic;">Kính gửi: Ban Tổng giám đốc Công ty Cổ phần Quản lý và Vận hành Cao ốc Khánh Hội.</div>

                <div style="line-height: 1.8; margin-bottom: 15px;">
                    <div>- Tôi tên: <?php echo $proposal['proposer_name']; ?></div>
                    <div>- Bộ phận: <?php echo $proposal['project_name']; ?></div>
                    <div>- Nội dung đề xuất: <?php echo nl2br($proposal['title']); ?></div>
                </div>

                <table class="print-table" style="width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 12pt;">
                    <thead>
                        <tr>
                            <th style="border: 1px solid black; padding: 8px; width: 40px; text-align: center;">STT</th>
                            <th style="border: 1px solid black; padding: 8px; text-align: center;">Tên vật tư / Quy cách</th>
                            <th style="border: 1px solid black; padding: 8px; width: 70px; text-align: center;">ĐVT</th>
                            <th style="border: 1px solid black; padding: 8px; width: 50px; text-align: center;">SL</th>
                            <th style="border: 1px solid black; padding: 8px; width: 90px; text-align: center;">Đơn giá</th>
                            <th style="border: 1px solid black; padding: 8px; width: 110px; text-align: center;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stt = 1;
                        foreach ($items as $it): 
                            $qty = (float)($it['quantity_appr'] > 0 ? $it['quantity_appr'] : $it['quantity_req']);
                            $price = (float)($it['price_final'] > 0 ? $it['price_final'] : $it['price_est']);
                            $row_t = $qty * $price;
                        ?>
                            <tr>
                                <td style="border: 1px solid black; padding: 8px; text-align: center;"><?php echo $stt++; ?></td>
                                <td style="border: 1px solid black; padding: 8px; text-align: left;"><?php echo $it['item_name']; ?></td>
                                <td style="border: 1px solid black; padding: 8px; text-align: center;"><?php echo $it['unit']; ?></td>
                                <td style="border: 1px solid black; padding: 8px; text-align: center;"><?php echo $qty; ?></td>
                                <td style="border: 1px solid black; padding: 8px; text-align: right;"><?php echo number_format($price); ?></td>
                                <td style="border: 1px solid black; padding: 8px; text-align: right;"><?php echo number_format($row_t); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="border: 1px solid black; padding: 8px; text-align: center; font-weight: bold;">TỔNG</td>
                            <td style="border: 1px solid black; padding: 8px; text-align: right; font-weight: bold;"><?php echo number_format($display_total); ?></td>
                        </tr>
                    </tfoot>
                </table>

                <div style="margin-top: 10px; margin-bottom: 25px; line-height: 1.6;">
                    <strong>Ghi chú:</strong>
                    <div style="padding-left: 10px;"><?php echo !empty(trim($proposal['notes'])) ? nl2br($proposal['notes']) : '................................................................................................................................................................................................................'; ?></div>
                </div>

                <div style="text-align: right; margin-bottom: 20px; font-style: italic;">
                    TP. HCM, Ngày <?php echo date('d', strtotime($proposal['created_at'])); ?> tháng <?php echo date('m', strtotime($proposal['created_at'])); ?> năm <?php echo date('Y', strtotime($proposal['created_at'])); ?>
                </div>

                <table style="width: 100%; border: none; text-align: center; line-height: 1.4;">
                    <tr>
                        <td style="width: 33.33%; vertical-align: top;"><strong>Người lập phiếu</strong><div style="height: 80px;"></div></td>
                        <td style="width: 33.33%; vertical-align: top;"><strong>Trưởng Bộ phận</strong><div style="height: 80px;"></div></td>
                        <td style="width: 33.33%; vertical-align: top;"><strong>Ban Tổng giám đốc</strong><div style="height: 80px;"></div></td>
                    </tr>
                </table>
            </div>
            <!-- ================= END PRINT TEMPLATE ================= -->

            <!-- Web View Layout -->
            <div class="no-print">
                <div class="card" style="margin-top: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div>
                            <table class="info-table">
                                <tr><td width="150" style="color:#64748b;">Mã phiếu:</td><td><strong><?php echo $proposal['code']; ?></strong></td></tr>
                                <tr><td style="color:#64748b;">Người đề xuất:</td><td><?php echo $proposal['proposer_name']; ?></td></tr>
                                <tr><td style="color:#64748b;">Ngày tạo:</td><td><?php echo date('d/m/Y H:i', strtotime($proposal['created_at'])); ?></td></tr>
                            </table>
                        </div>
                        <div>
                            <table class="info-table">
                                <tr>
                                    <td width="150" style="color:#64748b;">Trạng thái:</td>
                                    <td>
                                        <?php if ($is_admin): ?>
                                            <select name="status" class="form-control" style="width: auto; padding: 5px;">
                                                <?php $statuses = ['draft'=>'Nháp', 'pending'=>'Chờ duyệt', 'approved'=>'Đã duyệt', 'purchasing'=>'Đang mua', 'completed'=>'Hoàn thành', 'rejected'=>'Từ chối', 'cancelled'=>'Đã hủy'];
                                                foreach($statuses as $k=>$v) { $sel = $proposal['status'] == $k ? 'selected' : ''; echo "<option value='$k' $sel>$v</option>"; } ?>
                                            </select>
                                        <?php else: ?>
                                            <span class="badge badge-info"><?php echo strtoupper($proposal['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr><td style="color:#64748b;">Định mức dự án:</td><td><strong><?php echo number_format($proposal['budget_limit']); ?> đ</strong></td></tr>
                            </table>
                        </div>
                    </div>
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #e2e8f0;">
                        <strong style="display:block; margin-bottom: 5px;">Nội dung đề xuất:</strong>
                        <div style="color: #475569; font-style: italic;"><?php echo !empty(trim($proposal['title'])) ? nl2br($proposal['title']) : '(Trống)'; ?></div>
                        <strong style="display:block; margin-top: 10px; margin-bottom: 5px;">Ghi chú thêm:</strong>
                        <div style="color: #475569; font-style: italic;"><?php echo !empty(trim($proposal['notes'])) ? nl2br($proposal['notes']) : '(Trống)'; ?></div>
                    </div>
                </div>

                <div class="card" style="margin-top: 20px; padding: 0; overflow: hidden;">
                    <div style="padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 1rem;">Danh sách chi tiết vật tư</h3>
                        <div style="font-weight: 800; font-size: 1.1rem; color: var(--primary-color);">
                            TỔNG CỘNG DUYỆT: <?php echo number_format($display_total); ?> đ
                        </div>
                    </div>
                    <div class="table-container" style="border: none; border-radius: 0;">
                        <table class="table">
                            <thead>
                                <tr><th width="50" style="text-align: center;">STT</th><th>Tên vật tư</th><th width="80" style="text-align: center;">ĐVT</th><th width="100" style="text-align: center;">SL ĐX</th><th width="100" style="text-align: center;">SL Duyệt</th><th width="120" style="text-align: right;">Giá duyệt</th><th width="120" style="text-align: right;">Thành tiền</th><th width="140" style="text-align: center;">Giao hàng</th></tr>
                            </thead>
                            <tbody>
                                <?php $stt = 1; foreach ($items as $it): 
                                    $qty_v = (float)($it['quantity_appr'] > 0 ? $it['quantity_appr'] : $it['quantity_req']);
                                    $price_v = (float)($it['price_final'] > 0 ? $it['price_final'] : $it['price_est']); ?>
                                    <input type="hidden" name="item_id[]" value="<?php echo $it['id']; ?>">
                                    <tr><td style="text-align: center;"><?php echo $stt++; ?></td><td><div style="font-weight: 600;"><?php echo $it['item_name']; ?></div><small><?php echo $it['category_name']; ?></small></td><td style="text-align: center;"><?php echo $it['unit']; ?></td><td style="text-align: center;"><?php echo (float)$it['quantity_req']; ?></td>
                                        <td style="text-align: center;"><?php if ($is_admin): ?><input type="number" name="qty_appr[]" class="form-control" value="<?php echo $qty_v; ?>" step="0.1" style="text-align: center; padding: 5px;"><?php else: ?><strong><?php echo $qty_v; ?></strong><?php endif; ?></td>
                                        <td style="text-align: right;"><?php if ($is_admin): ?><input type="number" name="price_final[]" class="form-control" value="<?php echo $price_v; ?>" style="text-align: right; padding: 5px;"><?php else: ?><?php echo number_format($price_v); ?><?php endif; ?></td>
                                        <td style="text-align: right; font-weight: 700; color: var(--primary-color);"><?php echo number_format($qty_v * $price_v); ?></td>
                                        <td style="text-align: center;"><?php if ($is_admin): ?><select name="delivery_status[]" class="form-control" style="font-size: 0.8rem; padding: 5px;"><option value="pending" <?php echo $it['delivery_status'] == 'pending' ? 'selected' : ''; ?>>Chờ hàng</option><option value="ready" <?php echo $it['delivery_status'] == 'ready' ? 'selected' : ''; ?>>Có hàng tại VP</option><option value="delivered" <?php echo $it['delivery_status'] == 'delivered' ? 'selected' : ''; ?>>Đã bàn giao</option></select><?php else: 
                                            $ds = ['pending' => '<span class="text-sub">Chờ hàng</span>', 'ready' => '<span class="badge badge-warning">Sẵn sàng tại VP</span>', 'delivered' => '<span class="badge badge-success">Đã nhận</span>']; echo $ds[$it['delivery_status']]; endif; ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table td { padding: 8px 0; vertical-align: top; }
    .print-content { display: none; }
    @media print {
        @page { size: A4 portrait; margin: 2cm 2cm 2cm 3cm; }
        body { background: #fff !important; font-family: "Times New Roman", Times, serif !important; font-size: 12pt !important; color: #000 !important; }
        .no-print, .sidebar, .main-header, .main-footer, .action-header, .header-actions, .badge, .btn, #toast-container { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .content-wrapper { padding: 0 !important; }
        .wrapper { display: block !important; }
        .print-content { display: block !important; width: 100%; }
        .print-table th, .print-table td { border: 1px solid black !important; color: #000 !important; }
        strong, b { font-weight: bold !important; }
    }
</style>
</div>
<?php include '../../../includes/footer.php'; ?>
