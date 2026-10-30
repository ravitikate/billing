<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetHeadName extends Model
{
    use HasFactory;

    protected $table = 'budget_head_name';

    protected $fillable = [
        'id',
        'wbs',
        'name'
    ];

    // public function subProject()
    // {
    //     return $this->belongsTo(SubProject::class, 'sub_project_id');
    // }

    // public function project()
    // {
    //     return $this->belongsTo(Project::class, 'project_id');
    // }

    // public function details()
    // {
    //     return $this->hasMany(BudgetHeadDetail::class, 'budget_head_id');
    // }
}
