@extends('layouts.app')

@section('title', 'Dự án & Vận hành')

@section('content')
<div class="action-header">
    <h1 class="page-title">Dự án & Vận hành</h1>
    <a href="#" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm dự án</a>
</div>

<div class="card">
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th width="60">STT</th>
                    <th>Mã dự án</th>
                    <th>Tên tòa nhà / Dự án</th>
                    <th>Địa chỉ</th>
                    <th>Trạng thái</th>
                    <th width="120">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $p)
                    <tr>
                        <td>{{ $p->stt }}</td>
                        <td><strong>{{ $p->code }}</strong></td>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->address }}</td>
                        <td>
                            @php
                                $s_map = ['active'=>'Đang hoạt động', 'completed'=>'Hoàn thành', 'pending'=>'Tạm dừng'];
                                $s_cls = ['active'=>'badge-success', 'completed'=>'badge-info', 'pending'=>'badge-warning'];
                            @endphp
                            <span class="badge {{ $s_cls[$p->status] ?? 'badge-secondary' }}">
                                {{ $s_map[$p->status] ?? $p->status }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('projects.view', $p->id) }}" title="Xem chi tiết"><i class="fas fa-eye text-primary"></i></a> &nbsp;
                            <a href="#" title="Sửa"><i class="fas fa-edit text-warning"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Không tìm thấy dự án nào</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="list-footer">
        <div class="pagination-wrapper">
            {{ $projects->links() }}
        </div>
    </div>
</div>
@endsection
