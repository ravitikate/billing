@if ($multiple)
    <div {{ $attributes }}></div>
    <script>
        var chartType = "{{ $attributes->get('chartType', 'line') }}";
        var data = {
            labels: [
                @foreach ($chartData['labels'] as $label)
                    "{{ $label }}",
                @endforeach
            ],
            datasets: [
                @foreach ($chartData['values'] as $key => $value)
                    {
                    name: "{{ $chartData['name'][$key] }}",
                    values: [
                    @foreach ($value as $key => $val)
                        {{ $val }},
                    @endforeach
                    ],
                    chartType: chartType
                    },
                @endforeach
            ]
        };
    </script>

    <script>
        var chart = new frappe.Chart("#{{ $attributes['id'] }}", {
            data: data,
            type: chartType,
            height: 500,
            lineOptions: {
                dotSize: 4,
                hideLine: 0,
                hideDots: 0,
                heatline: 1,
                regionFill: 1,
            },
            valuesOverPoints: 1,
            axisOptions: {
                yAxisMode: 'tick',
                xAxisMode: 'tick',
                xIsSeries: 0
            },
            colors: [
                @foreach ($chartData['colors'] as $color)
                    "{{ $color }}",
                @endforeach
            ]
        });

        // Apply 45-degree rotation to X-axis labels after chart is rendered
        function applyLabelRotation() {
            var xAxisLabels = document.querySelectorAll("#{{ $attributes['id'] }} .x.axis text");
            if (xAxisLabels.length > 0) {
                xAxisLabels.forEach(function (label) {
                    label.setAttribute("transform", "rotate(45)");
                    label.setAttribute("text-anchor", "start");
                    label.setAttribute("dy", "0.35em"); // optional for vertical alignment
                });
            }
        }

        // Use setTimeout with requestAnimationFrame to ensure the chart is fully rendered
        setTimeout(function() {
            requestAnimationFrame(applyLabelRotation);
        }, 1000); // 1-second delay
    </script>
@else
    @php
        $valuesTotal = array_sum($chartData['values']);
    @endphp
    @if ($valuesTotal > 1)
        <div {{ $attributes }}></div>
        <script>
            var chartType = "{{ $attributes->get('chartType', 'line') }}";
            var data = {
                labels: [
                    @foreach ($chartData['labels'] as $label)
                        "{{ $label }}",
                    @endforeach
                ],
                datasets: [{
                    name: "{{ $chartData['name'] }}",
                    values: [
                        @foreach ($chartData['values'] as $value)
                            {{ $value }},
                        @endforeach
                    ],
                    chartType: chartType
                }]
            };
        </script>

        <script>
            var valuesOverPoint = data['datasets'][0]['values'].length <= 30 ? 1 : 0;
            var chart = new frappe.Chart("#{{ $attributes['id'] }}", {
                data: data,
                type: chartType,
                height: 500,
                lineOptions: {
                    dotSize: 4,
                    hideLine: 0,
                    hideDots: 0,
                    heatline: 1,
                    regionFill: 1,
                },
                valuesOverPoints: valuesOverPoint,
                axisOptions: {
                    yAxisMode: 'tick',
                    xAxisMode: 'tick',
                    xIsSeries: 0
                },
                colors: [
                    @foreach ($chartData['colors'] as $color)
                        "{{ $color }}",
                    @endforeach
                ]
            });

            // Apply 45-degree rotation to X-axis labels after chart is rendered
            function applyLabelRotation() {
                var xAxisLabels = document.querySelectorAll("#{{ $attributes['id'] }} .x.axis text");
                if (xAxisLabels.length > 0) {
                    xAxisLabels.forEach(function (label) {
                        label.setAttribute("transform", "rotate(45)");
                        label.setAttribute("text-anchor", "start");
                        label.setAttribute("dy", "0.35em"); // optional for vertical alignment
                    });
                }
            }

            // Use setTimeout with requestAnimationFrame to ensure the chart is fully rendered
            setTimeout(function() {
                requestAnimationFrame(applyLabelRotation);
            }, 1000); // 1-second delay
        </script>
    @else
        <div class="align-items-center d-flex flex-column text-lightest p-20"
            style="height: 500px;">
            <i class="side-icon bi bi-bar-chart"></i>
            <div class="f-15 mt-4">
                - @lang('messages.notEnoughData') -
            </div>
        </div>
    @endif
@endif
