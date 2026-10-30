<div class="d-lg-flex d-md-flex d-block py-2 pb-2 align-items-center">
    <div>
        <h3 class="heading-h3 mb-0 f-21 font-weight-bold">@lang('app.welcome') {{ $user->name }}</h3>
    </div>
    <div class="ml-auto d-flex clock-in-out mb-3 mb-lg-0 mb-md-0 m mt-4 mt-lg-0 mt-md-0 justify-content-end">
        <p class="mb-0 text-lg-right text-md-right f-18 font-weight-bold text-dark-grey d-grid align-items-center">
            <input type="hidden" id="current-latitude" name="current_latitude">
            <input type="hidden" id="current-longitude" name="current_longitude">
            <span id="dashboard-clock">{!! now()->timezone(company()->timezone)->translatedFormat(company()->time_format) .
                '</span><span class="f-10 font-weight-normal">' .
                now()->timezone(company()->timezone)->translatedFormat('l') .
                '</span>' !!}
        </p>
        <!--@if ($instantDebitPermission == 'all')-->
        <!--    <button type="button" class="btn btn-info rounded f-15 ml-4 btnAdd" id="instantDebit"><i-->
        <!--            class="icons icon-plus mr-2"></i>{{ __('app.addinstantDebitAmount') }}</button>-->
        <!--@endif-->
    </div>
</div>
