<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';

    protected $fillable = [
        'code', 'fullname', 'avatar', 'gender', 'dob', 'phone', 'email', 
        'identity_card', 'department_id', 'position_id', 'current_project_id', 
        'position', 'status', 'start_date', 'end_date'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'current_project_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(EmployeeStatusHistory::class);
    }
}