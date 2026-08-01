import Chart from 'chart.js/auto';

const currencyFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const palette = [
    '#2563EB',
    '#7C3AED',
    '#DB2777',
    '#EA580C',
    '#059669',
    '#0891B2',
    '#4F46E5',
    '#CA8A04',
    '#DC2626',
    '#64748B',
];

function destroyExistingCharts() {
    const existingCharts =
        window.__larasExpenseAnalysisCharts ?? [];

    existingCharts.forEach((chart) => {
        chart.destroy();
    });

    window.__larasExpenseAnalysisCharts = [];
}

function mountExpenseAnalysisCharts() {
    const dataElement = document.getElementById(
        'expense-analysis-chart-data',
    );

    if (!dataElement) {
        return;
    }

    let chartData;

    try {
        chartData = JSON.parse(
            dataElement.textContent ?? '{}',
        );
    } catch (error) {
        console.error(
            'Data grafik analisis tidak valid.',
            error,
        );

        return;
    }

    destroyExistingCharts();

    const chartInstances = [];

    const categoryCanvas = document.getElementById(
        'expense-category-chart',
    );

    if (categoryCanvas) {
        const categoryLabels =
            chartData.categories?.labels ?? [];

        const categoryValues =
            chartData.categories?.values ?? [];

        const categoryChart = new Chart(
            categoryCanvas,
            {
                type: 'bar',

                data: {
                    labels: categoryLabels,

                    datasets: [
                        {
                            label: 'Pengeluaran',
                            data: categoryValues,

                            backgroundColor:
                                categoryLabels.map(
                                    (_, index) =>
                                        palette[
                                            index
                                            % palette.length
                                        ],
                                ),

                            borderRadius: 8,
                            borderSkipped: false,
                            maxBarThickness: 48,
                        },
                    ],
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: {
                            display: false,
                        },

                        tooltip: {
                            callbacks: {
                                label(context) {
                                    return currencyFormatter
                                        .format(
                                            Number(
                                                context.raw
                                                ?? 0,
                                            ),
                                        );
                                },
                            },
                        },
                    },

                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },

                            ticks: {
                                maxRotation: 0,
                                autoSkip: true,
                            },
                        },

                        y: {
                            beginAtZero: true,

                            ticks: {
                                callback(value) {
                                    return currencyFormatter
                                        .format(
                                            Number(value),
                                        );
                                },
                            },
                        },
                    },
                },
            },
        );

        chartInstances.push(categoryChart);
    }

    const monthlyCanvas = document.getElementById(
        'expense-monthly-chart',
    );

    if (monthlyCanvas) {
        const monthlyChart = new Chart(
            monthlyCanvas,
            {
                type: 'line',

                data: {
                    labels:
                        chartData.monthly?.labels ?? [],

                    datasets: [
                        {
                            label:
                                'Pengeluaran bulanan',

                            data:
                                chartData.monthly?.values
                                ?? [],

                            borderColor: '#1D4ED8',
                            backgroundColor:
                                'rgba(37, 99, 235, 0.12)',

                            pointBackgroundColor:
                                '#1D4ED8',

                            pointRadius: 4,
                            pointHoverRadius: 6,
                            borderWidth: 3,
                            tension: 0.35,
                            fill: true,
                        },
                    ],
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },

                    plugins: {
                        legend: {
                            display: false,
                        },

                        tooltip: {
                            callbacks: {
                                label(context) {
                                    return currencyFormatter
                                        .format(
                                            Number(
                                                context.raw
                                                ?? 0,
                                            ),
                                        );
                                },
                            },
                        },
                    },

                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },
                        },

                        y: {
                            beginAtZero: true,

                            ticks: {
                                callback(value) {
                                    return currencyFormatter
                                        .format(
                                            Number(value),
                                        );
                                },
                            },
                        },
                    },
                },
            },
        );

        chartInstances.push(monthlyChart);
    }

    window.__larasExpenseAnalysisCharts =
        chartInstances;
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        mountExpenseAnalysisCharts,
        {
            once: true,
        },
    );
} else {
    mountExpenseAnalysisCharts();
}
