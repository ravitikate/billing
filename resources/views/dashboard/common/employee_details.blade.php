<div class="row">
    @if (in_array('profile', $activeWidgets))
        <div class="col-md-12">
            <div class="card border-0 b-shadow-4 mb-3 e-d-info">
                <a @if (!in_array('client', user_roles())) href="{{ route('employees.show', user()->id) }}" @endif>
                    <div class="card-horizontal align-items-center pb-2">
                        <div class="card-img">
                            <img class="" src=" {{ $user->image_url }}" alt="Card image">
                        </div>
                        <div class="card-body border-0 pl-0">
                            <h4 class="card-title text-dark f-18 f-w-500 mb-0">{{ $user->name }}</h4>
                            <p class="f-14 font-weight-normal text-dark-grey mb-2">
                                {{ $user->employeeDetail->designation->name ?? '--' }}</p>
                            {{-- <p class="card-text f-12 text-lightest"> @lang('app.employeeId') :
                                {{ $user->employeeDetail->employee_id }}</p> --}}
                        </div>
                    </div>
                </a>

            </div>
        </div>
    @endif
</div>
