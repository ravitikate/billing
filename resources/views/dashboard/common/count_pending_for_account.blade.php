<div class="bg-white p-20 rounded b-shadow-4">
     <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
            @lang('app.pending_for_account')
        </h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="javascript:void(0)" onclick="openStatusModal('intat')"  title="@lang('app.in_tat_bills_pending_for_account_description')">
                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                    {{ $tatCount }}<span class="f-12 font-weight-normal text-lightest">
                        @lang('app.intat')</span>
                </p>
            </a>
            <a href="javascript:void(0)" onclick="openStatusModal('overshot')"  title="@lang('app.overshot_bills_pending_for_account_description')">
                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                    {{ $overShotCount }}
                    <span class="f-12 font-weight-normal text-lightest">
                        @lang('app.overshot') </span>
                </p>
            </a>
        </div>
</div>
