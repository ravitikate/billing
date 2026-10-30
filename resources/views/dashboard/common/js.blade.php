
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script>

// ===== Chart Functions =====
    function renderBarChart(chartType, ctxId, labels, datasets, yAxisLabel, stacked) {
        const ctx = document.getElementById(ctxId).getContext('2d');
        new Chart(ctx, {
            type: chartType,
            data: { labels, datasets },
            options: {
                responsive: true,
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        font: { weight: 'bold', size: 10 },
                        formatter: value => value > 0 ? value.toFixed(2) : ''
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return `${tooltipItem.dataset.label}: ₹ ${tooltipItem.raw.toFixed(2)} Cr`;
                            }
                        }
                    },
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, stacked, title: { display: true, text: yAxisLabel }, grid: { display: false } },
                    x: { stacked, grid: { display: false } }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    function renderBarChart1(chartType, ctxId, labels, datasets, yAxisLabel, stacked) {
        const ctx = document.getElementById(ctxId).getContext('2d');
        new Chart(ctx, {
            type: chartType,
            data: { labels, datasets },
            options: {
                responsive: true,
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        font: { weight: 'bold', size: 10 },
                        formatter: value => value > 0 ? value.toFixed(2) : ''
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return `${tooltipItem.dataset.label}: ${tooltipItem.raw.toFixed(2)}`;
                            }
                        }
                    },
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, stacked, title: { display: true, text: yAxisLabel }, grid: { display: false } },
                    x: { stacked, grid: { display: false } }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    // ===== Render Charts =====
    Chart.register(ChartDataLabels);

    renderBarChart(
        'bar',
        'totalVSpaidBill',
        @json($chartLabels),
        [
            { label: 'Due + Paid (Total)', data: @json($chartTotalCombined), backgroundColor: '#007bff', borderColor: '#007bff', borderWidth: 1 },
            { label: 'Total Paid', data: @json($chartPaidAmounts), backgroundColor: '#28a745', borderColor: '#28a745', borderWidth: 1 },
            { label: 'Total Due', data: @json($chartTotalAmounts), backgroundColor: '#ffc107', borderColor: '#ffc107', borderWidth: 1 }
        ],
        'Amount in Crores (₹)',
        false
    );

    renderBarChart1(
        'bar',
        'chartPaidVsUnpaidVsDelay',
        @json($chartPaidVsUnpaidVsDelay['labels']),
        [
            { label: 'Paid Bills', data: @json($chartPaidVsUnpaidVsDelay['paid']), backgroundColor: '#007bff', borderColor: '#007bff', borderWidth: 1 },
            { label: 'Unpaid Bills', data: @json($chartPaidVsUnpaidVsDelay['unpaid']), backgroundColor: '#28a745', borderColor: '#28a745', borderWidth: 1 },
            { label: 'Delay Bills', data: @json($chartPaidVsUnpaidVsDelay['delayed']), backgroundColor: '#ffc107', borderColor: '#ffc107', borderWidth: 1 }
        ],
        'Count of Bills',
        false
    );

    // ===== Scroll to Section =====
    window.scrollToSection = function(sectionId) {
        const target = document.getElementById(sectionId);
        if (target) target.scrollIntoView({ behavior: 'smooth' });
    };
</script>
