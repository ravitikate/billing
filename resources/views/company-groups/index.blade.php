@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('content')
    <div class="content-wrapper">
        <div class="d-block d-lg-flex d-md-flex justify-content-between">
            <div id="table-actions" class="flex-grow-1 align-items-center mb-2 mb-lg-0 mb-md-0">
                <button id="companyGroup" type="button"
                    class="btn btn-primary rounded f-14 p-2 mr-3 float-left mb-2 mb-lg-0 mb-md-0" data-toggle="tooltip"
                    data-original-title="{{ __('app.companyGroup') }}">@lang('app.createCompanyGroup')</button>
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
                <form action="{{ route('projects.submitcompanyGroup') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createBudgetModalLabel">@lang('app.createCompanyGroup')</h5>
                        <button type="button" class="closeMod" data-bs-dismiss="modal" aria-label="Close">x</button>
                    </div>

                    <div class="modal-body">
                        <div class="row border-top-grey ">
                            <div class="col-sm-12">
                                <div class="form-group my-3">
                                    <label class="f-14 text-dark-grey mb-12" data-label="true"
                                        for="company_group">{{ __('app.companyGroup') }}
                                        <sup class="f-14 mr-1">*</sup>

                                    </label>

                                    <input type="text" class="form-control height-35 f-14"
                                        placeholder="Enter a {{ __('app.companyGroup') }} name" value=""
                                        id="company_group" name="company_group" required>

                                    <div class="d-block invalid-feedback" id="company_group_msg"></div>
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
                <form id="editCompanyGroupForm" method="POST" action="{{ route('projects.updatecompanyGroup') }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCompanyGroupModalLabel">Edit Company Group</h5>
                        <button type="button" class="btn-close closeMod" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="row border-top-grey ">
                            <div class="col-sm-12">
                                <div class="form-group my-3">
                                    <label class="f-14 text-dark-grey mb-12" data-label="true"
                                        for="company_group">{{ __('app.companyGroup') }}
                                        <sup class="f-14 mr-1">*</sup>

                                    </label>

                                    <input type="text" class="form-control height-35 f-14"
                                        placeholder="Enter a {{ __('app.companyGroup') }} name" value=""
                                        id="edit_company_group" name="company_group" required>

                                </div>
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

            // Set values inside modal
            $('#edit_id').val(id);
            $('#edit_company_group').val(name);

            // Open the modal
            $('#editCompanyGroupModal').modal('show');
        });

        $('#company_group').on('blur', function() {
            const company_name = $(this).val();

            $.ajax({
                url: "{{ route('projects.checkDuplicateCompanygroup') }}",
                type: "POST",
                data: {
                    company_name: company_name,
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    console.log(res.exists);
                    if (res.exists == true) {
                        $('#company_group').val('');
                        $('#company_group_msg').text('Already Exist');
                        setTimeout(() => {
                            $('#company_group_msg').text('')
                        }, 3000);
                    }

                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });
    </script>
@endpush
