<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));
        $project_id = $request->input('project_id');

        $projects = Project::where('status', 'active')->orderBy('name')->get();
        if (!$project_id && $projects->count() > 0) {
            $project_id = $projects->first()->id;
        }

        $employees = [];
        $att_data = [];
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        if ($project_id) {
            $employees = Employee::with(['department', 'position'])
                ->where('current_project_id', $project_id)
                ->where('status', 'working')
                ->get();

            $start_date = sprintf("%04d-%02d-01", $year, $month);
            $end_date = sprintf("%04d-%02d-%02d", $year, $month, $days_in_month);

            $raw_att = DB::table('attendance')
                ->whereBetween('date', [$start_date, $end_date])
                ->whereIn('employee_id', $employees->pluck('id'))
                ->get();

            foreach ($raw_att as $r) {
                $att_data[$r->employee_id][(int)date('j', strtotime($r->date))] = [
                    'symbol' => $r->timekeeper_symbol,
                    'ot' => $r->overtime_hours
                ];
            }
        }

        return view('attendance.index', compact('projects', 'employees', 'att_data', 'month', 'year', 'project_id', 'days_in_month'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer',
            'month' => 'required|integer',
            'year' => 'required|integer',
            'changes' => 'required|array'
        ]);

        $projectId = $request->project_id;
        $month = $request->month;
        $year = $request->year;

        foreach ($request->changes as $change) {
            $empId = $change['emp_id'];
            $day = $change['day'];
            $symbol = strtoupper(trim($change['symbol'] ?? ''));
            $ot = (float)($change['ot'] ?? 0);
            $date = "$year-" . sprintf('%02d', $month) . "-" . sprintf('%02d', $day);

            $existing = Attendance::where('employee_id', $empId)->where('date', $date)->first();

            if ($existing) {
                // Audit Logging
                if (($existing->timekeeper_symbol ?? '') !== $symbol) {
                    AttendanceLog::create([
                        'employee_id' => $empId, 'project_id' => $projectId,
                        'attendance_date' => $date, 'old_value' => $existing->timekeeper_symbol,
                        'new_value' => $symbol, 'field_type' => 'symbol', 'changed_by' => auth()->id()
                    ]);
                }
                if ((float)$existing->overtime_hours !== $ot) {
                    AttendanceLog::create([
                        'employee_id' => $empId, 'project_id' => $projectId,
                        'attendance_date' => $date, 'old_value' => $existing->overtime_hours,
                        'new_value' => $ot, 'field_type' => 'ot', 'changed_by' => auth()->id()
                    ]);
                }

                $existing->update(['timekeeper_symbol' => $symbol ?: null, 'overtime_hours' => $ot]);
            } else {
                if ($symbol !== '' || $ot > 0) {
                    Attendance::create([
                        'employee_id' => $empId, 'project_id' => $projectId,
                        'date' => $date, 'timekeeper_symbol' => $symbol, 'overtime_hours' => $ot
                    ]);
                    AttendanceLog::create([
                        'employee_id' => $empId, 'project_id' => $projectId,
                        'attendance_date' => $date, 'old_value' => '',
                        'new_value' => $symbol, 'field_type' => 'symbol', 'changed_by' => auth()->id()
                    ]);
                }
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Đã lưu bảng công thành công!']);
    }

    public function print(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $project_id = $request->project_id;

        $project = Project::findOrFail($project_id);
        $employees = Employee::with(['department', 'position'])
            ->where('current_project_id', $project_id)
            ->where('status', 'working')
            ->get();

        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $att_data = [];
        
        $start_date = sprintf("%04d-%02d-01", $year, $month);
        $end_date = sprintf("%04d-%02d-%02d", $year, $month, $days_in_month);

        $raw_att = DB::table('attendance')
            ->whereBetween('date', [$start_date, $end_date])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get();

        foreach ($raw_att as $r) {
            $att_data[$r->employee_id][(int)date('j', strtotime($r->date))] = [
                'symbol' => $r->timekeeper_symbol,
                'ot' => $r->overtime_hours
            ];
        }

        $settings = DB::table('settings')->pluck('setting_value', 'setting_key');
        $rows_per_page = 15;
        $chunks = $employees->chunk($rows_per_page);
        
        return view('attendance.print', compact('project', 'chunks', 'att_data', 'month', 'year', 'days_in_month', 'settings'));
    }
}
