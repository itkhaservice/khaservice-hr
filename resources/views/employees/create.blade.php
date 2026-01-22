@extends('layouts.app')

@section('title', 'Thêm nhân viên mới')

@section('content')
<form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="action-header">
        <h1 class="page-title">Thêm Nhân viên mới</h1>
        <div class="header-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu nhân viên</button>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 25px;">
        <div class="card" style="text-align: center;">
            <div class="avatar-preview-container" style="margin-bottom: 20px;">
                <img id="avatarPreview" src="https://ui-avatars.com/api/?name=NV&size=200&background=cbd5e1&color=fff" alt="Avatar" style="width: 200px; height: 200px; border-radius: 12px; object-fit: cover; border: 4px solid #f1f5f9;">
            </div>
            <label for="avatarInput" class="btn btn-secondary btn-sm" style="cursor: pointer;">
                <i class="fas fa-camera"></i> Chọn ảnh khuôn mặt
            </label>
            <input type="file" id="avatarInput" name="avatar" style="display: none;" accept="image/*" onchange="previewImage(this)">
        </div>

        <div class="card">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Mã nhân viên <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required value="{{ old('code') }}">
                </div>
                <div class="form-group">
                    <label>Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" name="fullname" class="form-control" required value="{{ old('fullname') }}">
                </div>
                <div class="form-group">
                    <label>Phòng ban</label>
                    <select name="department_id" id="deptSelect" class="form-control" required>
                        <option value="">-- Chọn ban --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Chức vụ</label>
                    <select name="position_id" id="posSelect" class="form-control" required>
                        <option value="">-- Chọn chức vụ --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Dự án hiện tại</label>
                    <select name="current_project_id" class="form-control" required>
                        <option value="">-- Chọn dự án --</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Ngày bắt đầu làm việc</label>
                    <input type="date" name="start_date" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="working">Đang làm việc</option>
                        <option value="resigned">Đã nghỉ việc</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { $('#avatarPreview').attr('src', e.target.result); }
        reader.readAsDataURL(input.files[0]);
    }
}

// Simple dynamic positions (I will implement a real API for this in next turn)
$('#deptSelect').on('change', function() {
    let deptId = $(this).val();
    if(deptId) {
        // Mocking for now, will fix with real data
        $('#posSelect').empty().append('<option value="1">Đang tải chức vụ...</option>');
    }
});
</script>
@endsection
