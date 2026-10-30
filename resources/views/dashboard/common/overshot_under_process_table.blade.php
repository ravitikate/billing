
                      <x-table id="overshotUnderz">
                          <x-slot name="thead">
                                   <th>@lang('app.purchase_bill_number')#</th>
                              <th>@lang('app.project')</th>
                              <th>@lang('app.vendor')</th>
                              <th>@lang('app.current_employee')</th>
                              <th >@lang('app.bill_date')</th>
                              <th >@lang('app.total')</th>
                          </x-slot>

                          @foreach ($overshot as $items)
                              <tr>
                                  <td>
                                      <a href="{{ route('bills.show', [$items->id]) }}">
                                          {{ $items->purchase_bill_number }}
                                      </a>
                                  </td>
                                  <td>{{ $items->project_name .'-'. $items->sub_project_name }}</td>
                                  <td>{{ $items->primary_name }}</td>
                                  <td>
                                          {{ $items->name }}
                                  </td>
                                  <td class="text-center">
                                      {{ \Carbon\Carbon::parse($items->updated_at)->format('d F Y') }}</td>
                                  <td class="text-right">
                                      {{ '₹' . \App\Helper\NumberFormat::formatIndianCurrency(round($items->net_payable)) }}
                                  </td>

                              </tr>
                          @endforeach
                      </x-table>
