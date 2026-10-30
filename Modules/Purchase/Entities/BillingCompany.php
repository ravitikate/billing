<?php

namespace Modules\Purchase\Entities;

use App\Models\BaseModel;
use App\Models\CompanyAddress;
use App\Models\Currency;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
 

class BillingCompany extends BaseModel
{
    protected $table = 'billing_company'; // Only if different
}
