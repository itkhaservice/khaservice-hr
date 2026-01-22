@extends('layouts.app')

@section('title', "Bảng Chấm Công $month/$year")

@section('styles')
<style>
.attendance-table { width: max-content; border-collapse: separate; border-spacing: 0; }
.attendance-table th, .attendance-table td { border: 1px solid var(--border-color); padding: 0; height: 45px; }
.fix-l { position: sticky; left: 0; background: #fff; z-index: 10; }
.is-sunday { background-color: #fef9c3 !important; }
.att-input { width: 100%; border: none; text-align: center; background: transparent; outline: none; font-weight: bold; }
.att-input.ot { font-size: 0.75rem; color: #c2410c; }
</style>
@endsection

@section('content')
<div class="action-header">
    <h1 class="page-title">Bảng Chấm Công - {{ $month }}/{{ $year }}</h1>
    <div class="header-actions">
        <button class="btn btn-secondary btn-sm" onclick="window.open('{{ route('attendance.print', ['month' => $month, 'year' => $year, 'project_id' => $project_id]) }}', '_blank')"><i class="fas fa-print"></i> In Bảng Công</button>
        <button class="btn btn-primary" id="btnSave" onclick="saveAttendance()"><i class="fas fa-save"></i> LƯU DỮ LIỆU</button>
    </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="attendance-toolbar" style="background: var(--card-bg); padding: 15px; border-radius: 8px; margin-bottom: 15px; display: flex; gap: 15px; align-items: center;">
    <form method="GET" style="display: flex; gap: 10px;">
        <select name="project_id" class="form-control" onchange="this.form.submit()">
            @foreach($projects as $p)
                <option value="{{ $p->id }}" {{ $project_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <select name="month" class="form-control" onchange="this.form.submit()">
            @for($i=1; $i<=12; $i++) <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>T{{ $i }}</option> @endfor
        </select>
        <select name="year" class="form-control" onchange="this.form.submit()">
            @for($y=2024; $y<=2026; $y++) <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option> @endfor
        </select>
    </form>
</div>

<div class="card" style="padding: 0; overflow: auto; height: calc(100vh - 250px);">
    <table class="attendance-table">
        <thead>
            <tr>
                <th class="fix-l" width="50">STT</th>
                <th class="fix-l" style="left: 50px; border-right: 2px solid #cbd5e1;" width="200">Nhân viên</th>
                @for($d=1; $d<=$days_in_month; $d++)
                    @php $dow = date('N', strtotime("$year-$month-$d")); @endphp
                    <th width="45" class="{{ $dow == 7 ? 'is-sunday' : '' }}">
                        <div style="font-size: 0.8rem;">{{ $d }}</div>
                        <div style="font-size: 0.6rem;">{{ ['', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'][$dow] }}</div>
                    </th>
                @endfor
                <th width="60" style="position: sticky; right: 0; background: #f8fafc; z-index: 10;">Tổng</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $index => $e)
                <tr data-emp-id="{{ $e->id }}">
                    <td class="fix-l text-center">{{ $index + 1 }}</td>
                    <td class="fix-l" style="left: 50px; padding: 5px 10px; border-right: 2px solid #cbd5e1;">
                        <div style="font-weight: bold; font-size: 0.85rem;">{{ $e->fullname }}</div>
                        <div style="font-size: 0.65rem; color: var(--text-sub);">{{ $e->position->name ?? $e->position }}</div>
                    </td>
                    @for($d=1; $d<=$days_in_month; $d++)
                        @php 
                            $dow = date('N', strtotime("$year-$month-$d")); 
                            $cell = $att_data[$e->id][$d] ?? ['symbol' => '', 'ot' => ''];
                        @endphp
                        <td class="{{ $dow == 7 ? 'is-sunday' : '' }}">
                            <input type="text" class="att-input sym" data-day="{{ $d }}" value="{{ $cell['symbol'] }}" oninput="this.value = this.value.toUpperCase()">
                            <input type="text" class="att-input ot" data-day="{{ $d }}" value="{{ $cell['ot'] }}">
                        </td>
                    @endfor
                    <td class="sum-total text-center" style="position: sticky; right: 0; background: #fff; font-weight: bold; color: var(--primary-color);">0</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@section('scripts')
<script>
function saveAttendance() {
    let payload = [];
    $('tr[data-emp-id]').each(function() {
        let empId = $(this).data('emp-id');
        $(this).find('.att-input.sym').each(function() {
            let day = $(this).data('day');
            let symbol = $(this).val();
            let ot = $(this).closest('td').find('.ot').val();
            if (symbol || ot > 0) {
                payload.push({ emp_id: empId, day: day, symbol: symbol, ot: ot });
            }
        });
    });

    $('#btnSave').prop('disabled', true).text('ĐANG LƯU...');

    $.ajax({
        url: "{{ route('attendance.save') }}",
        method: "POST",
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            project_id: {{ $project_id }},
            month: {{ $month }},
            year: {{ $year }},
            changes: payload
        },
        success: function(res) {
            alert(res.message);
            $('#btnSave').prop('disabled', false).html('<i class="fas fa-save"></i> LƯU DỮ LIỆU');
        },
        error: function() {
            alert('Lỗi khi lưu dữ liệu!');
            $('#btnSave').prop('disabled', false).html('<i class="fas fa-save"></i> LƯU DỮ LIỆU');
        }
    });
}
</script>
@endsection
@endsection
