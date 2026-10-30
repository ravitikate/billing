<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
       'budget_number', 'valid_till', 'currency_id', 'project_id',
        'sub_total', 'total', 'discount_value', 'discount_type', 'calculate_tax', 'budget_description'
    ];
    public function items()
    {
        return $this->hasMany(BudgetItem::class);
    }


}
