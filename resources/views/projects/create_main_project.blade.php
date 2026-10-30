<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('modules.projects.projectName')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-table class="table-bordered" headType="thead-light">
        <x-slot name="thead">
            <th>#</th>
            <th>@lang('modules.projects.projectName')</th>
            <th class="text-right">@lang('app.action')</th>
        </x-slot>

        @forelse($main_project as $key=>$item)
            <tr id="cat-{{ $item->id }}">
                <td>{{ $key + 1 }}</td>
                <td data-row-id="{{ $item->id }}" contenteditable="true">{{ $item->main_project_name }}</td>
                <td class="text-right">

                        <x-forms.button-secondary data-cat-id="{{ $item->id }}" icon="trash" class="delete-category">
                            @lang('app.delete')
                        </x-forms.button-secondary>

            </tr>
        @empty
            <x-cards.no-record-found-list />
        @endforelse
    </x-table>

    <x-form id="createProjectMain">
        <div class="row border-top-grey ">
            <div class="col-sm-12">
                <x-forms.text fieldId="main_project_name" :fieldLabel="__('modules.projects.projectName')"
                    fieldName="main_project_name" fieldRequired="true" :fieldPlaceholder="__('placeholders.projectName')">
                </x-forms.text>
            </div>

        </div>
    </x-form>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
    <x-forms.button-primary id="save-category" icon="check">@lang('app.save')</x-forms.button-primary>
</div>
<script>
    $('#save-category').click(function() {
        var url = "{{ route('project.storeMainProject') }}";
        $.easyAjax({
            url: url,
            container: '#createProjectMain',
            type: "POST",
            data: $('#createProjectMain').serialize(),
            success: function(response) {
                if (response.status == 'success') {
                    $('#main_project_name').html(response.data);
                    $('#main_project_name').selectpicker('refresh');
                    $(MODAL_LG).modal('hide');
                }
            }
        })
    });

    $('[contenteditable=true]').focus(function() {
        $(this).data("initialText", $(this).html());
        let rowId = $(this).data('row-id');
    }).blur(function() {
        if ($(this).data("initialText") !== $(this).html()) {
            let id = $(this).data('row-id');
            let value = $(this).html();

            var url = "{{ route('project.updateMainProject', ':id') }}";
            url = url.replace(':id', id);

            var token = "{{ csrf_token() }}";

            $.easyAjax({
                url: url,
                container: '#row-' + id,
                type: "POST",
                data: {
                    'main_project_name': value,
                    '_token': token,
                    '_method': 'PUT'
                },
                blockUI: true,
                success: function(response) {
                    if (response.status == 'success') {
                        $('#main_project_name').html(response.data);
                        $('#main_project_name').selectpicker('refresh');
                    }
                }
            })
        }
    });
</script>
