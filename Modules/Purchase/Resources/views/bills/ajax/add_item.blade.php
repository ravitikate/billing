<div id="bill-desc-data">
    <table class="table table-bordered table-striped">
        <thead class="thead-light">
            <tr class="text-dark-grey font-weight-bold">
                <th width="30%">Description</th>
                <th width="17%">WBS</th>
                <th width="10%">Total</th>
                <th width="7%">Quantity</th>
                <th width="7%">Unit Price</th>
                <th width="7%">GST%</th>
                <th width="10%">Basic Amount</th>
                <th width="10%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseItems as $item)
            
                @php
             
                    $remainingQty = number_format((float)$item->quantity - (float)$item->total_quantity, 2, '.', '');
                @endphp
                <tr class="item-row" id="bill-desc-{{ $item->id }}">
                    @if ($item->quantity == 0)
                        <td>
                            <p>{{ $item->item_name }}</p>
                            <input type="hidden" name="item_name[]" value="{{ $item->item_name }}">
                            <input type="hidden" name="product_id[]" value="{{ $item->product_id }}">
                            <input type="hidden" name="item_id[]" value="{{ $item->id }}">
                            <input type="hidden" name="department_id[]" value="{{ $item->department_id }}">
                            <input type="hidden" name="retention_amount[]" value="{{ $item->retention_amount }}">
                            <input type="hidden" name="budget_head_id" value="{{ $item->budget_head_id }}">
                            <input type="hidden" name="purchase_order_id" value="{{ $item->purchaseOrder->id }}">
                        </td>
                        <td>
                            <p>{{ $item->wbs }}</p>
                            <input type="hidden" name="wbs[]" value="{{ $item->wbs }}">
                            <input type="hidden" name="budget_head[]" value="{{ $item->budget_head }}">
                        </td>
                        <td colspan="6">
                            <input type="hidden" name="total_quantity[]" value="0">
                            <input type="hidden" name="remaining_quantity[]" value="0">
                            <input type="hidden" name="cost_per_item[]" value="{{ $item->unit_price }}">
                            <input type="hidden" name="quantity[]" value="0">
                            <input type="hidden" name="taxes[]" value="{{ $item->taxes_percent }}">
                            <input type="hidden" name="tax_amount[]" value="0">
                            <input type="hidden" name="product_amount[]" value="0">
                            <input type="hidden" name="unit[]" value="0" >
                            <input type="hidden" name="unit_id[]" value="0" >
                             
                        </td>
                    @else
                        <td>
                            <p>{{ $item->item_name }}</p>
                            <input type="hidden" name="item_name[]" value="{{ $item->item_name }}">
                            <input type="hidden" name="product_id[]" value="{{ $item->product_id }}">
                            <input type="hidden" name="item_id[]" value="{{ $item->id }}">
                            <input type="hidden" name="department_id[]" value="{{ $item->department_id }}">
                            <input type="hidden" name="retention_amount[]" value="{{ $item->retention_amount }}">
                            
                            <input type="hidden" name="budget_head_id" value="{{ $item->budget_head_id }}">
                            <input type="hidden" name="purchase_order_id" value="{{ $item->purchaseOrder->id }}">
                           
                        </td>
                        <td>
                            <p>{{ $item->wbs }}</p>
                            <input type="hidden" name="wbs[]" value="{{ $item->wbs }}">
                            <input type="hidden" name="budget_head[]" value="{{ $item->budget_head }}">
                        </td>
                       <td>
                            <div>Quantity: {{ $item->quantity }}</div> 
                            <div class="ra-bill-link text-primary" style="cursor:pointer;" onclick="showBreakup({{ $item->product_id }},{{ $item->purchaseOrder->id }})">RA Bill History</div>
                            <p class="my-0"><span class="text-success">Remaining:</span> {{ $remainingQty }}</p>
                            <input type="hidden" name="total_quantity[]" id="qty-{{ $item->id }}" value="{{ $item->quantity }}">
                            <input type="hidden" name="remaining_quantity[]" id="rem-{{ $item->id }}" value="{{ $remainingQty }}">
                        </td>

                        <td>
                            @if ($remainingQty > 0)
                                <input type="text" class="form-control text-right quantity" name="quantity[]" data-item-id="{{ $item->id }}" value="" placeholder="0"  max="{{ $remainingQty }}" oninput="validateQuantity(this)">
                            @else
                                <input type="hidden" class="quantity" name="quantity[]" placeholder="0" value="0">
                            @endif
                        </td>
                        <td>
                            <div>{{ $item->unit_price }} ({{ strtolower($item->unit->unit_type) }})</div>
                            <input type="hidden" class="cost_per_item" name="cost_per_item[]" value="{{ $item->unit_price }}" data-item-id="{{ $item->id }}">
                            <input type="hidden" class="cost_per_item" name="unit[]" value="{{$item->unit->unit_type}}" data-item-id="{{ $item->id }}">
                            <input type="hidden" class="cost_per_item" name="unit_id[]" value="{{ $item->unit_id }}" data-item-id="{{ $item->id }}">
                        </td>
                        <td>
                            <div class="select-others">
                                <!--@foreach ($item->taxes as $tax)-->
                                <!--    <span data-rate="{{ $tax->rate_percent }}">{{ $tax->rate_percent }}%</span><br>-->
                                <!--@endforeach-->
                                <span data-rate="{{ $item->taxes_percent }}">{{ $item->taxes_percent }}%</span><br>
                            </div>
                            <span class="text-success">Amount:</span>
                            <span class="tax-amount" data-item-id="{{ $item->id }}">0.00</span>
                            <input type="hidden" class="taxes" name="taxes[]" value="{{ $item->taxes_percent }}">
                            <input type="hidden" class="taxes_amount" name="tax_amount[]" value="0">
                        </td>
                        <td class="text-right">
                            <span class="amount-html" data-item-id="{{ $item->id }}">0.00</span>
                            <input type="hidden" class="amount" name="product_amount[]" value="0" data-item-id="{{ $item->id }}">
                        </td>
                        <td class="text-right">
                            <span class="total-amount-html" data-item-id="{{ $item->id }}">0.00</span>
                        </td>
                    @endif
                </tr>
                <script>
    // function validateQuantity(input) {
    //     const maxQuantity = parseFloat(input.getAttribute('max'));
    //     let enteredQuantity = input.value.trim();

    //     if (!/^\d*\.?\d+$/.test(enteredQuantity)) {
    //         alert('Please enter a valid numeric value.');
    //         input.value = 0;
    //         return;
    //     }

    //     enteredQuantity = parseFloat(enteredQuantity);

    //     if (enteredQuantity < 1 || isNaN(enteredQuantity)) {
    //         alert('Quantity must be at least 1.');
    //         input.value = 0;
    //         return;
    //     }

    //     if (enteredQuantity > maxQuantity) {
    //         alert(`Quantity cannot exceed the remaining quantity of ${maxQuantity}.`);
    //         input.value = maxQuantity;
    //     }

    //     calculateTotal();
    // }
    
    
                    function validateQuantity(input) {
                        const maxQuantity = parseFloat(input.getAttribute('max'));
                        let enteredQuantity = input.value.trim();
                        enteredQuantity = parseFloat(enteredQuantity);
                        if (enteredQuantity > maxQuantity) {
                            alert(`Quantity cannot exceed the remaining quantity of ${maxQuantity}.`);
                            input.value = 0;
                        }
                        calculateTotal();
                    }

    function calculateTotal() {
        let subtotal = 0;
        let taxTotal = 0;

        $(".item-row").each(function () {
            const $row = $(this);
            const quantity = parseFloat($row.find(".quantity").val()) || 0;
            const unitPrice = parseFloat($row.find(".cost_per_item").val()) || 0;
            const amount = quantity * unitPrice;

            let taxPercent = 0;
            $row.find(".select-others span").each(function () {
                taxPercent += parseFloat($(this).data("rate")) || 0;
            });

            const taxAmount = (amount * taxPercent) / 100;
            const totalAmount = amount + taxAmount;

            // Update values
            $row.find(".amount-html").html(amount.toFixed(2));
            $row.find(".amount").val(amount.toFixed(2));
            $row.find(".tax-amount").html(taxAmount.toFixed(2));
            $row.find(".taxes_amount").val(taxAmount.toFixed(2));
            $row.find(".total-amount-html").html(totalAmount.toFixed(2));

            subtotal += amount;
            taxTotal += taxAmount;
        });

        $(".sub-total").html(subtotal.toFixed(2));
        $(".tax-total").html(taxTotal.toFixed(2));
        $(".total").html((subtotal + taxTotal).toFixed(2));
        $(".sub-total1").val(subtotal.toFixed(2));
        $(".tax-total1").val(taxTotal.toFixed(2));
        $(".total1").val((subtotal + taxTotal).toFixed(2));
    }
</script>
            @endforeach
        </tbody>
    </table>
</div>



<div class="d-flex px-lg-4 px-md-4 px-3 pb-3 c-inv-total">
    <table width="100%" class="text-right f-14">
        <tbody>
            <tr><td width="70%" class="right">Total</td>
                <td class="tax-total">0.00</td> 
                <input type="hidden" class="tax-total1 field" name="total_tax" value="0">
                <td class="border-top-0 sub-total">0.00</td>
                <input type="hidden" class="sub-total1 field" name="sub_total" value="0">
                <td class="text-dark total">0.00</td> 
                <input type="hidden" class="total1 field" name="total_amount" value="0">
            </tr>
        </tbody>
    </table>
</div>










