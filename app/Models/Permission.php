<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['role_id', 'permission_name'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
