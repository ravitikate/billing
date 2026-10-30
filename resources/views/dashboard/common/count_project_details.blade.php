<div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
                    <div class="d-block text-capitalize">
                        <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">
                            @lang('app.project_details')
                        </h5>
                        <div class="d-flex">
                            <a href="{{ route('projects.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-blue d-grid mr-5">
                                    {{ $totalProjects }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.projects')</span>
                                </p>
                            </a>
                            <a href="{{ route('tasks.index') }}">
                                <p class="mb-0 f-21 font-weight-bold text-danger d-grid mr-5">
                                    {{ $totalSubProjects }}<span class="f-12 font-weight-normal text-lightest">
                                        @lang('app.subProjects') </span>
                                </p>
                            </a>
                        </div>
                    </div>

                    <div class="d-block">
                        <i class="fa fa-coins text-lightest f-18"></i>
                    </div>
                </div>