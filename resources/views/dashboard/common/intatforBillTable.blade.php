<x-table id="intatforBill">
    <x-slot name="thead">
        <th>@lang('app.purchase_bill_number')</th>
        <th>@lang('app.project')</th>
        <th>@lang('app.vendor')</th>
        <th>@lang('app.current_employee')</th>
        <th class="text-center">@lang('app.bill_date')</th>
        <th class="text-center">@lang('app.total')</th>

    </x-slot>
    {{-- {{ dd($tat) }} --}}
    @foreach ($tat as $items)
        <tr>
            <td>
                <a href="{{ route('bills.show', [$items->id]) }}">
                    {{ $items->purchase_bill_number }}
                </a>
            </td>
            <td>{{ $items->project_name . '-' . $items->sub_project_name }}</td>
            <td>{{ $items->primary_name }}</td>
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
