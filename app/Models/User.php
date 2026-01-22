<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'username', 'password', 'fullname', 'email', 'role', 'status', 'employee_id'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Custom helper to check roles
    public function isAdmin() { return $this->role === 'admin'; }
    public function isManager() { return $this->role === 'manager'; }
}