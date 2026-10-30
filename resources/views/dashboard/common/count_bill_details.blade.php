 <div class="bg-white p-20 rounded b-shadow-4">
     <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
         @lang('app.bill_details')
     </h5>
     <div class="d-flex flex-wrap gap-2">
         <a href="{{ $paidCount != '0' ? route('bills.index') . '?status=paid' : 'void:javascript()' }}"
             title="@lang('app.paid_bills_description')">
             <p class="mb-0 f-21 font-weight-bold text-success d-grid mr-5">
                 {{ $paidCount }}<span class="f-12 font-weight-normal text-lightest">
                     @lang('app.paid_bills')</span>
             </p>
         </a>
         <a href="void:javascript()" title="@lang('app.in_process_bills_description')">
             <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                 {{ $tatCount + $overShotCount + $abstractCount + $pendingCount }}
                 {{-- {{ $unpaidBillCount }} --}}
                 <span class="f-12 font-weight-normal text-lightest">
                     {{-- @lang('app.unpaid_bills')  --}}
                     @lang('app.in_process_bills')
                 </span>
             </p>
             {{-- </a>
             <a href="void:javascript()" onclick="scrollToSection('delay_bill_count')" title="@lang('app.delay_bills_description')">
                 <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                     {{ $delayBillCount }}<span class="f-12 font-weight-normal text-lightest">
                         @lang('app.delay_bills') </span>
                 </p>
             </a> --}}
             <a href="javascript:void(0)" onclick="openStatusModal('intatStatus')" title="@lang('app.intat_forbill_description')">
                 <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                     {{ $tatCount + $intatUnderProcessBill->intat }} <span
                         class="f-12 font-weight-normal text-lightest">
                         @lang('app.intat')</span>
                 </p>
             </a>
             <a href="javascript:void(0)" onclick="openStatusModal('overshotStatus')" title="@lang('app.overshot_forbill_description')">
                 <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                     {{ $overShotCount + $intatUnderProcessBill->overshot }}
                     <span class="f-12 font-weight-normal text-lightest">
                         @lang('app.overshot') </span>
                 </p>
             </a>
     </div>
 </div>
