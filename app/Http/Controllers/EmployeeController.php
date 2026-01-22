<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Project;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'position', 'project']);

        if ($request->filled('kw')) {
            $kw = $request->kw;
            $query->where(function($q) use ($kw) {
                $q->where('fullname', 'like', "%$kw%")
                  ->orWhere('code', 'like', "%$kw%")
                  ->orWhere('phone', 'like', "%$kw%");
            });
        }

        if ($request->filled('dept_id')) {
            $query->where('department_id', $request->dept_id);
        }

        if ($request->filled('proj_id')) {
            $query->where('current_project_id', $request->proj_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query->orderBy('id', 'desc')->paginate($request->limit ?? 10);
        $departments = Department::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments', 'projects'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        return view('employees.create', compact('departments', 'projects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|unique:employees,code',
            'fullname' => 'required',
            'department_id' => 'required',
            'position_id' => 'required',
            'current_project_id' => 'required',
            'start_date' => 'required|date',
            'status' => 'required',
            'avatar' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('upload/avatars', 'public');
        }

        $employee = Employee::create($data);

        $employee->statusHistory()->create([
            'new_status' => $request->status,
            'change_date' => $request->start_date,
            'note' => 'Tạo mới hồ sơ nhân viên',
            'created_by' => auth()->id()
        ]);

        return redirect()->route('employees.index')->with('success', 'Thêm nhân viên thành công!');
    }

    public function edit(Employee $employee)
    {
        $departments = Department::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        return view('employees.edit', compact('employee', 'departments', 'projects'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'code' => 'required|unique:employees,code,' . $employee->id,
            'fullname' => 'required',
            'department_id' => 'required',
            'position_id' => 'required',
            'current_project_id' => 'required',
            'status' => 'required',
            'avatar' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('upload/avatars', 'public');
        }

        if ($employee->status !== $request->status) {
            $employee->statusHistory()->create([
                'old_status' => $employee->status,
                'new_status' => $request->status,
                'change_date' => now(),
                'note' => 'Cập nhật từ trang quản lý (Laravel)',
                'created_by' => auth()->id()
            ]);
        }

        $employee->update($data);
        return redirect()->back()->with('success', 'Cập nhật nhân viên thành công!');
    }

    public function getPositions(Department $department)
    {
        return response()->json($department->positions()->orderBy('name')->get());
    }

    public function history(Employee $employee)
    {
        // Handled via relationships in blade
        return view('employees.history', compact('employee'));
    }
}
