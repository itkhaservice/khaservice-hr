<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $table = 'projects';
    protected $fillable = ['stt', 'name', 'code', 'address', 'manager_id', 'headcount_required', 'status'];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'current_project_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}