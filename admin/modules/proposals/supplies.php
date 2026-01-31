<?php
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';

require_permission('manage_system');

// Handle Add/Edit Supply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_supply'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $category_id = (int)$_POST['category_id'];
    $name = clean_input($_POST['name']);
    $unit = clean_input($_POST['unit']);
    $price = (float)$_POST['reference_price'];
    $supplier = clean_input($_POST['supplier']);

    if ($id > 0) {
        db_query("UPDATE supplies SET category_id = ?, name = ?, unit = ?, reference_price = ?, supplier = ? WHERE id = ?", 
                 [$category_id, $name, $unit, $price, $supplier, $id]);
        set_toast('success', 'Cập nhật vật tư thành công!');
    } else {
        db_query("INSERT INTO supplies (category_id, name, unit, reference_price, supplier) VALUES (?, ?, ?, ?, ?)", 
                 [$category_id, $name, $unit, $price, $supplier]);
        set_toast('success', 'Thêm vật tư mới thành công!');
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db_query("DELETE FROM supplies WHERE id = ?", [$id]);
    set_toast('success', 'Đã xóa vật tư!');
    redirect('supplies.php');
}

$categories = db_fetch_all("SELECT * FROM supply_categories ORDER BY name ASC");
$supplies = db_fetch_all("
    SELECT s.*, c.name as category_name 
    FROM supplies s 
    LEFT JOIN supply_categories c ON s.category_id = c.id 
    ORDER BY c.name ASC, s.name ASC
");

include '../../../includes/header.php';
include '../../../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../../../includes/topbar.php'; ?>

    <div class="content-wrapper">
        <div class="action-header">
            <h1 class="page-title">Danh mục Vật tư Tham khảo</h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                <button onclick="openAddModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm vật tư</button>
            </div>
        </div>

        <div class="card">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Loại vật tư</th>
                            <th>Tên vật tư</th>
                            <th>ĐVT</th>
                            <th>Giá tham khảo</th>
                            <th>Nhà cung cấp</th>
                            <th width="100">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($supplies)): ?>
                            <tr><td colspan="6" class="text-center" style="padding: 30px; color: #94a3b8;">Chưa có dữ liệu vật tư.</td></tr>
                        <?php else: ?>
                            <?php foreach ($supplies as $s): ?>
                                <tr>
                                    <td><span class="badge badge-info"><?php echo $s['category_name']; ?></span></td>
                                    <td><strong><?php echo $s['name']; ?></strong></td>
                                    <td><?php echo $s['unit']; ?></td>
                                    <td style="color: var(--primary-color); font-weight: 600;"><?php echo number_format($s['reference_price']); ?> đ</td>
                                    <td><?php echo $s['supplier']; ?></td>
                                    <td>
                                        <a href="javascript:void(0)" onclick='openEditModal(<?php echo json_encode($s); ?>)' title="Sửa"><i class="fas fa-edit text-warning"></i></a> &nbsp;
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $s['id']; ?>)" title="Xóa"><i class="fas fa-trash text-danger"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="supplyModal" class="modal-overlay">
    <div class="modal-box" style="width: 500px; text-align: left;">
        <h3 class="modal-title" id="modalTitle">Thêm vật tư mới</h3>
        <form method="POST">
            <input type="hidden" name="id" id="supply_id">
            <div class="form-group">
                <label>Loại vật tư <span class="text-danger">*</span></label>
                <select name="category_id" id="category_id" class="form-control" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tên vật tư <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" required placeholder="VD: Nước lau sàn Sunlight">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Đơn vị tính</label>
                    <input type="text" name="unit" id="unit" class="form-control" placeholder="VD: Chai, Thùng, Cái...">
                </div>
                <div class="form-group">
                    <label>Giá tham khảo</label>
                    <input type="number" name="reference_price" id="reference_price" class="form-control" value="0">
                </div>
            </div>
            <div class="form-group">
                <label>Ghi chú nhà cung cấp</label>
                <input type="text" name="supplier" id="supplier" class="form-control" placeholder="Tên công ty / Cửa hàng...">
            </div>
            <div class="modal-actions" style="justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                <button type="submit" name="save_supply" class="btn btn-primary">Lưu dữ liệu</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    $('#modalTitle').text('Thêm vật tư mới');
    $('#supply_id').val('');
    $('#name').val('');
    $('#unit').val('');
    $('#reference_price').val(0);
    $('#supplier').val('');
    $('#supplyModal').css('display', 'flex');
}

function openEditModal(data) {
    $('#modalTitle').text('Chỉnh sửa vật tư');
    $('#supply_id').val(data.id);
    $('#category_id').val(data.category_id);
    $('#name').val(data.name);
    $('#unit').val(data.unit);
    $('#reference_price').val(data.reference_price);
    $('#supplier').val(data.supplier);
    $('#supplyModal').css('display', 'flex');
}

function closeModal() {
    $('#supplyModal').hide();
}

function confirmDelete(id) {
    Modal.confirm('Bạn có chắc chắn muốn xóa vật tư này khỏi danh mục tham khảo?', () => {
        location.href = '?delete=' + id;
    });
}

window.onclick = function(event) {
    if (event.target == document.getElementById('supplyModal')) closeModal();
}
</script>
</div>
<?php include '../../../includes/footer.php'; ?>
