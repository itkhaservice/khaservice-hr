<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Department;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEmployees = Employee::where('status', 'working')->count();
        $totalProjects = Project::where('status', 'active')->count();
        $totalDepartments = Department::count();
        
        // Mock data for shortages (will implement real logic in Phase 2)
        $totalShortage = 0; 

        return view('dashboard', compact('totalEmployees', 'totalProjects', 'totalDepartments', 'totalShortage'));
    }
}