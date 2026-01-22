@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="action-header">
    <div>
        <h1 class="page-title" style="margin-bottom: 8px;">{{ $project->name }}</h1>
        <div class="project-location">
            <i class="fas fa-map-marker-alt"></i>
            <span>{{ $project->address }}</span>
        </div>
    </div>
    <div class="header-actions">
        <a href="#" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
        <a href="{{ route('projects.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 350px 1fr; gap: 25px;">
    <!-- Left Column -->
    <div style="display: flex; flex-direction: column; gap: 25px;">
        <div class="card">
            <h3 style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 15px;">Thông tin chung</h3>
            <div class="info-row">
                <label>Mã dự án:</label>
                <strong>{{ $project->code }}</strong>
            </div>
            <div class="info-row">
                <label>Trạng thái:</label>
                <span class="badge badge-success">{{ $project->status }}</span>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div style="display: flex; flex-direction: column; gap: 25px;">
        <div class="card">
            <h3 style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 15px;">Định biên nhân sự chi tiết</h3>
            <div class="table-container">
                @if(empty($grouped_data))
                    <div class="text-center" style="padding: 20px; color: #94a3b8;">Chưa cấu hình định biên chi tiết.</div>
                @else
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Phòng ban / Chức vụ</th>
                                <th class="text-center">Định biên</th>
                                <th class="text-center">Thực tế</th>
                                <th class="text-center">Chênh lệch</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grouped_data as $dept => $items)
                                <tr style="background-color: #f8fafc;">
                                    <td colspan="4" style="font-weight: 700; color: var(--primary-dark);">
                                        <i class="fas fa-layer-group"></i> {{ $dept }}
                                    </td>
                                </tr>
                                @foreach ($items as $it)
                                    <tr>
                                        <td style="padding-left: 30px;">{{ $it->position_name }}</td>
                                        <td class="text-center"><strong>{{ $it->count_required }}</strong></td>
                                        <td class="text-center">{{ $it->actual_count }}</td>
                                        <td class="text-center">
                                            <span style="font-weight:bold; color: {{ $it->diff >= 0 ? ($it->diff == 0 ? '#24a25c' : '#f59e0b') : '#dc2626' }}">
                                                {{ ($it->diff > 0 ? '+' : '') . $it->diff }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="card">
            <h3>Cấu hình ca làm việc</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tên ca</th>
                            <th>Loại</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $s)
                            <tr>
                                <td><strong>{{ $s->name }}</strong></td>
                                <td><span class="badge badge-info">{{ $s->type }}</span></td>
                                <td>{{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center">Chưa có ca làm việc nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #e2e8f0; }
.project-location { display: inline-flex; align-items: center; gap: 8px; background: #fff; padding: 6px 16px; border-radius: 50px; font-size: 0.85rem; border: 1px solid var(--border-color); }
.project-location i { color: #ef4444; }
.text-center { text-align: center; }
</style>
@endsection
