import Alpine from 'alpinejs';

import {
    Archive,
    ArchiveRestore,
    ArrowDown,
    ArrowUp,
    ArrowLeftRight,
    Bell,
    CalendarDays,
    ChartNoAxesCombined,
    ChevronDown,
    CircleDollarSign,
    CircleUserRound,
    createIcons,
    Landmark,
    LayoutDashboard,
    Lightbulb,
    ListTodo,
    LogOut,
    Menu,
    Pencil,
    Plus,
    Search,
    Settings,
    Smartphone,
    Sparkles,
    Wallet,
    WalletCards,
    X,
} from 'lucide';

window.Alpine = Alpine;

Alpine.start();

const larasIcons = {
    ArrowLeftRight,
    Archive,
    ArchiveRestore,
    ArrowDown,
    ArrowUp,
    Bell,
    CalendarDays,
    ChartNoAxesCombined,
    ChevronDown,
    CircleDollarSign,
    CircleUserRound,
    Landmark,
    LayoutDashboard,
    Lightbulb,
    ListTodo,
    LogOut,
    Menu,
    Pencil,
    Plus,
    Search,
    Settings,
    Smartphone,
    Sparkles,
    Wallet,
    WalletCards,
    X,
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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderIcons);
} else {
    renderIcons();
}
