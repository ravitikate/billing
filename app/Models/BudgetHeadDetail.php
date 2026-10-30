<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetHeadDetail extends Model
{
    use HasFactory;

    protected $table = 'budget_head_details';

    protected $fillable = [
        'budget_head_id',
        'wps',
        'budget_head',
        'cost'
    ];

    public function budgetHead()
    {
        return $this->belongsTo(BudgetHead::class, 'budget_head_id');
    }
}
