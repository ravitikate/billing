<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id', 'item_name', 'wbs','quantity', 'product_category_id','tax', 'cost_per_item', 'amount', 'description', 'invoice_item_image_url'
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }
}
