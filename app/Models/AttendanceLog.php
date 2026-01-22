<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;
    protected $table = 'attendance_logs';
    public $timestamps = false; // Using changed_at column

    protected $fillable = [
        'employee_id', 'project_id', 'attendance_date', 'old_value', 'new_value', 'field_type', 'changed_by'
    ];

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}