<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">
<style>
    .mt-9 {
        margin-top: 48px;
    }
</style>
<!-- CREATE BILL
     START -->
<div class="bg-white rounded b-shadow-4 create-inv">

    <!-- HEADING START -->
    <div class="px-3 py-3 px-lg-4 px-md-4">
        <h4 class="mb-0 f-21 font-weight-normal ">@lang('purchase::app.menu.bill')</h4>
    </div>
    <!-- HEADING END -->
    <hr class="m-0 border-top-grey">
    <!-- FORM START -->
    <x-form class="c-inv-form" id="save-bill-data-form">

        <!-- BILL NUMBER, DATE, FREQUENCY START -->
        <div class="px-3 pt-3 row px-lg-4 px-md-4">
            <!-- BILL NUMBER START -->
            <div class="col-md-4">
               
                
                <div class="mb-4 form-group mb-lg-0 mb-md-0">
                    <x-forms.label class="mt-3" fieldId="bill_number" :fieldLabel="__('purchase::app.menu.billNumber')">
                    </x-forms.label>
                    <div class="input-group">
                        <input type="text" id="bill_number" name="bill_number"
                            class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                            placeholder="@lang('app.bill_number')">
                    </div>
            </div></div>



            <!-- BILL NUMBER END -->
            <div class="col-md-4" hidden>
                <div class="mb-4 form-group mb-lg-0 mb-md-0">
                    <x-forms.label class="mt-3" fieldId="reference_bill_no" :fieldLabel="__('app.reference_bill_no')">
                    </x-forms.label>
                    <div class="input-group">
                        <input type="text" id="reference_bill_no" name="reference_bill_no"
                            class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                            placeholder="@lang('app.reference_bill_no')">
                    </div>
                </div>
            </div>
            <!-- SELECT VENDOR -->
            @if ($purchaseVendorsDetails)
                <input type="hidden" id="vendor-id" name="vendor_id" value="{{ $purchaseVendorsDetails->id }}">
            @else
                <div class="col-md-4">
                    <x-forms.label class="mt-3" fieldId="vendor-id" :fieldLabel="__('purchase::app.selectVendor')">
                    </x-forms.label>
                    <div class="rounded select-others height-35">

                        @if ($vendorID)
                            <input type="hidden" name="vendor_id" value="{{ $vendorID }}">
                        @endif
                        <select @if ($vendorID) disabled @endif class="form-control select-picker"
                            id="vendor-id" name="vendor_id" data-live-search="true" data-size="8">
                            @if (isset($type) && $type == 'order')
                                <option value="{{ $purchaseOrder->vendor->id }}" selected>
                                    {{ $purchaseOrder->vendor->primary_name }}</option>
                            @elseif($purchaseVendors->isNotEmpty())
                                <option value="">--</option>
                                @foreach ($purchaseVendors as $purchaseVendor)
                                    <option @if ($vendorID == $purchaseVendor->id) selected @endif
                                        value="{{ $purchaseVendor->id }}">{{ $purchaseVendor->primary_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                </div>
            @endif
            <!-- SELECT VENDOR -->

            <!-- BILL DATE START -->
            <div class="col-md-4">
                <div class="mb-4 form-group mb-lg-0 mb-md-0">
                    <x-forms.label class="mt-3" fieldId="due_date" :fieldLabel="__('purchase::app.menu.billDate')">
                    </x-forms.label>
                    <div class="input-group">
                        <input type="text" id="bill_date" name="issue_date"
                            class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                            placeholder="@lang('placeholders.date')"
                            value="{{ now(company()->timezone)->translatedFormat(company()->date_format) }}" readonly>
                    </div>
                </div>
            </div>
            <!-- BILL DATE END -->

            <div class="col-md-4">
                <div class="mb-2 form-group c-inv-select">
                    <x-forms.select fieldId="purchase-order" :fieldLabel="__('purchase::app.purchaseOrder.purchaseWorkOrder')" fieldName="purchase_order_id"
                        search="true">
                        @if (isset($type) && $type == 'order')
                            <option option value="{{ $purchaseOrder->id }}" selected>
                                {{ $purchaseOrder->purchase_order_number }}</option>
                        @else
                            <option value="">--</option>
                        @endif
                    </x-forms.select>
                </div>
            </div>
        </div>
        <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY END -->

        <div class="px-3 py-3 row px-lg-4 px-md-4">

        </div>

        <div id="sortable"></div>


        <hr class="m-0 border-top-grey">

        <div id="fileInputs">
            <div class="file-group">
                <div class="px-3 pt-3 row px-lg-4 px-md-4">
                    <div class="col-md-4">
                        <div class="mb-4 form-group mb-lg-0 mb-md-0">
                            <label class="f-14 text-dark-grey mb-12 mt-3" data-label="" for="file_titles">Name of
                                file</label>
                            <div class="input-group">
                                <input type="text"
                                    class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                                    id="file_titles" name="file_titles[]" placeholder="Name of file" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-4 form-group mb-lg-0 mb-md-0">
                            <label class="f-14 text-dark-grey mb-12 mt-3" data-label="" for="files">
                                Document</label>
                            <div class="input-group">
                                <input type="file"
                                    class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                                    id="files" name="files[]">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <button type="button" class="btn-danger mt-9 remove-btn"
                            onclick="removeFileInput(this)">Remove</button>
                    </div>
                </div>

            </div>
        </div>
        <div class="px-3 pt-3 row px-lg-4 px-md-4">
            <div class="col-md-12">
                <button type="button" class="btn btn-info" onclick="addFileInput()">Add More</button><br>
            </div>
        </div>

        <br>





        <hr class="m-0 border-top-grey">


        <!-- NOTE AND TERMS AND CONDITIONS START -->
        <div class="flex-wrap px-3 py-3 d-flex px-lg-4 px-md-4">
            <div class="p-0 mb-3 col-md-6 col-sm-12 c-inv-note-terms mb-lg-0 mb-md-0">
                <x-forms.label fieldId="" class="" :fieldLabel="__('modules.invoices.note')">
                </x-forms.label>
                <textarea class="form-control" name="note" id="note" rows="4" placeholder="@lang('placeholders.invoices.note')"></textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <p id="error_message" style=" color: red;text-align:center; font-size:18px"></p>
            </div>
        </div>
        <!-- CANCEL SAVE SEND START -->
        <x-form-actions class="c-inv-btns d-block d-lg-flex d-md-flex">
            <div class="mb-3 d-flex mb-lg-0 mb-md-0">

                <div class="mr-3 inv-action dropup">
                    <button class="btn-primary dropdown-toggle" type="button" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        @lang('app.save')
                        <span><i class="text-white fa fa-chevron-up f-15"></i></span>
                    </button>
                    <!-- DROPDOWN - INFORMATION -->
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuBtn" tabindex="0">
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:;"
                                data-type="abstract">
                                <i class="mr-2 fa fa-save f-w-500 f-11"></i> @lang('app.saveAsAbstract')
                            </a>
                        </li>
                        <!--<li>-->
                        <!--    <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"-->
                        <!--        data-type="open">-->
                        <!--        <i class="mr-2 fa fa-paper-plane f-w-500 f-12"></i> @lang('purchase::modules.purchaseBill.saveAsOpen')-->
                        <!--    </a>-->
                        <!--</li>-->
                    </ul>
                </div>
            </div>

            <!--        <div class="mr-3 inv-action">-->
            <!--    <button class="btn-primary rounded f-14 p-2 mr-3 save-form" data-type="abstract">-->
            <!--        <i class="mr-2 fa fa-save f-w-500 f-11"></i> @lang('app.saveAsAbstract')-->
            <!--    </button>-->
            <!--<button class="btn btn-success save-form" data-type="open">-->
            <!--    <i class="mr-2 fa fa-paper-plane f-w-500 f-12"></i> @lang('purchase::modules.purchaseBill.saveAsOpen')-->
            <!--</button>-->
            <!--</div>-->


            <x-forms.button-cancel :link="isset($vendorID) ? route('vendors.show', $vendorID) . '?tab=bills' : route('bills.index')" class="border-0">@lang('app.cancel')
            </x-forms.button-cancel>

        </x-form-actions>
        <!-- CANCEL SAVE SEND END -->

    </x-form>
    <!-- FORM END -->
</div>

<div class="modal fade" id="ra-breakup-modal" class="breakup" tabindex="-1" aria-labelledby="raBreakupModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="raBreakupModalLabel">RA Bill History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>

            </div>
            <div class="modal-body" id="ra-breakup-body">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>
</div>



<script>
    $('body').on('click', '.close', function() {
        console.log('Closing modal using jQuery Bootstrap method...');
        $('#ra-breakup-modal').modal('hide');
    });



    function showBreakup(productId, poId) {
        $.ajax({
            url: '{{ route('getBreakUp') }}',
            method: 'GET',
            data: {
                productId: productId,
                poId: poId
            },
            success: function(response) {
                const data = response.data;

                // Start table structure
                let modalContent = `
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Reference ID</th>
                            <th>Bill Number</th>
                            <th>Bill Date</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

                data.forEach(item => {
                    modalContent += `
                    <tr>
                        <td>${item.reference_bill_no}</td>
                        <td>${item.bill_number}</td>
                        <td>${item.bill_date}</td>
                        <td>${item.quantity}</td>
                    </tr>
                `;
                });

                modalContent += `
                    </tbody>
                </table>
            `;
                $('#ra-breakup-modal .modal-body').html(modalContent);

                // Create a modal instance and show it
                const myModal = new bootstrap.Modal(document.getElementById('ra-breakup-modal'));
                myModal.show();

                // Optionally dismiss after 10 seconds (for example):
                // setTimeout(function() {
                //     myModal.hide(); // This will hide the modal after 10 seconds
                // }, 10000);

            },
            error: function() {
                alert('Failed to load RA Bill Breakup data.');
            }
        });
    }

    $(document).ready(function() {

        var type = "{{ isset($type) ? $type : '' }}";

        if (type == 'order') {
            let url = "{{ route('purchase_order_products') }}";
            let purchaseOrderId = "{{ $purchaseOrder ? $purchaseOrder->id : '' }}";
            addProduct(purchaseOrderId, url);
        }

        let defaultImage = '';
        let lastIndex = 0;

        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });

       const today = new Date();

        const dp1 = datepicker('#bill_date', {
            position: 'bl',
            minDate: today, // disables all dates before today
            ...datepickerConfig
        });

        calculateTotal();
        var vendorId = "{{ $purchaseOrder ? $purchaseOrder->vendor->id : '' }}";

        function vendorOrders(vendorId) {
            let url = "{{ route('purchase_orders') }}";
            let prefix =
                "{{ $purchaseSetting->purchase_order_prefix }}{{ $purchaseSetting->purchase_order_number_seprator }}{{ $zero }}";

            $.easyAjax({
                url: url,
                type: "GET",
                data: {
                    vendor_id: vendorId
                },
                success: function(response) {
                    var options = [];
                    var rData = response.data;
                    var purchaseOrderId = $('#purchase-order').val();

                    $.each(rData, function(index, value) {
                        let selected = (purchaseOrderId == value.id) ? "selected" : "";
                        let purchaseOrderNumber = value.po_number ? value.po_number : 'N/A';
                        let selectData =
                            `<option ${selected} value="${value.id}">${purchaseOrderNumber}</option>`;
                        options.push(selectData);
                    });

                    $('#purchase-order').html('<option value="">--</option>' + options.join(''));
                    $('#purchase-order').selectpicker('refresh');
                }
            });
        }

        $('#vendor-id').change(function() {
            let vendorId = $(this).val();
            vendorOrders(vendorId);
        });

        let vendor_id = $('#vendor-id').val();
        if (vendor_id) {
            vendorOrders(vendor_id);
        }

        function calculateRowAmount(row) {
            const quantity = parseFloat(row.find('.quantity').val()) || 0;
            const price = parseFloat(row.find('.cost_per_item').val()) || 0;
            const tax = parseFloat(row.find('.taxes').val()) || 0;

            const baseAmount = quantity * price;
            const taxAmount = (baseAmount * tax) / 100;
            const totalAmount = baseAmount + taxAmount;

            row.find('.amount-html').text(totalAmount.toFixed(2));
            row.find('.amount').val(totalAmount.toFixed(2));
        }

        $('#save-bill-data-form').on('keyup change input',
            '.quantity, .cost_per_item, .discount_value, .taxes, .type, #discount_type, #calculate_tax',
            function() {
                var quantity = parseFloat($(this).closest('.item-row').find('.quantity').val()) || 0;
                var perItemCost = parseFloat($(this).closest('.item-row').find('.cost_per_item').val()) ||
                    0;

                // if (quantity <= 0) {
                //     Swal.fire({
                //         icon: 'warning',
                //         text: "Quantity cannot be zero or empty!",
                //         customClass: {
                //             confirmButton: 'btn btn-primary'
                //         },
                //         buttonsStyling: false
                //     });
                //     $(this).val(0);
                //     quantity = 0;
                // }

                var amount = quantity * perItemCost;
                $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
                $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

                const row = $(this).closest('tr');
                calculateRowAmount(row);
                calculateTotal();
            });

        calculateTotal();
        init(RIGHT_MODAL);

        $('#purchase-order').change(function() {
            let purchaseOrderId = $(this).val();
            let url = "{{ route('purchase_order_products') }}";

            if ($('#bill-desc-data').length) {
                $('#bill-desc-data').remove();
            }
            if ($('.c-inv-total').length) {
                $('.c-inv-total').remove();
            }

            addProduct(purchaseOrderId, url);
        });

        function addProduct(purchaseOrderId, url) {
            $.easyAjax({
                url: url,
                type: "GET",
                data: {
                    purchase_order_id: purchaseOrderId
                },
                blockUI: true,
                success: function(response) {
                    $(response.view).hide().appendTo("#sortable").fadeIn(500);
                    calculateTotal();
                }
            });
        }

        $('.save-form').click(function(event) {
            var isValid = true;

            // $('.quantity').each(function() {
            //     var quantity = parseFloat($(this).val()) || 0;
            //     if (quantity <= 0) {
            //         isValid = false;
            //         Swal.fire({
            //             icon: 'error',
            //             text: "All items must have a quantity greater than zero!",
            //             customClass: { confirmButton: 'btn btn-primary' },
            //             buttonsStyling: false
            //         });
            //         return false; // Break loop
            //     }
            // });

            // if (!isValid) {
            //     event.preventDefault();
            //     return false;
            // }

            var type = $(this).data('type');
            var url = "{{ route('bills.billstore') }}";

            if (KTUtil.isMobileDevice()) {
                $('.desktop-description').remove();
            } else {
                $('.mobile-description').remove();
            }

            calculateTotal();

            var discount = $('#discount_amount').html();
            var total = $('.sub-total-field').val();

            if (parseFloat(discount) > parseFloat(total)) {
                Swal.fire({
                    icon: 'error',
                    text: "{{ __('messages.discountExceed') }}",
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                });
                return false;
            }

            $.easyAjax({
                url: "{{ route('bills.billstore') }}" + "?type=" + type,
                container: '#save-bill-data-form',
                type: "POST",
                blockUI: true,
                redirect: true,
                file: true,
                data: $('#save-bill-data-form').serialize(),
                success: function(response) {
                    console.log(response);
                    if (response.error) {
                        $('#error_message').text(response.error);
                    } else {
                        window.location.href =
                            "{{ route('vendor-credits.show-abstract') }}";
                    }

                }
            });
        });
    });
</script>
<script>
    function addFileInput() {
        const group = document.createElement('div');
        group.className = 'file-group';
        group.innerHTML = `<div class="px-3 pt-3 row px-lg-4 px-md-4">
                    <div class="col-md-4">
                        <div class="mb-4 form-group mb-lg-0 mb-md-0">
                            <label class="f-14 text-dark-grey mb-12 mt-3" data-label="" for="file_titles">Name of
                                file</label>
                            <div class="input-group">
                                <input type="text"
                                    class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                                    id="file_titles" name="file_titles[]" placeholder="Name of file" autocomplete="off" >
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-4 form-group mb-lg-0 mb-md-0">
                            <label class="f-14 text-dark-grey mb-12 mt-3" data-label="" for="files"> Document</label>
                            <div class="input-group">
                                <input type="file"
                                    class="p-0 px-6 text-left rounded position-relative text-dark font-weight-normal form-control height-35 f-15"
                                    id="files" name="files[]" >
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <button type="button" class="btn-danger mt-9 remove-btn"
                            onclick="removeFileInput(this)">Remove</button>
                    </div>
                </div>`;
        document.getElementById('fileInputs').appendChild(group);
    }

    function removeFileInput(button) {
        const fileGroup = button.closest('.file-group');
        if (fileGroup) {
            fileGroup.remove();
        }
    }
</script>
