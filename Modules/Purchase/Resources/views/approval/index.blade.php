@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        <div class="d-flex justify-content-between action-bar">

            <div id="table-actions" class="d-block d-lg-flex align-items-center">

            </div>

            <x-datatable.actions>
                <div class="select-status mr-3 pl-3">
                    <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                        <option value="">@lang('app.selectAction')</option>
                        <option value="delete">@lang('app.delete')</option>
                    </select>
                </div>
            </x-datatable.actions>

        </div>
        <!-- Add Task Export Buttons End -->
        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->

    <div class="modal fade" id="approvalModal" tabindex="-1" role="dialog" aria-labelledby="approvalModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <!-- Dynamic title based on status -->
              <h5 class="modal-title" id="approvalModalLabel">Are you sure you want to proceed?</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <form id="statusChangeForm" enctype="multipart/form-data">
              <div class="modal-body">
                <!-- Confirmation Message (Only visible when status == 2) -->
                <div id="confirmationMessage" class="alert alert-warning" style="display: none;">
                  Are you sure you want to approve the item bill?
                </div>

                <!-- Message Input (Hidden for status 2) -->
                <div class="mb-3" id="messageField">
                  <label for="statusMessage" class="form-label">Message:</label>
                  <textarea id="statusMessage" name="message" class="form-control" rows="3" required></textarea>
                </div>

                <!-- Document Type Input (Hidden for status 2) -->
                <div class="mb-3" id="documentTypeField">
                  <label for="documentType" class="form-label">Document Type:</label>
                  <input type="text" id="documentType" name="document_name" class="form-control" placeholder="Enter document type" required>
                </div>

                <!-- File Upload (Hidden for status 2) -->
                <div class="mb-3" id="fileUploadField">
                  <label for="documentFile" class="form-label">Upload Document:</label>
                  <input type="file" id="documentFile" name="file" class="form-control" accept=".pdf,.jpg,.png" required>
                  <small class="form-text text-muted">Allowed file types: PDF, JPG, PNG</small>
                </div>

                <!-- Hidden Inputs -->
                <input type="hidden" id="modalId" name="row_id">
                <input type="hidden" id="approve_status" name="approve_status">
                <input type="hidden" id="approveReject" name="approveReject">
                <input type="hidden" id="projectIds" name="project_id">
                <input type="hidden" id="department_ids" name="dept_id">
                <input type="hidden" id="priority_counts" name="priority_count">
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm</button>
              </div>
            </form>
          </div>
        </div>
      </div>



@endsection
@push('scripts')
    @include('sections.datatable_js')

    <script>

        const showTable = () => {
            window.LaravelDataTables["vendor-credits-table"].draw(true);
        }
        $('body').on('click', '#quick-action-apply', function () {
            const actionValue = $('#quick-action-type').val();
            if (actionValue == 'delete') {
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('messages.recoverRecord')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('messages.confirmDelete')",
                    cancelButtonText: "@lang('app.cancel')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        applyQuickAction();
                    }
                });

            } else {
                applyQuickAction();
            }
        });



        $(document).on('change', '.status-dropdown', function () {
            let status = $(this).val();
            let id = $(this).data('id');
            let projectId = $(this).data('project-id');
            let approve_status = $(this).data('approve-status');
            let department_id = $(this).data('department-id');
            let priority_count = $(this).data('priority-count');



            if (status == 2 || status == 3) {
    $('#approvalModal').modal('show');
    $('#approveReject').val(status);
    $('#approve_status').val(approve_status);
    $('#modalId').val(id);
    $('#projectIds').val(projectId);
    $('#department_ids').val(department_id);
    $('#priority_counts').val(priority_count);

    if (status == 2) {
  // Hide the modal body and show a confirmation message
  $('.modal-body').hide();
  $('#confirmationMessage').show().text('Are you sure you want to approve the item bill?');

  // Remove required attributes
  $('#statusMessage').prop('required', false);
  $('#documentType').prop('required', false);
  $('#documentFile').prop('required', false);
} else if (status == 3) {
  // Show the modal body for rejection
  $('.modal-body').show();
  $('#confirmationMessage').hide();

  // Make the message field required
  $('#statusMessage').prop('required', true);
  $('#documentType').prop('required', false);
  $('#documentFile').prop('required', false);
}


            }
        });
        $('#statusChangeForm').on('submit', function (e) {
    e.preventDefault();
    $('body').addClass('loading');

    let submitButton = $(this).find("button[type='submit']");
    submitButton.prop("disabled", true).text("Processing...");

    let formData = new FormData(this);

    $.ajax({
        url: '{{ route('update_status') }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            $('body').removeClass('loading');
            submitButton.prop("disabled", false).text("Confirm");

            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload(); // Reload the page after success
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        },
        error: function () {
            $('body').removeClass('loading');
            submitButton.prop("disabled", false).text("Confirm");

            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Something went wrong!',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
});






    </script>


@endpush
