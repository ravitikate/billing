@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('content')
    <div class="content-wrapper">
        <div class="d-block d-lg-flex d-md-flex justify-content-between">
            <div id="table-actions" class="flex-grow-1 align-items-center mb-2 mb-lg-0 mb-md-0">
                <button id="companyGroup" type="button"
                    class="btn btn-primary rounded f-14 p-2 mr-3 float-left mb-2 mb-lg-0 mb-md-0 btnAdd" data-toggle="tooltip"
                    data-original-title="{{ __('app.billingcompany') }}">@lang('app.createbillingcompany')</button>
            </div>
        </div>
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white table-responsive">
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
        </div>
    </div>
    <div class="modal fade" id="companyGroupModal" tabindex="-1" aria-labelledby="companyGroupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('projects.submitbillingcompanyGroup') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createBudgetModalLabel">@lang('app.createbillingcompany')</h5>
                        <button type="button" class="closeMod" data-bs-dismiss="modal" aria-label="Close">x</button>
                    </div>

                    <div class="modal-body">
                        <div class="row border-top-grey ">
                            <div class="col-md-12">
                                <x-forms.select fieldId="companyName" fieldName="companyName" :fieldLabel="__('app.companyName')"
                                    :popover="__('app.companyName')" search="true">
                                    <option value="">--</option>
                                </x-forms.select>
                            </div>
                            <div class="col-sm-12">

                                <div class="form-group my-3">
                                    <label class="f-14 text-dark-grey mb-12" data-label="true"
                                        for="company_group">{{ __('app.billing_company') }}
                                        <sup class="f-14 mr-1">*</sup>

                                    </label>

                                    <input type="text" class="form-control height-35 f-14"
                                        placeholder="Enter a {{ __('app.billing_company') }} name" value=""
                                        id="billing_company" name="billing_company" required>

                                    <div class="d-block invalid-feedback" id="billing_company_msg"></div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary closeMod" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>

            </div>
        </div>
    </div>


    <div class="modal fade" id="editCompanyGroupModal" tabindex="-1" aria-labelledby="editCompanyGroupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editCompanyGroupForm" method="POST" action="{{ route('projects.updatebillingcompanyGroup') }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCompanyGroupModalLabel">Edit Billing Company</h5>
                        <button type="button" class="btn-close closeMod" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="col-md-12">
                            <x-forms.select fieldId="editcompanyName" fieldName="companyName" :fieldLabel="__('app.companyName')"
                                :popover="__('app.companyName')" search="true">
                                <option value="">--</option>
                            </x-forms.select>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group my-3">

                                <label class="f-14 text-dark-grey mb-12" data-label="true"
                                    for="edit_billing_company">{{ __('app.billingcompany') }}
                                    <sup class="f-14 mr-1">*</sup>
                                </label>
                                <input type="text" class="form-control height-35 f-14"
                                    placeholder="Enter a {{ __('app.billingcompany') }} name" value=""
                                    id="edit_billing_company" name="billing_company" required>

                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary closeMod" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('sections.datatable_js')
    <script src="{{ asset('vendor/jquery/clipboard.min.js') }}"></script>
    <script>
        $('#companyGroup').click(function() {
            $('#companyGroupModal').modal('show');
        });
        $('.closeMod').click(function() {
            $('.modal').modal('hide');
        });


        $(document).on('click', '.editCompanyGroup', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var billname = $(this).data('billname');

            // Set values inside modal
            $('#edit_id').val(id);
            setTimeout(() => {
                $('#editcompanyName').val(name).trigger('change');
            }, 1000);
            $('#edit_billing_company').val(billname);
            $('#editCompanyGroupModal').modal('show');
        });

        $('#billing_company').on('blur', function() {
            const billing_company = $(this).val();

            $.ajax({
                url: "{{ route('projects.checkDuplicatebillingCompanygroup') }}",
                type: "POST",
                data: {
                    billing_company: billing_company,
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    console.log(res.exists);
                    if (res.exists == true) {
                        $('#billing_company').val('');
                        $('#billing_company_msg').text('Already Exist');
                        setTimeout(() => {
                            $('#billing_company_msg').text('')
                        }, 3000);
                    }

                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });

        $('body').on('click', '.btnAdd', function() {
            $.ajax({
                url: "{{ route('projects.getCompanyGroup') }}",
                type: "get",
                success: function(response) {

                    console.log(response);

                    $('#companyName').empty().append(
                        '<option value="" disabled selected >-Select Company Name-</option>');
                    $('#editcompanyName').empty().append(
                        '<option value="" disabled selected >-Select Company Name-</option>');
                    if (response && response.length > 0) {
                        $.each(response, function(index, item) {
                            console.log(item);

                            $('#companyName').append(`
                                <option data-content="${item.company_name}"
                                        value="${item.id}">
                                    ${item.company_name || 'No Heading'}
                                </option>
                            `);

                            $('#editcompanyName').append(`
                                <option data-content="${item.company_name}"
                                        value="${item.id}">
                                    ${item.company_name || 'No Heading'}
                                </option>
                            `);
                        });
                        $('.select-picker').selectpicker('refresh');
                    }


                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });
    </script>
@endpush
