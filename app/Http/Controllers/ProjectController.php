<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::orderBy('stt')->paginate($request->limit ?? 10);
        return view('projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        $id = $project->id;
        
        // Fetch staffing data (matching logic from legacy)
        $positions_data = DB::table('project_positions as pp')
            ->leftJoin('departments as d', 'pp.department_id', '=', 'd.id')
            ->where('pp.project_id', $id)
            ->select('pp.*', 'd.name as dept_name')
            ->orderBy('d.id')
            ->get();

        $grouped_data = [];
        foreach ($positions_data as $row) {
            $actual_count = Employee::where('current_project_id', $id)
                ->where('department_id', $row->department_id)
                ->where('position', $row->position_name)
                ->where('status', 'working')
                ->count();
            
            $row->actual_count = $actual_count;
            $row->diff = $actual_count - $row->count_required;
            $grouped_data[$row->dept_name ?: 'Khác'][] = $row;
        }

        // Fetch shifts
        $shifts = DB::table('shifts')->where('project_id', $id)->orderBy('start_time')->get();

        return view('projects.view', compact('project', 'grouped_data', 'shifts'));
    }
}