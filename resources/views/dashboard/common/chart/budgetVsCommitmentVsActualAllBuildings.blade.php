<div class="col-sm-8 mt-3">
    <x-cards.data>
        @slot('title')
            {{ __('app.budgetvsactualvspoBuildiing') }} &nbsp;&nbsp;
            <button onclick="downloadChart('budgetvsactualvspoBuildiing')" class="btn btn-sm btn-light"
                style="float:right; padding:0 5px;" title="Download Chart">
                <i class="fa fa-download"></i>
            </button>
        @endslot

        <div style="height:400px; width:100%;">
            <canvas id="budgetvsactualvspoBuildiing" style="width:100%; height:100%;"></canvas>
        </div>

    </x-cards.data>
</div>
