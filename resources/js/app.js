import './bootstrap';
import '../css/vira-phase1.css';
import '../css/vira-workspace.css';
import '../css/vira-catalog-upgrade.css';
import '../css/vira-reports.css';
import '../css/vira-customer-profile.css';
import '../css/vira-minimal.css';
import '../css/vira-v4.css';
import '../css/vira-customers-360.css';
import '../css/vira-catalog-v2.css';
import '../css/vira-feedback.css';
import { chartShape, chartIsEmpty } from './vira-sales-chart.js';
import {
    Livewire,
    Alpine,
} from '../../vendor/livewire/livewire/dist/livewire.esm';
import {
    createIcons,
    LayoutDashboard,
    ContactRound,
    Boxes,
    ReceiptText,
    ScanLine,
    Store,
    Blocks,
    PanelRightClose,
    Menu,
    Moon,
    Sun,
    ShieldCheck,
    Package,
    LogOut,
    Check,
    ChevronLeft,
    ArrowLeft,
    CheckCheck,
    ArrowRight,
    Users,
    Activity,
    CircleHelp,
    ImagePlus,
    Shapes,
    UserRoundCog,
    UserRound,
    UserPlus,
    Search,
    Plus,
    Trash2,
    LayoutGrid,
    ChevronDown,
    X,
    ExternalLink,
    PackagePlus,
    CalendarPlus,
    ChartNoAxesCombined,
    Wallet,
    Clock,
    ClockAlert,
    CalendarDays,
    UsersRound,
    SearchX,
    Pencil,
    Smartphone,
    Phone,
    MapPin,
    MessageCircle,
    Settings,
} from 'lucide';

const iconSet = {
    LayoutDashboard,
    ContactRound,
    Boxes,
    ReceiptText,
    ScanLine,
    Store,
    Blocks,
    PanelRightClose,
    Menu,
    Moon,
    Sun,
    ShieldCheck,
    Package,
    LogOut,
    Check,
    ChevronLeft,
    ArrowLeft,
    CheckCheck,
    ArrowRight,
    Users,
    Activity,
    CircleHelp,
    ImagePlus,
    Shapes,
    UserRoundCog,
    UserRound,
    UserPlus,
    Search,
    Plus,
    Trash2,
    LayoutGrid,
    ChevronDown,
    X,
    ExternalLink,
    PackagePlus,
    CalendarPlus,
    ChartNoAxesCombined,
    Wallet,
    Clock,
    ClockAlert,
    CalendarDays,
    UsersRound,
    SearchX,
    Pencil,
    Smartphone,
    Phone,
    MapPin,
    MessageCircle,
    Settings,
};
const refreshIcons = () =>
    createIcons({
        icons: iconSet,
        attrs: { width: 18, height: 18, 'aria-hidden': true },
    });

Alpine.data('viraSalesChart', (series) => ({
    period: 'week',
    get points() { return series[this.period] || []; },
    get shape() { return chartShape(this.points); },
    get empty() { return chartIsEmpty(this.points); },
    get total() { return series.formatted?.[this.period] ?? '۰'; },
}));

Alpine.data('workspace', () => ({
    menu: false,
    more: false,
    quickOpen: false,
    searchOpen: false,
    searchTerm: '',
}));

document.addEventListener('livewire:navigated', refreshIcons);
document.addEventListener('DOMContentLoaded', refreshIcons);
Livewire.hook('morphed', refreshIcons);
Livewire.start();
refreshIcons();
