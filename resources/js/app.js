import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import './features/expense-analysis';
import './features/settings';
import './features/data-deletion';
import './ui/loading-screen';
import './ui/toast';
import './ui/confirm-dialog';

import {
    createIcons,
    Archive,
    ArchiveRestore,
    ArrowDown,
    ArrowDownLeft,
    ArrowLeftRight,
    ArrowUp,
    ArrowUpRight,
    Ban,
    Bell,
    CalendarDays,
    ChartNoAxesCombined,
    ChevronDown,
    CircleDollarSign,
    CircleUserRound,
    Eye,
    Landmark,
    LayoutDashboard,
    Lightbulb,
    ListTodo,
    LogOut,
    Menu,
    Pencil,
    Plus,
    ReceiptText,
    Search,
    Settings,
    Smartphone,
    Sparkles,
    Wallet,
    WalletCards,
    X,
    AlarmClock,
    Check,
    Clock,
    Flag,
    MapPin,
    Play,
    RotateCcw,
    SlidersHorizontal,
    BellRing,
    CheckCheck,
    CircleAlert,
    CircleCheck,
    CirclePause,
    Repeat2,
    Camera,
    Image,
    Trash2,
    ShieldCheck,
    KeyRound,
    MonitorSmartphone,
    History,
    Download,
    FileJson,
    TriangleAlert,
    Database,
    ArrowLeft,
    Save,
    PauseCircle,
    PlayCircle,
    PiggyBank,
} from 'lucide';

window.Alpine = Alpine;

Alpine.start();

const larasIcons = {
    Archive,
    ArchiveRestore,
    ArrowDown,
    ArrowDownLeft,
    ArrowLeftRight,
    ArrowUp,
    ArrowUpRight,
    Ban,
    Bell,
    CalendarDays,
    ChartNoAxesCombined,
    ChevronDown,
    CircleDollarSign,
    CircleUserRound,
    Eye,
    Landmark,
    LayoutDashboard,
    Lightbulb,
    ListTodo,
    LogOut,
    Menu,
    Pencil,
    Plus,
    ReceiptText,
    Search,
    Settings,
    Smartphone,
    Sparkles,
    Wallet,
    WalletCards,
    X,
    AlarmClock,
    Check,
    Clock,
    Flag,
    MapPin,
    Play,
    RotateCcw,
    SlidersHorizontal,
    BellRing,
    CheckCheck,
    CircleAlert,
    CircleCheck,
    CirclePause,
    Repeat2,
    History,
    Camera,
    Image,
    Trash2,
    ShieldCheck,
    KeyRound,
    MonitorSmartphone,
    Download,
    FileJson,
    TriangleAlert,
    Database,
    ArrowLeft,
    Save,
    PauseCircle,
    PlayCircle,
    PiggyBank,
};

const renderIcons = () => {
    createIcons({
        icons: larasIcons,
        attrs: {
            'stroke-width': 1.8,
            'aria-hidden': 'true',
        },
    });
};

document.addEventListener(
    'laras:icons-refresh',
    renderIcons
);

const readJsonPayload = (elementId) => {
    const element = document.getElementById(elementId);

    if (! element) {
        return null;
    }

    try {
        return JSON.parse(element.textContent);
    } catch (error) {
        console.error(
            `Data grafik ${elementId} tidak valid.`,
            error
        );

        return null;
    }
};

const currencyFormatter = (currency) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    });
};

const compactNumberFormatter = new Intl.NumberFormat(
    'id-ID',
    {
        notation: 'compact',
        maximumFractionDigits: 1,
    }
);

const renderCashFlowChart = () => {
    const canvas = document.getElementById(
        'dashboard-cash-flow-chart'
    );

    const payload = readJsonPayload(
        'dashboard-cash-flow-data'
    );

    if (! canvas || ! payload) {
        return;
    }

    const formatter = currencyFormatter(
        payload.currency ?? 'IDR'
    );

    new Chart(canvas, {
        type: 'bar',

        data: {
            labels: payload.labels,

            datasets: [
                {
                    label: 'Pemasukan',
                    data: payload.income,
                    backgroundColor:
                        'rgba(16, 185, 129, 0.78)',
                    borderColor:
                        'rgb(5, 150, 105)',
                    borderWidth: 1,
                    borderRadius: 7,
                    maxBarThickness: 26,
                },
                {
                    label: 'Pengeluaran',
                    data: payload.expense,
                    backgroundColor:
                        'rgba(244, 63, 94, 0.72)',
                    borderColor:
                        'rgb(225, 29, 72)',
                    borderWidth: 1,
                    borderRadius: 7,
                    maxBarThickness: 26,
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
                    position: 'bottom',

                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 20,
                    },
                },

                tooltip: {
                    callbacks: {
                        label(context) {
                            const label =
                                context.dataset.label ?? '';

                            const value = Number(
                                context.parsed.y ?? 0
                            );

                            return `${label}: ${formatter.format(value)}`;
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
                        maxTicksLimit: 10,
                    },
                },

                y: {
                    beginAtZero: true,

                    border: {
                        display: false,
                    },

                    grid: {
                        color: 'rgba(148, 163, 184, 0.16)',
                    },

                    ticks: {
                        callback(value) {
                            return compactNumberFormatter.format(
                                Number(value)
                            );
                        },
                    },
                },
            },
        },
    });
};

const renderCategoryChart = () => {
    const canvas = document.getElementById(
        'dashboard-category-chart'
    );

    const payload = readJsonPayload(
        'dashboard-category-data'
    );

    if (
        ! canvas
        || ! payload
        || payload.values.length === 0
    ) {
        return;
    }

    const formatter = currencyFormatter(
        payload.currency ?? 'IDR'
    );

    new Chart(canvas, {
        type: 'doughnut',

        data: {
            labels: payload.labels,

            datasets: [
                {
                    data: payload.values,
                    backgroundColor: payload.colors,
                    borderColor: '#FFFFFF',
                    borderWidth: 3,
                    hoverOffset: 5,
                },
            ],
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',

            plugins: {
                legend: {
                    display: false,
                },

                tooltip: {
                    callbacks: {
                        label(context) {
                            const label =
                                context.label ?? '';

                            const value = Number(
                                context.parsed ?? 0
                            );

                            return `${label}: ${formatter.format(value)}`;
                        },
                    },
                },
            },
        },
    });
};

const initializeApplication = () => {
    renderIcons();
    renderCashFlowChart();
    renderCategoryChart();
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeApplication
    );
} else {
    initializeApplication();
}
