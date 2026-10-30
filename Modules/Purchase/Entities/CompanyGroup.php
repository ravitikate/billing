<?php

namespace Modules\Purchase\Entities;

use App\Models\BaseModel;
use App\Models\CompanyAddress;
use App\Models\Currency;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
 

class CompanyGroup extends BaseModel
{
    protected $table = 'company_group'; // Only if different
}

