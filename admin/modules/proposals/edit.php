<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

if (!isset($_SESSION['user_id'])) redirect(BASE_URL . 'admin/login.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$proposal = db_fetch_row("SELECT * FROM material_proposals WHERE id = ?", [$id]);

if (!$proposal) redirect('index.php');

if ($proposal['created_by'] != $_SESSION['user_id'] || !in_array($proposal['status'], ['draft', 'cancelled'])) {
    set_toast('error', 'Bạn không có quyền sửa phiếu này.');
    redirect('index.php');
}

$items = db_fetch_all("SELECT * FROM material_proposal_items WHERE proposal_id = ?", [$id]);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['submit_proposal']) || isset($_POST['save_draft']))) {
    $proj_id = (int)$_POST['project_id'];
    $title = clean_input($_POST['title']);
    $notes = clean_input($_POST['notes']);
    $proposer_name = clean_input($_POST['proposer_name']); 
    $status = isset($_POST['save_draft']) ? 'draft' : 'pending';
    
    $total_est = 0;
    if (isset($_POST['item_total']) && is_array($_POST['item_total'])) {
        foreach ($_POST['item_total'] as $t) { $total_est += (float)str_replace([',', '.'], '', $t); }
    }

    db_query("UPDATE material_proposals SET project_id = ?, proposer_name = ?, title = ?, notes = ?, status = ?, total_amount_est = ? WHERE id = ?", 
             [$proj_id, $proposer_name, $title, $notes, $status, $total_est, $id]);
    
    db_query("DELETE FROM material_proposal_items WHERE proposal_id = ?", [$id]);

    if (isset($_POST['item_name']) && is_array($_POST['item_name'])) {
        foreach ($_POST['item_name'] as $i => $name) {
            if (empty(trim($name))) continue;
            $cat_id = (int)$_POST['item_category'][$i];
            $unit = clean_input($_POST['item_unit'][$i]);
            $qty = (float)$_POST['item_qty'][$i];
            $price = (float)$_POST['item_price'][$i];
            $supply_id = !empty($_POST['item_supply_id'][$i]) ? (int)$_POST['item_supply_id'][$i] : null;
            db_query("INSERT INTO material_proposal_items (proposal_id, supply_id, item_name, category_id, unit, quantity_req, price_est) VALUES (?, ?, ?, ?, ?, ?, ?)", 
                        [$id, $supply_id, $name, $cat_id ?: null, $unit, $qty, $price]);
        }
    }

    set_toast('success', $status == 'draft' ? 'Đã lưu nháp thay đổi.' : 'Đã gửi đề xuất thành công!');
    redirect('index.php');
}

$allowed_projs = get_allowed_projects();
$projects = [];
if ($allowed_projs === 'ALL') {
    $projects = db_fetch_all("SELECT * FROM projects WHERE status = 'active' ORDER BY name ASC");
} elseif (!empty($allowed_projs)) {
    $in_placeholder = implode(',', array_fill(0, count($allowed_projs), '?'));
    $projects = db_fetch_all("SELECT * FROM projects WHERE id IN ($in_placeholder) AND status = 'active' ORDER BY name ASC", $allowed_projs);
} else {
    $curr_emp = db_fetch_row("SELECT current_project_id FROM employees WHERE id = ?", [$_SESSION['user_id']]);
    if ($curr_emp && $curr_emp['current_project_id']) {
        $projects = db_fetch_all("SELECT * FROM projects WHERE id = ? AND status = 'active'", [$curr_emp['current_project_id']]);
    }
}

$project_info = db_fetch_row("SELECT * FROM projects WHERE id = ?", [$proposal['project_id']]);
$categories = db_fetch_all("SELECT * FROM supply_categories ORDER BY id ASC");
$supplies_json = json_encode(db_fetch_all("SELECT s.*, c.name as category_name FROM supplies s JOIN supply_categories c ON s.category_id = c.id"));

$m = $proposal['month']; $y = $proposal['year'];
$used_row = db_fetch_row("SELECT SUM(total_amount_final) as s FROM material_proposals WHERE project_id = ? AND month = ? AND year = ? AND status != 'rejected' AND id != ?", [$proposal['project_id'], $m, $y, $id]);
$used = $used_row['s'] ?? 0;

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <form method="POST" id="proposalForm">
            <div class="action-header">
                <div>
                    <h1 class="page-title">Sửa Đề xuất: <?php echo $proposal['code']; ?></h1>
                    <span class="badge badge-warning">Đang sửa</span>
                </div>
                <div class="header-actions">
                    <button type="submit" name="save_draft" class="btn btn-secondary" style="background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1;"><i class="fas fa-save"></i> Lưu nháp</button>
                    <button type="submit" name="submit_proposal" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Gửi lại</button>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
                </div>
            </div>

            <div class="card">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label>Dự án <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-control">
                            <?php foreach ($projects as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $proposal['project_id'] == $p['id'] ? 'selected' : ''; ?>><?php echo $p['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Người đề xuất</label>
                        <input type="text" name="proposer_name" class="form-control" value="<?php echo $proposal['proposer_name']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Ngân sách tháng (Đã dùng: <?php echo number_format($used); ?>đ)</label>
                        <div style="font-weight: bold; color: var(--primary-color); font-size: 1.1rem; padding-top: 5px;">
                            <?php echo number_format($project_info['budget_limit'] ?? 0); ?> đ
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nội dung / Lý do đề xuất</label>
                    <input type="text" name="title" class="form-control" value="<?php echo $proposal['title']; ?>">
                </div>

                <div class="form-group">
                    <label>Ghi chú thêm (nếu có)</label>
                    <textarea name="notes" class="form-control" rows="2"><?php echo $proposal['notes']; ?></textarea>
                </div>

                <div style="margin-top: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0; font-size: 1.1rem;"><i class="fas fa-list"></i> Chi tiết Vật tư</h3>
                        <button type="button" class="btn btn-sm btn-success" onclick="addNewRow()"><i class="fas fa-plus"></i> Thêm dòng</button>
                    </div>

                    <div class="table-container">
                        <table class="table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Loại vật tư</th>
                                    <th style="width: 30%;">Tên vật tư</th>
                                    <th style="width: 10%;">ĐVT</th>
                                    <th style="width: 10%;">Số lượng</th>
                                    <th style="width: 15%;">Đơn giá (DK)</th>
                                    <th style="width: 15%;">Thành tiền</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $it): ?>
                                <tr class="item-row">
                                    <td>
                                        <select name="item_category[]" class="form-control item-cat" onchange="filterSupplies(this)">
                                            <option value="0">-- Chọn loại --</option>
                                            <?php foreach($categories as $c): ?>
                                                <option value="<?php echo $c['id']; ?>" <?php echo $it['category_id'] == $c['id'] ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                                            <?php endforeach; ?>
                                            <option value="999" <?php echo empty($it['category_id']) ? 'selected' : ''; ?>>Khác...</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div style="position: relative;">
                                            <input type="text" name="item_name[]" class="form-control item-name" value="<?php echo $it['item_name']; ?>" onchange="autoFillPrice(this)" list="list_<?php echo $it['id']; ?>" required>
                                            <datalist id="list_<?php echo $it['id']; ?>"></datalist>
                                            <input type="hidden" name="item_supply_id[]" class="item-supply-id" value="<?php echo $it['supply_id']; ?>">
                                        </div>
                                    </td>
                                    <td><input type="text" name="item_unit[]" class="form-control item-unit" value="<?php echo $it['unit']; ?>"></td>
                                    <td><input type="number" name="item_qty[]" class="form-control item-qty" value="<?php echo (float)$it['quantity_req']; ?>" min="0.1" step="0.1" oninput="calcRow(this)"></td>
                                    <td><input type="number" name="item_price[]" class="form-control item-price" value="<?php echo (float)$it['price_est']; ?>" min="0" step="1000" oninput="calcRow(this)"></td>
                                    <td>
                                        <input type="number" name="item_total[]" class="form-control item-total" value="<?php echo (float)$it['quantity_req'] * (float)$it['price_est']; ?>" readonly style="background: transparent; border: none; font-weight: 700;">
                                    </td>
                                    <td><button type="button" class="btn-icon text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: bold;">
                                    <td colspan="5" style="text-align: right; padding-right: 20px;">TỔNG CỘNG:</td>
                                    <td id="grandTotal" style="color: var(--primary-color); font-size: 1.1rem;">0 đ</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const categories = <?php echo json_encode($categories); ?>;
const masterSupplies = <?php echo $supplies_json; ?>;

$(document).ready(function() {
    updateGrandTotal();
    $('.item-cat').each(function() { filterSupplies(this); });
});

function addNewRow() {
    const rowId = Date.now() + Math.floor(Math.random() * 1000);
    let catOptions = '<option value="0">-- Chọn loại --</option>';
    categories.forEach(c => { catOptions += `<option value="${c.id}">${c.name}</option>`; });
    const html = `
        <tr class="item-row" id="row_${rowId}">
            <td><select name="item_category[]" class="form-control item-cat" onchange="filterSupplies(this)">${catOptions}<option value="999">Khác...</option></select></td>
            <td><div style="position: relative;"><input type="text" name="item_name[]" class="form-control item-name" placeholder="Nhập tên vật tư..." list="list_${rowId}" onchange="autoFillPrice(this)" autocomplete="off" required><datalist id="list_${rowId}"></datalist><input type="hidden" name="item_supply_id[]" class="item-supply-id"></div></td>
            <td><input type="text" name="item_unit[]" class="form-control item-unit" placeholder="ĐVT"></td>
            <td><input type="number" name="item_qty[]" class="form-control item-qty" value="1" min="0.1" step="0.1" oninput="calcRow(this)"></td>
            <td><input type="number" name="item_price[]" class="form-control item-price" value="0" min="0" step="1000" oninput="calcRow(this)"></td>
            <td><input type="number" name="item_total[]" class="form-control item-total" value="0" readonly style="background: transparent; border: none; font-weight: 700;"></td>
            <td><button type="button" class="btn-icon text-danger" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
        </tr>
    `;
    $('#itemsTable tbody').append(html);
}

function removeRow(btn) {
    $(btn).closest('tr').remove();
    if ($('#itemsTable tbody tr').length === 0) addNewRow();
    updateGrandTotal();
}

function filterSupplies(select) {
    const catId = $(select).val();
    const $row = $(select).closest('tr');
    const $datalist = $row.find('datalist');
    $datalist.empty();
    if (catId == 999 || catId == 0) return; 
    const filtered = masterSupplies.filter(s => s.category_id == catId);
    filtered.forEach(s => { $datalist.append(`<option value="${s.name}" data-unit="${s.unit}" data-price="${s.reference_price}" data-id="${s.id}">`); });
}

function autoFillPrice(input) {
    const val = $(input).val();
    const $row = $(input).closest('tr');
    const $datalist = $row.find('datalist');
    let found = false;
    $datalist.find('option').each(function() {
        if ($(this).val() === val) {
            $row.find('.item-unit').val($(this).data('unit'));
            $row.find('.item-price').val($(this).data('price'));
            $row.find('.item-supply-id').val($(this).data('id'));
            calcRow(input);
            found = true;
            return false;
        }
    });
    if (!found) $row.find('.item-supply-id').val('');
}

function calcRow(input) {
    const $row = $(input).closest('tr');
    const qty = parseFloat($row.find('.item-qty').val()) || 0;
    const price = parseFloat($row.find('.item-price').val()) || 0;
    $row.find('.item-total').val(qty * price);
    updateGrandTotal();
}

function updateGrandTotal() {
    let grand = 0;
    $('.item-total').each(function() { grand += parseFloat($(this).val()) || 0; });
    $('#grandTotal').text(new Intl.NumberFormat('vi-VN').format(grand) + ' đ');
}
</script>

<style>
.btn-icon { background: none; border: none; cursor: pointer; font-size: 1.2rem; }
.table th { background: #f1f5f9; font-size: 0.85rem; text-transform: uppercase; color: #475569; }
.form-control { border-color: #cbd5e1; }
</style>

<?php include '../../../includes/footer.php'; ?>