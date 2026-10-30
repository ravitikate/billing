  {{-- Due Bills --}}
  <div class="col-xl-6 col-lg-6 col-md-6" id="delay_bill_count">
      <div class="card border-0 b-shadow-4 mb-3 e-d-info">
          <x-cards.data :title="__('app.delay_bill')" padding="false" otherClasses="h-200">
              <x-slot name="action">
                  
              </x-slot>
              @if ($delayBills->isEmpty())
                  <x-cards.no-record icon="tasks" :message="__('messages.noRecordFound')" />
              @else
                  <x-table id="due">
                      <x-slot name="thead">
                          <th>@lang('app.purchase_bill_number')#</th>
                          <th>@lang('app.vendor')</th>
                          <th>@lang('app.name')</th>
                          <th class="text-center">@lang('app.bill_date')</th>
                          <th class="text-center">@lang('app.total')</th>
                      </x-slot>
                      @foreach ($delayBills as $items)

                          <tr>
                              <td>
                                  <a href="{{ route('bills.show', [$items->id]) }}">
                                      {{ $items->purchase_bill_numberx }}
                                  </a>
                              </td>
                              <td>{{ $items->primary_name }}</td>
                              <td>
                                {{ $items->delayuname }}
                              </td>
                              <td class="text-center">
                                  {{ \Carbon\Carbon::parse($items->updated_at)->format('d F Y') }}</td>
                              <td class="text-right">
                                  {{ '₹' . \App\Helper\NumberFormat::formatIndianCurrency(round($items->net_payable)) }}
                              </td>
                          </tr>
                      @endforeach
                  </x-table>
              @endif
          </x-cards.data>
      </div>
  </div>
