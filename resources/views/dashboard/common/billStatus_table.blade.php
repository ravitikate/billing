
                      <x-table id="overshotUnderz">
                          <x-slot name="thead">
                                   <th>@lang('app.purchase_bill_number')#</th>
                              <th>@lang('app.project')</th>
                              <th>@lang('app.vendor')</th>
                              <th>@lang('app.current_employee')</th>
                              <th >@lang('app.bill_date')</th>
                              <th >@lang('app.total')</th>
                          </x-slot>
{{-- @php
echo "<pre>";
print_r($overshot); exit;
@endphp --}}
                          @foreach ($overshot as $items)
                              <tr>
                                  <td>
                                      <a href="{{ route('bills.show', ['bill' => $items->bill_id]) }}">
                                          {{ $items->purchase_bill_number }}
                                      </a>
                                  </td>
                                  <td>{{ $items->project_name .'-'. $items->sub_project_name }}</td>
                                  <td>{{ $items->vendor_name }}</td>
                                  <td>
                                          {{ $items->current_employee }}
                                  </td>
                                  <td class="text-center">
                                      {{ \Carbon\Carbon::parse($items->updated_at)->format('d F Y') }}</td>
                                  <td class="text-right">
                                      {{ '₹' . \App\Helper\NumberFormat::formatIndianCurrency(round($items->net_payable)) }}
                                  </td>

                              </tr>
                          @endforeach
                      </x-table>
