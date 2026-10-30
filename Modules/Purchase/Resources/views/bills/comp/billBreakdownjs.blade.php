<script>
    // Helper function to format values in Indian currency format
    function formatINR(value) {
        if (isNaN(value)) return value; // Return the value as is if it's not a number
        return '₹ ' + Number(value).toLocaleString('en-IN');
    }

    // Function to handle bill breakdown
    function billBreakdown(id) {
        var url = "{{ route('get-bill-breakdown', ':id') }}".replace(':id', id);

        // Show loading state in the modal

        $('#billBreakdown').modal('show');

        // AJAX request
        $.ajax({
            url: url,
            method: 'GET',

            success: function(data) {
                console.log(data);

                // Fill values dynamically with formatted currency
                $('#steel').text(formatINR(data.steel));
                $('#cement').text(formatINR(data.cement));
                $('#electricity').text(formatINR(data.electricity));
                $('#safetydebit').text(formatINR(data.safetydebit));
                $('#qualitydebit').text(formatINR(data.qualitydebit));
                $('#housekeeping').text(formatINR(data.housekeeping));
                $('#poorperformance').text(formatINR(data.poorperformance));
                $('#otherdebit').text(formatINR(data.otherdebit));
                $('#instantdebit').text(formatINR(data.instantdebit));
                $('#holdbillamt').text(formatINR(data.holdbillamt));
                $('#advancerecovery').text(formatINR(data.advancerecovery));
                $('#currentsub_total').text(formatINR(data.currentsub_total));
                $('#currenttax_total').text(formatINR(data.currenttax_total));
                $('#currentretention_cost').text(formatINR(data.currentretention_cost));
                $('#currenttotal').text(formatINR(data.currenttotal));
                $('#totalrecoveries').text(formatINR(data.totalrecoveries));
                $('#totaldebitamount').text(formatINR(data.totaldebitamount));
                $('#netpayableamount').text(formatINR(data.netpayableamount));
                $('#releaseHoldAmt').text(formatINR(data.releaseHoldAmt));


                $('#steel_input').val((data.steel));
                $('#cement_input').val((data.cement));
                $('#electricity_input').val((data.electricity));
                $('#safetydebit_input').val((data.safetydebit));
                $('#qualitydebit_input').val((data.qualitydebit));
                $('#housekeeping_input').val((data.housekeeping));
                $('#poorperformance_input').val((data.poorperformance));
                $('#otherdebit_input').val((data.otherdebit));
                $('#instantdebit_input').val((data.instantdebit));
                $('#holdbillamt_input').val((data.holdbillamt));
                $('#advancerecovery_input').val((data.advancerecovery));
                $('#currentsub_total_input').val((data.currentsub_total));
                $('#currenttax_total_input').val((data.currenttax_total));
                $('#currentretention_cost_input').val((data.currentretention_cost));
                $('#currenttotal_input').val((data.currenttotal));
                $('#totalrecoveries_input').val((data.totalrecoveries));
                $('#totaldebitamount_input').val((data.totaldebitamount));
                $('#netpayableamount_input').val((data.netpayableamount));
                $('#releaseHoldAmt_input').val((data.releaseHoldAmt));





                // Show modal with filled data
                $('#billBreakdown').modal('show');
            },
            error: function(xhr, status, error) {
                console.error('Error fetching bill breakdown:', error);
                alert('Failed to load bill breakdown. Please try again.');
            }
        });
    }
</script>
