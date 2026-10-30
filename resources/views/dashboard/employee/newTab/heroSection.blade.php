<style>
    .hero-section {
        display: flex;
        gap: 20px;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .btn-infox {
        box-shadow: 1px 1px 2px #ccc;
        width: 100%;
        background: #fff;
        padding: 7px;
    }

    .hero_box {
        width: calc(25% - 20px);
    }

    .hero_inBox {
        padding-top: 30px;
    }

    .title_box {
        border: #d1d6dc 1px solid;
        border-radius: 10px;
        background: #ffffff;
        padding: 10px;
        display: block
    }

    .title_box #title {
        position: relative;
        top: -2em;
        margin-left: 10%;
        display: inline-block;
        background-color: white;
        padding: 10px;
        width: 80%;
        text-align: center;
        box-shadow: 1px 1px 3px #ccc;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 500;
    }

    .count_dash {
        text-align: center;
        font-size: 35px;
    }
    
        .btn-warning {
    color: #fff !important;
}

.btn-warning:hover {
    color: #000 !important;
}

@media (max-width: 1024px) {
    .hero_box {
        width: calc(50% - 20px);
    }
}

@media (max-width: 768px) {
    .hero_box {
        width: 100%;
    }
    

}

</style>
<div class="hero-section">
    <div class="hero_box">
        <!-- View Bill Status -->
        <a href="javascript:void(0)" onclick="openStatusModal('billStatus')"
            title="View detailed status and progress of the bill" class="btn btn-info w-100 rounded">
            <i class="icons icon-eye mr-2"></i>
            {{ __('app.viewBillStatus') }}
        </a>
    </div>

    @if ($instantDebitPermission == 'all')
        <div class="hero_box"> 
            <!-- Add Instant Debit -->
            <button type="button" id="instantDebit" class="btn btn-warning text-white w-100 rounded">
                <i class="icons icon-minus mr-2"></i>
                {{ __('app.addinstantDebitAmount') }}
            </button>
        </div>
    @endif

    <div class="hero_box">
        <!-- Approve Bills -->
        <a href="{{ route('bills.bill-approval') }}" class="btn btn-success w-100 rounded">
            <i class="icons icon-check mr-2"></i>
            {{ __('app.approveBills') }}
        </a>
    </div>

    <div class="hero_box">
        <!-- Track Budget vs Actual -->
        <a href="{{ route('budget.index') }}" class="btn btn-info w-100 rounded">
            <i class="icons icon-chart mr-2"></i>
            {{ __('app.trackBudgetVsActual') }}
        </a>
    </div>
</div>

<div class="hero-section">

    <div class="hero_box">

        <div class="hero_inBox">
            <br>
            <a href="{{ route('vendor-credits.show-abstract') }}" class="title_box" id="bill_to">
                <div id="title">{{ __('app.abstractApproval') }}</div>
                <div id="content">
                    <p class="count_dash font-weight-bold text-warning ">
                        {{ $abstractCount }}
                    </p>
                </div>
            </a>
        </div>
    </div>

    <div class="hero_box">

        <div class="hero_inBox">
            <br>
            <a href="javascript:void(0)" onclick="openStatusModal('intatUnder')" title="@lang('app.bill_under_prorocess_intat_description')"
                class="title_box" id="bill_to">
                <div id="title">{{ __('app.intat') }}</div>
                <div id="content">
                    <p class="count_dash font-weight-bold text-primary">
                        {{ $intatUnderProcessBill->intat }}
                    </p>
                </div>
            </a>
        </div>
    </div>
    <div class="hero_box">
        <div class="hero_inBox">
            <br>
            <a href="javascript:void(0)" onclick="openStatusModal('overshotUnder')" title="@lang('app.bill_under_prorocess_overshot_description')"
                class="title_box" id="bill_to">
                <div id="title">{{ __('app.overshot') }}</div>
                <div id="content">
                    <p class="count_dash font-weight-bold text-danger">
                        {{ $intatUnderProcessBill->overshot }}
                    </p>
                </div>
            </a>
        </div>
    </div>
    <div class="hero_box">
        <div class="hero_inBox">
            <br>
            <a href="javascript:void(0)" onclick="openStatusModal('withAccount')" class="title_box" id="bill_to">
                <div id="title">{{ __('app.withAccount') }}</div>
                <div id="content">
                    <p class="count_dash font-weight-bold text-success">
                        {{ $tatCount + $overShotCount }}
                    </p>
                </div>
            </a>
        </div>
    </div>

</div>
