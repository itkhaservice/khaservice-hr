@extends('layouts.app')

@section('title', 'Chỉnh sửa: ' . $employee->fullname)

@section('content')
<form action="{{ route('employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="action-header">
        <h1 class="page-title">Chỉnh sửa: {{ $employee->fullname }}</h1>
        <div class="header-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu thay đổi</button>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Quay lại</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 25px;">
        <div class="card" style="text-align: center;">
            <div class="avatar-preview-container" style="margin-bottom: 20px;">
                <img id="avatarPreview" src="{{ $employee->avatar ? asset('storage/' . $employee->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($employee->fullname) . '&size=200&background=24a25c&color=fff' }}" alt="Avatar" style="width: 200px; height: 200px; border-radius: 12px; object-fit: cover; border: 4px solid #f1f5f9;">
            </div>
            <label for="avatarInput" class="btn btn-secondary btn-sm" style="cursor: pointer;">
                <i class="fas fa-camera"></i> Đổi ảnh
            </label>
            <input type="file" id="avatarInput" name="avatar" style="display: none;" accept="image/*" onchange="previewImage(this)">
            
            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">
            <div style="text-align: left;">
                <div class="form-group">
                    <label>Mã nhân viên</label>
                    <div style="font-weight: 700; color: var(--primary-dark);">{{ $employee->code }}</div>
                </div>
                <div class="form-group">
                    <label>Trạng thái</label>
                    <span class="badge {{ $employee->status == 'working' ? 'badge-success' : 'badge-danger' }}">
                        {{ $employee->status == 'working' ? 'Đang làm việc' : 'Nghỉ việc' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card" style="padding: 0;">
            <div class="tabs" style="padding: 0 25px; border-bottom: 1px solid #e2e8f0;">
                <div class="tab-item active" data-tab="personal">Thông tin cá nhân</div>
                <div class="tab-item" data-tab="job">Công việc</div>
            </div>

            <div style="padding: 25px;">
                <div id="personal" class="tab-content active">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Họ và tên</label>
                            <input type="text" name="fullname" class="form-control" value="{{ $employee->fullname }}" required>
                        </div>
                        <div class="form-group">
                            <label>Giới tính</label>
                            <select name="gender" class="form-control">
                                <option value="Nam" {{ $employee->gender == 'Nam' ? 'selected' : '' }}>Nam</option>
                                <option value="Nữ" {{ $employee->gender == 'Nữ' ? 'selected' : '' }}>Nữ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" value="{{ $employee->phone }}">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $employee->email }}">
                        </div>
                    </div>
                </div>

                <div id="job" class="tab-content">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Phòng ban</label>
                            <select name="department_id" id="deptSelect" class="form-control" required>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" {{ $employee->department_id == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Chức vụ</label>
                            <select name="position_id" id="posSelect" class="form-control" required>
                                <!-- Will be loaded via JS -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Dự án hiện tại</label>
                            <select name="current_project_id" class="form-control" required>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}" {{ $employee->current_project_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="working" {{ $employee->status == 'working' ? 'selected' : '' }}>Đang làm việc</option>
                                <option value="resigned" {{ $employee->status == 'resigned' ? 'selected' : '' }}>Đã nghỉ việc</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@section('scripts')
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { $('#avatarPreview').attr('src', e.target.result); }
        reader.readAsDataURL(input.files[0]);
    }
}

function loadPositions(deptId, selectedId = null) {
    if(!deptId) return;
    $.get("{{ url('employees/api/positions') }}/" + deptId, function(data) {
        let $posSelect = $('#posSelect');
        $posSelect.empty();
        data.forEach(p => {
            let selected = (selectedId && p.id == selectedId) ? 'selected' : '';
            $posSelect.append(`<option value="${p.id}" ${selected}>${p.name}</option>`);
        });
    });
}

$('#deptSelect').on('change', function() {
    loadPositions($(this).val());
});

// Init
$(document).ready(function() {
    loadPositions({{ $employee->department_id }}, {{ $employee->position_id }});
    
    $('.tab-item').on('click', function() {
        $('.tab-item, .tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + $(this).data('tab')).addClass('active');
    });
});
</script>
@endsection
@endsection
