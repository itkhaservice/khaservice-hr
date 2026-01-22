@extends('layouts.app')

@section('title', 'Lịch sử Nhân sự: ' . $employee->fullname)

@section('content')
<div class="action-header">
    <div>
        <h1 class="page-title">Lịch sử Nhân sự: {{ $employee->fullname }}</h1>
        <p style="color: var(--text-sub);"><i class="fas fa-id-card"></i> Mã NV: {{ $employee->code }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
    <!-- Column 1: Status Changes -->
    <div class="card">
        <h3 style="margin-top: 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 20px;">
            <i class="fas fa-history text-primary"></i> Quá trình Công tác
        </h3>
        <div class="timeline" style="position: relative; padding-left: 30px;">
            <div style="position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;"></div>
            
            @forelse($employee->statusHistory->sortByDesc('created_at') as $h)
                <div class="timeline-item" style="position: relative; margin-bottom: 25px;">
                    <div style="position: absolute; left: -25px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--primary-color); border: 2px solid #fff; box-shadow: 0 0 0 3px #f1f5f9;"></div>
                    <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 5px;">
                        @php
                            $s_map = ['working' => 'Đi làm / Tái tuyển dụng', 'resigned' => 'Nghỉ việc', 'maternity_leave' => 'Nghỉ thai sản', 'unpaid_leave' => 'Nghỉ không lương'];
                        @endphp
                        {{ $s_map[$h->new_status] ?? $h->new_status }}
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-sub);">
                        <i class="far fa-calendar-alt"></i> Ngày áp dụng: {{ date('d/m/Y', strtotime($h->change_date)) }}
                    </div>
                    <div style="background: #f8fafc; padding: 10px; border-radius: 6px; margin-top: 8px; font-size: 0.85rem; border: 1px solid #e2e8f0;">
                        <strong>Ghi chú:</strong> {{ $h->note }}<br>
                        <small style="color: #64748b;"><i class="fas fa-user-edit"></i> Bởi: {{ $h->creator->fullname ?? 'Hệ thống' }}</small>
                    </div>
                </div>
            @empty
                <p style="color: #94a3b8;">Chưa có dữ liệu lịch sử.</p>
            @endforelse
        </div>
    </div>

    <!-- Column 2: Attendance Logs -->
    <div class="card">
        <h3 style="margin-top: 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 20px;">
            <i class="fas fa-user-shield text-danger"></i> Nhật ký Chỉnh sửa Công (Audit)
        </h3>
        <div class="table-container" style="max-height: 600px; overflow-y: auto;">
            <table class="table" style="font-size: 0.85rem;">
                <thead style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th>Ngày công</th>
                        <th>Loại</th>
                        <th>Cũ -> Mới</th>
                        <th>Người sửa</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Fetch logs manually as we haven't defined relationship yet
                        $att_logs = \App\Models\AttendanceLog::where('employee_id', $employee->id)->orderBy('changed_at', 'desc')->get();
                    @endphp
                    @forelse($att_logs as $l)
                        <tr>
                            <td><strong>{{ date('d/m/Y', strtotime($l->attendance_date)) }}</strong></td>
                            <td>
                                <span class="badge {{ $l->field_type == 'symbol' ? 'badge-info' : 'badge-warning' }}">
                                    {{ $l->field_type == 'symbol' ? 'Ký hiệu' : 'Giờ OT' }}
                                </span>
                            </td>
                            <td>
                                <span style="text-decoration: line-through; color: #94a3b8;">{{ $l->old_value ?: '(trống)' }}</span> 
                                <i class="fas fa-long-arrow-alt-right"></i> 
                                <span style="font-weight: 700; color: var(--primary-dark);">{{ $l->new_value ?: '(trống)' }}</span>
                            </td>
                            <td>
                                <div style="font-weight: 600;">{{ $l->changer->fullname ?? 'Admin' }}</div>
                                <small style="color: #94a3b8;">{{ date('d/m H:i', strtotime($l->changed_at)) }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center" style="padding: 20px; color: #94a3b8;">Chưa có log chỉnh sửa nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
