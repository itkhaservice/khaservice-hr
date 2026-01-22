<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Bảng Chấm Công - {{ $month }}/{{ $year }} - {{ $project->name }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 5mm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 11pt; margin: 0; padding: 0; color: #000; }
        .page-container { width: 100%; height: 185mm; position: relative; page-break-after: always; box-sizing: border-box; overflow: hidden; }
        .page-container:last-child { page-break-after: avoid !important; }
        .attendance-table { width: 100%; border-collapse: collapse; font-size: 9pt; border: 1px solid #000; }
        .attendance-table th, .attendance-table td { border: 1px solid #000; padding: 4px 2px; text-align: center; }
        .attendance-table thead th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
        .text-left { text-align: left !important; padding-left: 5px !important; }
        .is-sunday { background-color: #eee !important; -webkit-print-color-adjust: exact; }
        .header-table { width: 100%; margin-bottom: 10px; }
        .company-name { font-weight: bold; text-transform: uppercase; font-size: 11pt; }
        .report-title { text-align: center; margin-bottom: 10px; }
        .report-title h2 { margin: 0; text-transform: uppercase; font-size: 14pt; }
        .page-footer-info { position: absolute; bottom: 0; right: 0; font-size: 9pt; font-style: italic; }
        .signature-table { width: 100%; margin-top: 15px; text-align: center; border-collapse: collapse; }
        .signature-table td { width: 25%; vertical-align: top; border: none !important; }
        .signature-space { height: 160px; }
        .legend-container { border: 1px solid #000; padding: 6px; margin-top: 5px; }
        .legend-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2px 10px; font-size: 7.5pt; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    @php 
        $total_pages = $chunks->count();
        $last_chunk = $chunks->last();
        $need_extra_page = ($last_chunk && $last_chunk->count() > 8);
        if ($need_extra_page) $total_pages++;
    @endphp

    @foreach ($chunks as $index => $page_employees)
        <div class="page-container">
            @if ($index == 0)
                <table class="header-table">
                    <tr>
                        <td style="width: 50%;">
                            <div class="company-name">{{ $settings['company_name'] ?? 'KHASERVICE' }}</div>
                            <div style="font-size: 8pt;">{{ $settings['company_address'] ?? '' }}</div>
                        </td>
                        <td style="text-align: right; vertical-align: top; font-size: 9pt;">
                            Dự án/Bộ phận: <strong>{{ $project->name }}</strong>
                        </td>
                    </tr>
                </table>
                <div class="report-title">
                    <h2>BẢNG CHẤM CÔNG NHÂN VIÊN</h2>
                    <div style="font-style: italic; font-size: 10pt;">Tháng {{ $month }} năm {{ $year }}</div>
                </div>
            @else
                <div style="height: 10px;"></div>
            @endif

            <table class="attendance-table">
                <thead>
                    <tr>
                        <th rowspan="2" width="30">STT</th>
                        <th rowspan="2" width="180">Họ và tên</th>
                        <th colspan="{{ $days_in_month }}">Ngày trong tháng</th>
                        <th colspan="3">Ngày nghỉ</th>
                        <th colspan="3">Tăng ca</th>
                        <th rowspan="2" width="40">Tổng</th>
                    </tr>
                    <tr>
                        @for($d=1; $d<=$days_in_month; $d++)
                            @php $dow = date('N', strtotime("$year-$month-$d")); @endphp
                            <th width="20" class="{{ $dow == 7 ? 'is-sunday' : '' }}">{{ $d }}</th>
                        @endfor
                        <th width="20">P</th><th width="20">K</th><th width="20">L</th>
                        <th width="20">TC</th><th width="20">CN</th><th width="20">L</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($page_employees as $emp_index => $e)
                        <tr>
                            <td>{{ ($index * 15) + $emp_index + 1 }}</td>
                            <td class="text-left">
                                <div style="font-weight: bold; font-size: 9pt;">{{ $e->fullname }}</div>
                                <div style="font-size: 7.5pt; font-style: italic;">{{ $e->department->name ?? '-' }} - {{ $e->position->name ?? $e->position }}</div>
                            </td>
                            @for($d=1; $d<=$days_in_month; $d++)
                                @php 
                                    $cell = $att_data[$e->id][$d] ?? ['symbol' => '', 'ot' => ''];
                                    $dow = date('N', strtotime("$year-$month-$d"));
                                @endphp
                                <td class="{{ $dow == 7 ? 'is-sunday' : '' }}">
                                    <div style="font-weight: bold;">{{ $cell['symbol'] }}</div>
                                    <div style="font-size: 7pt; color: #d00;">{{ $cell['ot'] > 0 ? $cell['ot'] : '' }}</div>
                                </td>
                            @endfor
                            <td></td><td></td><td></td><td></td><td></td><td></td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($index == $chunks->count() - 1 && !$need_extra_page)
                @include('attendance.partials.print_footer')
            @endif

            <div class="page-footer-info">Trang {{ $index + 1 }}/{{ $total_pages }}</div>
        </div>
    @endforeach

    @if ($need_extra_page)
        <div class="page-container">
            <div style="height: 20px;"></div>
            @include('attendance.partials.print_footer')
            <div class="page-footer-info">Trang {{ $total_pages }}/{{ $total_pages }}</div>
        </div>
    @endif
</body>
</html>
