@php
    $addProjectMemberPermission = user()->permission('add_project_members');
    $viewProjectMemberPermission = user()->permission('view_project_members');
    $editProjectMemberPermission = user()->permission('edit_project_members');
    $deleteProjectMemberPermission = user()->permission('delete_project_members');
    $viewProjectHourlyRatePermission = user()->permission('view_project_hourly_rates');
@endphp

<!-- ROW START -->
<div class="row py-5">
    <div class="col-lg-12 col-md-12 mb-4 mb-xl-0 mb-lg-4">
        @if (
            ($addProjectMemberPermission == 'all' ||
                $addProjectMemberPermission == 'added' ||
                $project->project_admin == user()->id) &&
                !$project->trashed())
            <x-forms.button-primary icon="plus" id="add-project-member" class="type-btn mb-3">
                @lang('modules.projects.addMemberTitle')
            </x-forms.button-primary>
        @endif

        @if ($viewProjectMemberPermission == 'all')
            <x-cards.data :title="__('modules.projects.members')"
                otherClasses="border-0 p-0 d-flex justify-content-between align-items-center table-responsive-sm">
                <x-table class="border-0 pb-3 admin-dash-table table-hover">

                    <x-slot name="thead">
                        <th class="pl-20">#</th>
                        <th>@lang('app.name')</th>
                        
                        <th>Level Of Approval</th>
                        <th>Approval Time</th>
                        {{-- <th>count</th> --}}
                        <th class="text-right pr-20">@lang('app.action')</th>
                    </x-slot>

                    @forelse($project->members as $key=>$member)
                        <tr id="row-{{ $member->id }}" data-dept-id="{{ $member->user->employeeDetail->department_id }}" data-proj-id="{{ $member->project->id }}", data-user-id="{{ $member->user->id }}">
                            <td class="pl-20">{{ $key + 1 }}</td>
                            <td>
                                <x-employee :user="$member->user" />

                            </td>
                            
                            <td>
                                <select name="" id="" class="form-control p-2 priority_select">
                                    <option value="">Select Priority</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" min="0" class="form-control p-2 approval-time approval-time-input" value="{{ $member->priorityRole->approval_time ?? '' }}" data-row-id="{{ $member->id }}" placeholder="Enter time" style="width: 80px; display: inline-block; margin-right: 10px;">
                                <select class="form-control p-2 approval-time approval-time-unit" data-row-id="{{ $member->id }}" style="width: 100px; display: inline-block;">
                                    <option value="hours" {{ (old('approval_time_unit', $member->approval_time_unit) == 'hours') ? 'selected' : '' }}>Hours</option>
                                    <option value="days" {{ (old('approval_time_unit', $member->approval_time_unit) == 'days') ? 'selected' : '' }}>Days</option>
                                </select>
                            </td>
                            <td class="text-right pr-20">
                                @if ($member->user->id == $project->project_admin && $editProjectMemberPermission == 'all')
                                    <x-forms.button-secondary data-row-id="{{ $member->id }}" icon="times"
                                        class="remove-admin">
                                        @lang('app.removeProjectAdmin')</x-forms.button-secondary>
                                @endif

                                @if ($deleteProjectMemberPermission == 'all')
                                    <x-forms.button-secondary data-row-id="{{ $member->id }}" icon="trash"
                                        class="delete-row">
                                        @lang('app.delete')</x-forms.button-secondary>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-cards.no-record icon="user" :message="__('messages.noMemberAddedToProject')" />
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </x-cards.data>
        @endif
    </div>

</div>
<!-- ROW END -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    $(document).ready(function() {
        setTimeout(function() {
            $('[data-toggle="popover"]').popover();
        }, 500);
    });

    $('#add-project-member').click(function() {
        const url = "{{ route('project-members.create') }}" + "?id={{ $project->id }}";
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);

    });

    $('.delete-row').click(function() {

        var id = $(this).data('row-id');
        var url = "{{ route('project-members.destroy', ':id') }}";
        url = url.replace(':id', id);

        var token = "{{ csrf_token() }}";

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
                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            $('#row-' + id).fadeOut();
                        }
                    }
                });
            }
        });

    });




    $('.change-hourly-rate').blur(function() {
        let id = $(this).data('row-id');
        let value = $(this).val();

        var url = "{{ route('project-members.update', ':id') }}";
        url = url.replace(':id', id);

        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: url,
            container: '#row-' + id,
            type: "POST",
            blockUI: true,
            data: {
                'hourly_rate': value,
                '_token': token,
                '_method': 'PUT'
            }
        });
    });


    $('body').on('click', '.assign_role', function() {
        var userId = $(this).data('user-id');
        var projectId = '{{ $project->id }}';
        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: "{{ route('projects.assign_project_admin') }}",
            type: "POST",
            data: {
                userId: userId,
                projectId: projectId,
                _token: token
            },
            blockUI: true,
            container: '.admin-dash-table',
            success: function(response) {
                if (response.status == "success") {
                    window.location.reload();
                }
            }
        });
    });

    $('body').on('click', '.remove-admin', function() {
        var userId = null;
        var projectId = '{{ $project->id }}';
        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: "{{ route('projects.assign_project_admin') }}",
            type: "POST",
            data: {
                userId: userId,
                projectId: projectId,
                _token: token
            },
            success: function(response) {
                if (response.status == "success") {
                    window.location.reload();
                }
            }
        });

    });




    $(document).on('change', '.approval-time-input, .approval-time-unit', function () {
    const $approvalTimeCell = $(this).closest('td');
    const approvalTime = $approvalTimeCell.find('.approval-time-input').val();
    const approvalUnit = $approvalTimeCell.find('.approval-time-unit').val();
    const $row = $(this).closest('tr');
    const departmentId = $row.data('dept-id');
    const projectId = $row.data('proj-id');
    const userId = $row.data('user-id');
    $.ajax({
        url: "{{ route('projects.updatePriority') }}",
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            department_id: departmentId,
            project_id: projectId,
            user_id: userId,
            approval_time: approvalTime,
            approval_unit: approvalUnit
        },
        success: function (response) {
            toastr.success('Successfully Submitted!', 'Success', { timeOut: 3000 });
        },
        error: function (xhr, status, error) {
            toastr.error('Failed to Submit!', 'Error', { timeOut: 3000 });
        }
    });
});

$(document).ready(function () {
    // Flag to track if priority_select is being updated programmatically
    let isUpdatingPrioritySelect = false;

    function loadApprovalPriorityTime() {
        $('tr[data-dept-id]').each(function () {
            const $row = $(this);
            const departmentId = $row.data('dept-id');
            const projectId = $row.data('proj-id');
            const userId = $row.data('user-id');
            $.ajax({
                url: "{{ route('projects.getApprovalPriorityTime') }}",
                type: 'GET',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    department_id: departmentId,
                    project_id: projectId,
                    user_id: userId
                },
                success: function (response) {
                    if (response.status === 'success' && response.data) {
                        const prioritySelectValue = response.data.priority_id;
                        const approvalTime = response.data.approval_time;
                        const approvalUnit = response.data.approval_unit;

                        const $prioritySelect = $row.find('.priority_select');
                        const $approvalTimeInput = $row.find('.approval-time-input');
                        const $approvalTimeUnitSelect = $row.find('.approval-time-unit');

                        if (approvalTime) {
                            $approvalTimeInput.val(approvalTime);
                        }
                        if (approvalUnit) {
                            $approvalTimeUnitSelect.val(approvalUnit);
                        }

                        if (prioritySelectValue) {
                            getApprovalDeptCountAutoSelect(prioritySelectValue, departmentId, projectId, $prioritySelect);
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error updating approval time and unit:', error);
                }
            });
        });
    }

    function getApprovalDeptCount() {
        $('tr[data-dept-id]').each(function () {
            const $row = $(this);
            const departmentId = $row.data('dept-id');
            const projectId = $row.data('proj-id');

            $.ajax({
                url: "{{ route('projects.getApprovalDeptCount') }}",
                type: 'GET',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    department_id: departmentId,
                    project_id: projectId
                },
                success: function (response) {
                    const departmentCount = response.departmentCount;
                    let optionsHtml = '';
                    optionsHtml += `<option value="">Select Priority</option>`;
                    for (let i = 1; i <= departmentCount; i++) {
                        optionsHtml += `<option value="${i}">${i}</option>`;
                    }
                    optionsHtml += `<option value="0">Deselect Priority</option>`;
                    const $prioritySelect = $row.find('.priority_select');
                    $prioritySelect.html(optionsHtml);
                },
                error: function (xhr, status, error) {
                    console.error('Error updating department count:', error);
                }
            });
        });
    }

    // Call to load initial data
    getApprovalDeptCount();
    loadApprovalPriorityTime();

    $(document).on('change', '.priority_select', function () {
        if (isUpdatingPrioritySelect) return;
        const selectedPriority = $(this).val();
        const $row = $(this).closest('tr');
        const departmentId = $row.data('dept-id');
        const projectId = $row.data('proj-id');
        const userId = $row.data('user-id');
        const $prioritySelect = $row.find('.priority_select');
        if (selectedPriority) {
            $.ajax({
                url: "{{ route('projects.updatePrioritySequency') }}",
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    department_id: departmentId,
                    project_id: projectId,
                    user_id: userId,
                    priority_select: selectedPriority
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message, 'Success', { timeOut: 3000 });
                    } else {
                        toastr.error(response.message, 'Error', { timeOut: 3000 });
                        resetPriorityDropdown($prioritySelect, departmentId, projectId);
                    }
                },
                error: function () {
                    toastr.error('An error occurred. Please try again.', 'Error', { timeOut: 3000 });
                    resetPriorityDropdown($prioritySelect, departmentId, projectId);
                }
            });
        }
    });

    function resetPriorityDropdown($prioritySelect, departmentId, projectId) {
        $.ajax({
            url: "{{ route('projects.getApprovalDeptCount') }}",
            type: 'GET',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                department_id: departmentId,
                project_id: projectId
            },
            success: function (response) {
                const departmentCount = response.departmentCount;
                let optionsHtml = '';
                optionsHtml += `<option value="">Select Priority</option>`;
                for (let i = 1; i <= departmentCount; i++) {
                    optionsHtml += `<option value="${i}">${i}</option>`;
                }

                optionsHtml += `<option value="0">Deselect Priority</option>`;
                $prioritySelect.html(optionsHtml);
                $prioritySelect.val("");
            },
            error: function (xhr, status, error) {
                toastr.error('Failed to reset priority dropdown', 'Error', { timeOut: 3000 });
            }
        });
    }

    function getApprovalDeptCountAutoSelect(prioritySelectValue, department_id, project_id, $prioritySelect) {
        const $row = $('tr[data-dept-id="' + department_id + '"][data-proj-id="' + project_id + '"]');
        $.ajax({
            url: "{{ route('projects.getApprovalDeptCount') }}",
            type: 'GET',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                department_id: department_id,
                project_id: project_id
            },
            success: function (response) {
                const departmentCount = response.departmentCount;
                let optionsHtml = '';
                optionsHtml += `<option value="">Select Priority</option>`;
                for (let i = 1; i <= departmentCount; i++) {
                    optionsHtml += `<option value="${i}">${i}</option>`;
                }
                optionsHtml += `<option value="0">Deselect Priority</option>`;
                if ($prioritySelect.length === 0) {
                    console.error('No .priority_select found in the row!');
                    return;
                }
                isUpdatingPrioritySelect = true;
                $prioritySelect.html(optionsHtml);

                if (prioritySelectValue) {
                    const optionExists = $prioritySelect.find(`option[value="${prioritySelectValue}"]`).length > 0;
                    if (optionExists) {
                        setTimeout(function () {
                            $prioritySelect.val(prioritySelectValue);
                            isUpdatingPrioritySelect = false;
                        }, 10);
                    } else {
                        console.log('The selected value does not exist in the options');
                    }
                } else {
                    isUpdatingPrioritySelect = false;
                }
            },
            error: function (xhr, status, error) {
                toastr.error('Error updating department count', 'Error', { timeOut: 3000 });
            }
        });
    }
});


</script>
