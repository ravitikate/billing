<div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
                    <div class="d-block text-capitalize">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
                            @lang('app.wo_details')
                        </h5>
                        <div class="d-flex">
                            <a href="{{ route('work_order.work-order') }}">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    {{ $totalWO }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.totalwork_order')</span>
                                </p>
                            </a>

                            <a href="{{ route('work_order.work-order') }}">
                                <p class="mb-0 f-21 font-weight-bold text-success d-grid mr-5">
                                    {{ $totalValidateWO }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.validate_wo') </span>
                                </p>
                            </a>
                        </div>
                    </div>
                    <div class="d-block">
                        <i class="fa fa-coins text-lightest f-18"></i>
                    </div>
                </div>