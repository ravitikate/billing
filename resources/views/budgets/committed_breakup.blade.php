@extends('layouts.app') 

@section('content') 
    <div class="content-wrapper border-top-0 client-detail-wrapper">
        <style>
            #logo {
                height: 50px;
            }

            #signatory img {
                height: 95px;
                margin-bottom: -40px;
                margin-top: 5px;
                margin-right: 15px;
            }
        </style>
        <div class="card  invoice">
            <!-- CARD BODY START -->
            <div class="add-client bg-white rounded">


            <div class="card-body">
             @if(isset($projectName) && $projectName)
                <div class="mb-3">
                    <strong class="h5">Project: {{ $projectName }}  ({{$subprojectName}})</strong>
                    @if(isset($wbs) && $wbs)
                        <br>
                        <small class="text-muted">PO Breakup for WBS: {{ $wbs }}</small>
                    @endif
                </div>
            @endif
                    @if ($poBreakups->isEmpty())
                    <p class="text-muted">No PO data available for this WBS.</p>
                    @else
                        <table id="poBreakupTable" class="table table-bordered">
                          <thead>
                            <tr>
                              <th >Sr.No.</th>
                              <th >Sub-Project</th>
                              <th>PO Number</th> 
                              <th>Vendor</th> 
                              <th>Amount (₹)</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach ($poBreakups as $po)
                              <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{$po->heading}}</td>
                                <td class="text-end">
                                    <a href="{{ route('committed.breakupOfPo', ['po_id' => $po->id , 'po_number'=>$po->po_number,'wbs'=>$po->wbs]) }}">
                                        {{ $po->po_number }}
                                    </a>
                                </td> 
                                 <td>{{ $po->name }}</td> 
                                <td> ₹ {{ \App\Helper\NumberFormat::formatIndianCurrency(round($po->total_amount) + round($po->total_gst_amount)) }}</td>
                              </tr>
                            @endforeach
                          </tbody>
                          <tfoot>
                            <tr>
                              <th colspan="4" class="text-end">Total:</th>
                              <th id="totalAmountCell"></th>
                            </tr>
                          </tfoot>
                        </table>

                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection 
 
 <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css" />

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.3/js/dataTables.buttons.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.3/js/buttons.dataTables.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.3/js/buttons.html5.min.js"></script> 

<!-- Your custom script -->
 <script>
  document.addEventListener('DOMContentLoaded', function () {
    new DataTable('#poBreakupTable', {
      pageLength: 100,
      searching: true,
      layout: {
        topStart: {
          buttons: ['excel']
        }
      },
      footerCallback: function (row, data, start, end, display) {
        let api = this.api();

        // Function to remove ₹ and commas, and parse to float
        const parseValue = (val) => parseFloat(val.replace(/[^0-9.-]+/g, '')) || 0;

        // Calculate total from column index 4 (5th column)
        let total = api
          .column(4, { page: 'current' })
          .data()
          .reduce((sum, val) => sum + parseValue(val), 0);

        // Format as Indian currency without decimal places
        let formattedTotal = new Intl.NumberFormat('en-IN', {
          style: 'currency',
          currency: 'INR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(total);

        // Update footer cell
        document.getElementById('totalAmountCell').innerText = formattedTotal;
      }
    });
  });
</script>








