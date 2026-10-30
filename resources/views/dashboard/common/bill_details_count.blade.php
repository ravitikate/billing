<div class="bg-white p-20 rounded b-shadow-4">
     <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
            @lang('app.bill_detail')
        </h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="javascript:void(0)" onclick="openStatusModal('billStatus')" title="@lang('app.bill_status_description')">
                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                    {{ $bill_status }}<span class="f-12 font-weight-normal text-lightest">
                        @lang('app.bill_status')</span>
                </p>
            </a>
            <a href="{{ route('bills.bill-approval') }}" title="@lang('app.bill_pending_approval_description')">
                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                    {{ $approve_bill }} <span class="f-12 font-weight-normal text-lightest">
                        @lang('app.approve_bill') </span>
                </p>
            </a>
            <a  href="{{ route('bills.review-action') }}"  title="@lang('app.bill_review_required_description')">
                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                    {{ $review_action }} <span class="f-12 font-weight-normal text-lightest">
                        @lang('app.review_action')</span>
                </p>
            </a>

        </div>
</div>
