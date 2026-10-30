     <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                            <x-cards.data :title="__('app.paid_bills')" padding="false" otherClasses="h-200">
                                <x-slot name="action">
                                    <a href="{{ route('bills.index', ['status' => 'paid']) }}"
                                        class="btn btn-xs btn-primary">
                                        {{ __('app.view_all') }}
                                    </a>
                                </x-slot>
                                @if ($paid->isEmpty())
                                    <x-cards.no-record icon="tasks" :message="__('messages.noRecordFound')" />
                                @else
                                    <x-table id="paid">
                                        <x-slot name="thead">
                                            <th>@lang('app.purchase_bill_number')#</th>
                                            <th>@lang('app.vendor')</th>
                                            <th>@lang('app.order_type')</th>
                                            <th class="text-center">@lang('app.bill_date')</th>
                                            <th class="text-center">@lang('app.total')</th>
                                        </x-slot>

                                        @foreach ($paid as $items)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('bills.show', [$items->id]) }}">
                                                        {{ $items->purchase_bill_numberx }}
                                                    </a>
                                                </td>
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
