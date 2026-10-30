@extends('layouts.app')
@section('content')
    <div class="content-wrapper">
        <link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/css/jquery-ui.css') }}">

        <div class="bg-white rounded b-shadow-4 create-inv">
            <div class="px-lg-4 px-md-4 px-3 py-3">
                <h4 class="mb-0 f-21 font-weight-normal">Edit Email Template</h4>
            </div>
            <hr class="m-0 border-top-grey">

            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">
                    <div class="s-b-inner s-b-notifications b-shadow-4 rounded">
                        <div class="s-b-n-content">
                            <div class="table-responsive">
                                <table class="table table-bordered dataTable" id="example">
                                    <thead>
                                        <tr>
                                            <th>SL.</th>
                                            <th>variable name</th>
                                            <th>variable meaning</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($t_variable as $index => $item)
                                        <tr>
                                            <td>{{$index + 1}}</td>
                                            <td>{{$item->variable_name}}</td>
                                            <td>{{$item->variable_meaning}}</td>
                                        </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="3"><h4>
                                                Use this variable for type message dynamically</h4></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <hr class="m-0 border-top-grey">
            <form id="handleSubmission">
                @csrf
                <div class="row px-lg-4 px-md-4 px-3 py-3">
                    <div class="col-md-12">
                        <input type="hidden" name="id" id="id">
                        <div class="form-group mb-4">
                            <label class="f-14 text-dark-grey mb-12" for="template_name">Template Name</label>
                            <input type="text" id="template_name" name="template_name"
                                value="{{ old('template_name', $template->name ?? '') }}"
                                class="form-control height-35 f-15 datepicker" required readonly>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-4">
                            <label class="f-14 text-dark-grey mb-12" for="email_subject">Subject</label>
                            <input type="text" id="email_subject" name="email_subject"
                                value="{{ old('email_subject', $template->subject ?? '') }}"
                                class="form-control height-35 f-15 datepicker" required>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" fieldrequired="true" type="checkbox" name="template_status"
                                id="template_status" autocomplete="off"
                                {{ $template->mail_status == '1' ? 'checked' : '' }}>
                            <label
                                class="form-check-label form_custom_label text-dark-grey pl-2 mr-4 justify-content-start cursor-pointer checkmark-20 pt-1 text-wrap text-break"
                                for="template_status">
                                Status
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12 my-3">
                        <div class="form-group">
                            <x-forms.label fieldId="template" :fieldLabel="__('Message')">
                            </x-forms.label>
                            <textarea name="template" id="template-text"> {{ old('template', $template->template ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="col-md-12 my-3">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                Update
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trumbowyg@2.24.0/dist/ui/trumbowyg.min.css">
    <script src="https://cdn.jsdelivr.net/npm/trumbowyg@2.24.0/dist/trumbowyg.min.js"></script>




    <script>
    $(function () {
    const url = window.location.href;
    const path = new URL(url).pathname;
    const segments = path.split('/');
    const id = segments[segments.length - 1];

    $('#id').val(id);
    $('#template-text').trumbowyg();

    $('#handleSubmission').on('submit', function (e) {
    e.preventDefault();

    // Get all form data including HTML content
    let formData = {
        _token: $('[name="_token"]').val(),
        id: $('#id').val(),
        template_name: $('#template_name').val(),
        email_subject: $('#email_subject').val(),
        template_status: $('#template_status').is(':checked') ? 'on' : 'off',
        template: $('#template-text').trumbowyg('html')
    };

    $.ajax({
        url: '{{ route('emailtemplate.update') }}',
        type: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        processData: false,
        success: function (res) {
            toastr.success(res.success);
        },
        error: function (xhr) {
            toastr.error('Something went wrong. Please try again.');
            console.error(xhr.responseText);
        }
    });
});
});
    </script>
@endsection
