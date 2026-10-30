@php
   $sessionUserId = Auth::id();
    $projects = DB::table('project_members as pm')
    ->join('projects as p', 'p.id', '=', 'pm.project_id')
    ->where('pm.user_id', $sessionUserId) // Fixed variable name from $ssessionUserId to $sessionUserId
    ->select( 'p.*') // specify columns you need
    ->get();



@endphp
<div class="modal fade" id="instantDebitModal" tabindex="-1" aria-labelledby="instantDebitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('projects.submitaddinstantDebitAmount') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">@lang('app.instantDebit')</h5>
                    <button type="button" class="closeMod" data-bs-dismiss="modal" aria-label="Close">x</button>
                </div>

                <div class="modal-body">
                    <div class="row border-top-grey ">
                        <div class="col-md-3">
                            <label class="f-14 text-dark-grey mb-12 mt-3"
                                for="project_id">{{ __('app.project') }}</label>
                            <select name="project_id" id="project_id" class="form-control select-picker" required>
                                <option value="">{{ __('app.select_project') }}</option>
                                @foreach ($projects as $item)
                                    <option value="{{ $item->id }}">{{ $item->project_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <x-forms.select fieldId="subproject_id" fieldName="subproject_id" :fieldLabel="__('app.subproject')"
                                :popover="__('app.selectSubproject')" search="true">
                                <option value="">--</option>
                            </x-forms.select>

                        </div>
                        <div class="col-md-3">
                            <x-forms.select fieldId="vendor_id" fieldName="vendor_id" :fieldLabel="__('app.vendorName')"
                                :popover="__('app.vendorName')" search="true">
                                <option value="">--</option>
                            </x-forms.select>
                        </div>
                        <div class="col-md-3">
                            <x-forms.select fieldId="purchaseOrder" fieldName="purchaseOrder" :fieldLabel="__('app.purchaseOrder')"
                                :popover="__('app.purchaseOrder')" search="true">
                                <option value="">--</option>
                            </x-forms.select>
                        </div>

                    </div>
                    <br>
                    <div class="row border-top-grey p-2">

                        <table class="table" id="htmlInstantDebitDetail">

                        </table>
                        <div id="messageBox" class="alert alert-danger" style="display:none;"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <h5>Add Instant Debit</h5>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group my-3">
                                <label for="instantDebitAmount"
                                    class="form-label">{{ __('app.instantDebitAmount') }}</label>
                                <input type="text" class="form-control height-35 f-15" id="instantDebitAmount"
                                    name="instantDebitAmount">
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="form-group my-3">
                                <label for="statusMessage" class="form-label">Message:</label>
                                <input type="text" class="form-control height-35 f-15" id="statusMessage"
                                    name="message">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mt-3 mb-3" id="fileUploadField">
                                <label for="documentFile" class="form-label">Upload Document:</label>
                                <input type="file" id="documentFile" name="file" class="form-control"
                                    accept=".pdf,.jpg,.png" autocomplete="off">
                                <small class="form-text text-muted">Allowed file types: PDF, JPG, PNG</small>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary closeMod" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="smt_btn">Submit</button>
                </div>
            </form>


        </div>
    </div>
</div>


<div class="modal fade" id="editInstantDebitModal" tabindex="-1" aria-labelledby="editInstantDebitModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('projects.updateaddinstantDebitAmount') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">@lang('app.instantDebit')</h5>
                    <button type="button" class="closeMod" data-bs-dismiss="modal" aria-label="Close">x</button>
                </div>

                <div class="modal-body">
                    <div class="row border-top-grey ">
                        <table class="table" id="htmlInstantDebitDetailx"></table>
                    </div>
                    <input type="text" id="editinstantDebitId" name="id" hidden>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group my-3">
                                <label for="instantDebitAmount"
                                    class="form-label">{{ __('app.instantDebitAmount') }}</label>
                                <input type="text" class="form-control height-35 f-15" id="editinstantDebitAmount"
                                    name="instantDebitAmount">
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="form-group my-3">
                                <label for="statusMessage" class="form-label">{{ __('app.message') }}</label>
                                <input type="text" class="form-control height-35 f-15" id="editstatusMessage" name="message">
                                </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="mt-3 mb-3" id="fileUploadField">
                                <input type="hidden" id="old_file" name="old_file">
                                <div style="display: flex;justify-content:space-between">
                                    <label for="documentFile"
                                        class="form-label">{{ __('app.changeDocument') }}</label> <span
                                        id="viewFile"></span>
                                </div>

                                <input type="file" id="editdocumentFile" name="file" class="form-control"
                                    accept=".pdf,.jpg,.png" autocomplete="off">
                                <small class="form-text text-muted">Allowed file types: PDF, JPG, PNG</small>

                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary closeMod" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="smt_btn">Submit</button>
                </div>
            </form>


        </div>
    </div>
</div>
