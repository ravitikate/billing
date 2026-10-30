<div class="bg-white p-20 rounded b-shadow-4">
     <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
            @lang('app.bill_under_prorocess')
        </h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('vendor-credits.show-abstract') }}" title="@lang('app.bill_under_prorocess_abstract_description')">
                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                    {{ $abstractCount }}<span class="f-12 font-weight-normal text-lightest">
                        @lang('app.abstract')</span>
                </p>
            </a>
            <a href="{{ route('bills.bill-approval') . '?status=pending' }}" title="@lang('app.bill_under_prorocess_pending_description')">
                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                    {{ $pendingCount }} <span class="f-12 font-weight-normal text-lightest">
                        @lang('app.pending') </span>
                </p>
            </a>
            <a  href="javascript:void(0)"  onclick="openStatusModal('intatUnder')"  title="@lang('app.bill_under_prorocess_intat_description')">
                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                    {{ $intatUnderProcessBill->intat }} <span class="f-12 font-weight-normal text-lightest">
                        @lang('app.intat')</span>
                </p>
            </a>
            <a href="javascript:void(0)" onclick="openStatusModal('overshotUnder')"  title="@lang('app.bill_under_prorocess_overshot_description')">
                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                    {{ $intatUnderProcessBill->overshot }}
                    <span class="f-12 font-weight-normal text-lightest">
                        @lang('app.overshot') </span>
                </p>
            </a>
        </div>
</div>
