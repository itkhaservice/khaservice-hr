<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeStatusHistory extends Model
{
    use HasFactory;
    protected $table = 'employee_status_history';

    protected $fillable = [
        'employee_id', 'old_status', 'new_status', 'change_date', 'note', 'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}