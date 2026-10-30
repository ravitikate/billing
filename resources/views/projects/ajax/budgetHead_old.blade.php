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
                    <div class="col-md-6">
                    <input type="hidden" name="project_id" id="project_id" value="{{ $project->id }}">
                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <x-forms.text :fieldLabel="__('app.project')" fieldName="projectName"
                                  fieldId="projectName" :fieldValue="$project->project_name" fieldReadOnly="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-forms.select fieldId="subproject_id" fieldName="subproject_id" :fieldLabel="__('app.subproject')"
                            :popover="__('app.selectSubproject')" search="true">
                <option value="">--</option>
            </x-forms.select>

        </div>

                    <div class="col-md-12">
                        <x-forms.file :fieldLabel="__('modules.import.file')" fieldName="import_file" fieldId="budget_import" />
                    </div>
                    <div class="col-md-12">
                        <x-forms.toggle-switch class="mr-0 mr-lg-12"
                            :fieldLabel="__('modules.import.containsHeadings')"
                            fieldName="heading"
                            fieldId="heading"/>
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
    const initialProjectId = $('#project_id').val();
    if (initialProjectId) {
        loadSubprojects(initialProjectId);
    }

    $('#project_id').on('change', function() {
        const projectId = $(this).val();
        loadSubprojects(projectId);
    });

    function loadSubprojects(projectId) {
       
            if (projectId) {
                 alert(projectId);
                $.ajax({
                    url: "{{ route('projects.getSubprojects') }}",
                    type: 'GET',
                    data: { project_id: projectId },
                    success: function(response) {
                        const $subprojectSelect = $('#subproject_id');
                        $subprojectSelect.empty().append('<option value="">--</option>');
                        if (response.subprojects && response.subprojects.length > 0) {
                            $.each(response.subprojects, function(index, subproject) {
                                $subprojectSelect.append(`
                                    <option data-content="<strong>${subproject.task_short_code || ''}</strong> ${subproject.heading || ''}"
                                            value="${subproject.id}">
                                        ${subproject.heading || 'No Heading'}
                                    </option>
                                `);
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching subprojects:', xhr.responseText);
                    }
                });
            } else {
                $('#subproject_id').empty().append('<option value="">--</option>');
            }
    }
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
