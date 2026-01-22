@extends('layouts.app')

@section('title', 'Quản lý Nhân sự')

@section('content')
<div class="action-header">
    <h1 class="page-title">Quản lý Nhân sự</h1>
    <a href="{{ route('employees.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm nhân viên</a>
</div>

<!-- Filters -->
<form method="GET" action="{{ route('employees.index') }}" class="filter-section">
    <input type="text" name="kw" value="{{ request('kw') }}" class="form-control" placeholder="Tên, mã, SĐT...">
    <select name="dept_id" class="form-control">
        <option value="">-- Phòng ban --</option>
        @foreach ($departments as $d)
            <option value="{{ $d->id }}" {{ request('dept_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
        @endforeach
    </select>
    <select name="proj_id" class="form-control">
        <option value="">-- Dự án --</option>
        @foreach ($projects as $p)
            <option value="{{ $p->id }}" {{ request('proj_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
        @endforeach
    </select>
    <select name="status" class="form-control">
        <option value="">-- Trạng thái --</option>
        <option value="working" {{ request('status') == 'working' ? 'selected' : '' }}>Đang làm việc</option>
        <option value="resigned" {{ request('status') == 'resigned' ? 'selected' : '' }}>Đã nghỉ việc</option>
    </select>
    
    <div style="display: flex; gap: 5px;">
        <button type="submit" class="btn btn-secondary" style="flex: 1;"><i class="fas fa-filter"></i> Lọc</button>
        @if(request()->hasAny(['kw', 'dept_id', 'proj_id', 'status']))
            <a href="{{ route('employees.index') }}" class="btn btn-danger" title="Xóa lọc"><i class="fas fa-times"></i></a>
        @endif
    </div>
</form>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã NV</th>
                    <th>Họ và tên</th>
                    <th>Phòng ban</th>
                    <th>Chức vụ</th>
                    <th>Dự án hiện tại</th>
                    <th>Trạng thái</th>
                    <th width="120">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td><strong>{{ $e->code }}</strong></td>
                        <td>{{ $e->fullname }}</td>
                        <td>{{ $e->department->name ?? '-' }}</td>
                        <td>{{ $e->position->name ?? $e->position }}</td>
                        <td>{{ $e->project->name ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $e->status == 'working' ? 'badge-info' : 'badge-danger' }}">
                                {{ $e->status == 'working' ? 'Đang làm việc' : 'Đã nghỉ' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('employees.edit', $e->id) }}" title="Sửa"><i class="fas fa-edit text-warning"></i></a> &nbsp;
                            <a href="{{ route('employees.history', $e->id) }}" title="Lịch sử"><i class="fas fa-history text-info"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">Không tìm thấy nhân viên nào</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- List Footer -->
    <div class="list-footer">
        <div class="display-count">
            <span>Hiển thị:</span>
            <select onchange="location.href='{{ route('employees.index', array_merge(request()->query(), ['limit' => ''])) }}' + this.value">
                @foreach ([10, 20, 50, 100] as $l)
                    <option value="{{ $l }}" {{ $employees->perPage() == $l ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="pagination-wrapper">
            {{ $employees->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
