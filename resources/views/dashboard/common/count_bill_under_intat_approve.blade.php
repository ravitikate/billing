 <div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
                    <div class="d-block text-capitalize">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
                            @lang('app.pending_for_bills')
                        </h5>
                        <div class="d-flex">
                            <a href="#intatStatus" onclick="scrollToCenter(event, 'intatStatus')">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    {{ $intatBillcounts->intat_count }} <span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.intat')</span>
                                </p>
                            </a>
                            <a href="#overshotStatus" onclick="scrollToCenter(event, 'overshotStatus')">
                                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                                    {{ $intatBillcounts->overdue_pending_count }}
                                    <span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.overshot') </span>
                                </p>
                            </a>
                        </div>
                    </div>

                    <div class="d-block">
                        <i class="fa fa-coins text-lightest f-18"></i>
                    </div>
                </div>


