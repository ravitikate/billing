<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalItemsBy extends Model
{
    protected $table = 'approval_items_by';

    public function approvalBill(): BelongsTo
    {
        return $this->belongsTo(ApprovalBillsBy::class, 'approval_bill_id');
    }
}
