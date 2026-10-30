     <div class="col-xl-6 col-lg-6 col-md-6">
         <div class="card border-0 b-shadow-4 mb-3 e-d-info">
             @php($isVendorDashboard = in_array('vendor', user_roles()))
             <x-cards.data :title="__('app.abstract_bills')" padding="false" otherClasses="h-200">
                 <x-slot name="action">
                     <a href="{{ route('vendor-credits.show-abstract') }}" class="btn btn-xs btn-primary">
                         {{ __('app.view_all') }}
                     </a>
                 </x-slot>
                 @if ($abstract->isEmpty())
                     <x-cards.no-record icon="tasks" :message="__('messages.noRecordFound')" />
                 @else
                     <x-table id="abstract">
                         <x-slot name="thead">
                             <th>@lang('app.bill_number')#</th>
                             @if ($isVendorDashboard)
                                 <th>@lang('app.project')</th>
                             @else
                                 <th>@lang('app.vendor')</th>
                                 <th>@lang('app.order_type')</th>
                             @endif
                             <th class="text-center">@lang('app.bill_date')</th>
                             <th class="text-center">@lang('app.total')</th>
                             @if ($isVendorDashboard)
                                 <th>@lang('app.status')</th>
                             @endif
                         </x-slot>

                         @foreach ($abstract as $items)
                             <tr>
                                 <td>
                                     <a href="{{ route('bills.show', [$items->id]) }}">
                                         {{ $items->purchase_bill_numberx }}
                                     </a>
                                 </td>
                                 @if ($isVendorDashboard)
                                     <td>{{ $items->project_name }}</td>
                                 @else
                                     <td>{{ $items->primary_name }}</td>
                                     <td>
                                         @if ($items->order_type == 'purchase_order')
                                             Purchase Order
                                         @elseif($items->order_type == 'work_order')
                                             Work Order
                                         @else
                                             {{ $items->order_type }}
                                         @endif
                                     </td>
                                 @endif
                                 <td class="text-center">
                                     {{ \Carbon\Carbon::parse($items->updated_at)->format('d F Y') }}</td>
                                 <td class="text-right">
                                     {{ '₹' . \App\Helper\NumberFormat::formatIndianCurrency(round($items->net_payable)) }}
                                 </td>
                                 @if ($isVendorDashboard)
                                     <td>
                                         @if ($items->status === 'draft')
                                             <button type="button" class="btn btn-primary btn-xs"
                                                 onclick="sendInvoiceFromVendorDashboard({{ $items->id }})">
                                                 {{ __('app.sendInvoice') }}
                                             </button>
                                         @else
                                             {{ ucfirst(str_replace('_', ' ', $items->status)) }}
                                         @endif
                                     </td>
                                 @endif
                             </tr>
                         @endforeach
                     </x-table>
                 @endif
             </x-cards.data>
         </div>
     </div>

     @if ($isVendorDashboard)
         <div class="modal fade" id="dashboardInvoiceSendVendor" tabindex="-1" aria-hidden="true">
             <div class="modal-dialog">
                 <div class="modal-content">
                     <form id="dashboardInvoiceSendVendorForm">
                         @csrf
                         <div class="modal-header">
                             <h5 class="modal-title">{{ __('app.sendInvoice') }}</h5>
                             <button type="button" class="close" data-dismiss="modal" aria-label="@lang('app.close')">
                                 <span aria-hidden="true">&times;</span>
                             </button>
                         </div>
                         <div class="modal-body">
                             <input type="hidden" name="bill_id" id="dashboard_bill_id_invoice">
                             <div class="form-group">
                                 <label for="dashboard_invoiceno">{{ __('app.invoiceno') }}</label>
                                 <input type="text" class="form-control" name="invoiceno" id="dashboard_invoiceno" required>
                                 <div class="invalid-feedback" id="dashboard_invoiceno_feedback">
                                     @lang('validation.required', ['attribute' => __('app.invoiceno')])
                                 </div>
                             </div>
                         </div>
                         <div class="modal-footer">
                             <button type="submit" class="btn btn-primary">{{ __('app.submit') }}</button>
                             <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('app.close')</button>
                         </div>
                     </form>
                 </div>
             </div>
         </div>

         <script>
             function sendInvoiceFromVendorDashboard(billId) {
                 $('#dashboard_bill_id_invoice').val(billId);
                 $('#dashboardInvoiceSendVendorForm')[0].reset();
                 $('#dashboard_bill_id_invoice').val(billId);
                 $('#dashboard_invoiceno_feedback').hide();
                 $('#dashboardInvoiceSendVendor').modal('show');
             }

             $('#dashboardInvoiceSendVendorForm').on('submit', function (event) {
                 event.preventDefault();
                 const invoiceNumber = $('#dashboard_invoiceno').val().trim();

                 if (!invoiceNumber) {
                     $('#dashboard_invoiceno_feedback').show();
                     return;
                 }

                 $.ajax({
                     url: '{{ route('update_status_pending') }}',
                     method: 'POST',
                     data: $(this).serialize(),
                     success: function (response) {
                         if (response.success) {
                             location.reload();
                             return;
                         }

                         Swal.fire('Error!', response.message, 'error');
                     },
                     error: function () {
                         Swal.fire('Error!', 'Unable to send the invoice. Please try again.', 'error');
                     }
                 });
             });
         </script>
     @endif
