<div class="row" id="import_table">
    <div class="col-sm-12">
        <x-form id="import-budget-data-form">
            <div class="add-project bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @lang('app.uploadBudgetHead')</h4>
                <div class="col-sm-12 pt-2">
                    <div class="alert alert-warning" role="alert">
                        @lang('app.uploadBudgetHead')
                    </div>
                </div>
                <div class="row py-20">
                    <div class="col-md-12">
                        <input type="hidden" value="{{$budget_id}}" name="budget_id">
                        <x-forms.file :fieldLabel="__('modules.import.file')" fieldName="import_file" fieldId="budget_import" />
                    </div>
                    <div class="col-md-12" hidden>
                        <x-forms.toggle-switch class="mr-0 mr-lg-12" :fieldLabel="__('modules.import.containsHeadings')" fieldName="heading"
                            fieldId="heading" />
                    </div>
                </div>
                <x-form-actions>
                    <x-forms.button-primary id="import-project-form" class="mr-3" icon="arrow-right">@lang('app.uploadNext')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('projects.index')" class="border-0">@lang('app.back')
                    </x-forms.button-cancel>

                </x-form-actions>
            </div>
        </x-form>

    </div>
</div>

<script> 
  $(document).ready(function() {
        const $projectSelect = $('#project_id');
        const $subprojectSelect = $('#subproject_id');
        var defaultOption = '<option value="" disabled selected >-Select Sub Project-</option>';

        function loadSubprojects(projectId) {
            if (projectId) {
                $.ajax({
                    url: "{{ route('get.subProjects') }}",
                    type: "GET",
                    data: {
                        project_id: projectId
                    },
                    success: function(response) {
                        console.log(response);

                        $subprojectSelect.empty().append(defaultOption);

                        if (response && response.length > 0) {
                            $.each(response, function(index, subproject) {
                                $subprojectSelect.append(`
                                <option data-content="<strong>${subproject.task_short_code || ''}</strong> ${subproject.heading || ''}"
                                        value="${subproject.id}">
                                    ${subproject.heading || 'No Heading'}
                                </option>
                            `);
                            });
                            $('.select-picker').selectpicker('refresh');
                        }
                        if (response.length == 0) {
                            $subprojectSelect.append(
                                '<option value="" disabled selected>No Sub Projects Found</option>'
                                );

                            $('.select-picker').selectpicker('refresh');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching subprojects:', status, error);
                        $subprojectSelect.html(
                            '<option value="">Error loading sub-projects</option>');
                    }
                });
            } else {
                $subprojectSelect.html('<option value="">Select a Project First</option>');
            }
        }

        const initialProjectId = $projectSelect.val();
        if (initialProjectId) {
            $subprojectSelect.empty().append(defaultOption);
            loadSubprojects(initialProjectId);
        }

        $projectSelect.on('change', function() {
            const projectId = $(this).val();
            $subprojectSelect.empty().append(defaultOption);
            loadSubprojects(projectId);
        });
    });

    $(document).ready(function() {

        $("#budget_import").dropify({
            messages: dropifyMessages
        });

        $('body').on('click', '#import-project-form', function() {
            const url = "{{ route('projects.budgetheads') }}";

            $.easyAjax({
                url: url,
                container: '#import-budget-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#import-project-form",
                file: true,
                data: $('#import-budget-data-form').serialize(),
                success: function(response) {
                    if (response.status === 'confirm') {
                        // Show SweetAlert if budget head already exists
                        Swal.fire({
                            title: 'Data Already Exists!',
                            text: response.message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'OK',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Perform additional action if needed
                                console.log('User confirmed to proceed.');
                            } else {
                                console.log('User canceled.');
                            }
                        });
                    } else if (response.status === 'success') {
                        $('#import_table').html(response.view);
                    }
                }
            });
        });
    });
</script>
