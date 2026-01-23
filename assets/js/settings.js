$(document).ready(function() {
    // Tab switching logic
    if(window.location.hash) {
        var hash = window.location.hash.substring(1);
        showTab(hash);
    }
    $('.tab-item').on('click', function() {
        var tabId = $(this).data('tab');
        showTab(tabId);
    });

    // Money formatting logic
    $('.input-money').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value !== "") {
            $(this).val(new Intl.NumberFormat('en-US').format(value));
        }
    });
});

function showTab(tabId) {
    $('.tab-item').removeClass('active');
    $('.tab-content').removeClass('active');
    $(`.tab-item[data-tab="${tabId}"]`).addClass('active');
    $('#' + tabId).addClass('active');
    
    // Prevent auto-scroll by using pushState instead of location.hash
    if(history.pushState) {
        history.pushState(null, null, '#' + tabId);
    } else {
        window.location.hash = tabId;
    }
}

// Department Logic
function editDept(data) {
    $('#deptFormTitle').text('Sửa phòng ban');
    $('#deptId').val(data.id);
    $('#deptCode').val(data.code);
    $('#deptName').val(data.name);
    
    $('#deptBtn').attr('name', 'edit_dept').html('<i class="fas fa-save"></i> Cập nhật');
    $('#deptCancel').show();
}

function resetDeptForm() {
    $('#deptFormTitle').text('Thêm phòng ban mới');
    $('#deptForm')[0].reset();
    $('#deptId').val('');
    
    $('#deptBtn').attr('name', 'add_dept').html('Thêm mới');
    $('#deptCancel').hide();
}

function confirmDelDept(id) {
    Modal.confirm('Bạn có chắc muốn xóa phòng ban này?', () => {
        location.href = 'index.php?del_dept=' + id;
    });
}

// Position Logic
function openPosModal(deptId, deptName) {
    $('#posModalTitle').text('Thêm chức vụ');
    $('#posModalSubtitle').text('Phòng ban: ' + deptName);
    $('#posDeptId').val(deptId);
    $('#posId').val('');
    $('#posName').val('');
    
    $('#posBtn').attr('name', 'add_position').text('Thêm mới');
    $('#posModal').css('display', 'flex');
}

function editPos(data, deptName) {
    $('#posModalTitle').text('Sửa chức vụ');
    $('#posModalSubtitle').text('Phòng ban: ' + deptName);
    $('#posDeptId').val(data.department_id);
    $('#posId').val(data.id);
    $('#posName').val(data.name);
    
    $('#posBtn').attr('name', 'edit_position').text('Cập nhật');
    $('#posModal').css('display', 'flex');
}

function confirmDelPos(id) {
    Modal.confirm('Bạn có chắc muốn xóa chức vụ này?', () => {
        location.href = 'index.php?del_position=' + id;
    });
}

// Document Type Logic
function editDoc(data) {
    $('#docFormTitle').text('Sửa loại hồ sơ');
    $('#docId').val(data.id);
    $('#docCode').val(data.code);
    $('#docName').val(data.name);
    $('#docRequired').prop('checked', data.is_required == 1);
    $('#docMultiple').prop('checked', data.is_multiple == 1);
    
    $('#docBtn').attr('name', 'edit_doctype').html('<i class="fas fa-save"></i> Cập nhật');
    $('#docCancel').show();
}

function resetDocForm() {
    $('#docFormTitle').text('Thêm loại hồ sơ');
    $('#docForm')[0].reset();
    $('#docId').val('');
    $('#docRequired').prop('checked', true); // Default
    
    $('#docBtn').attr('name', 'add_doctype').html('Thêm mới');
    $('#docCancel').hide();
}

function confirmDelDoc(id) {
    Modal.confirm('Bạn có chắc muốn xóa loại hồ sơ này? Các tài liệu liên quan sẽ bị lỗi hiển thị.', () => {
        location.href = 'index.php?del_doctype=' + id;
    });
}