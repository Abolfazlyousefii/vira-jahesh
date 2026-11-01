import './bootstrap';
import '../css/vira-phase1.css';
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
};
const refreshIcons = () =>
    createIcons({
        icons: iconSet,
        attrs: { width: 18, height: 18, 'aria-hidden': true },
    });

Alpine.data('workspace', () => ({
    palette: 'violet',
    mode: 'light',
    collapsed: false,
    menu: false,
    init() {
        try {
            const prefs = JSON.parse(
                localStorage.getItem('vira.appearance') || '{}',
            );
            if (['violet', 'ocean', 'rose'].includes(prefs.palette))
                this.palette = prefs.palette;
            if (['light', 'dark'].includes(prefs.mode)) this.mode = prefs.mode;
            this.collapsed = prefs.collapsed === true;
        } catch {
            /* Invalid appearance settings fall back to defaults. */
        }
        this.$watch('palette', () => this.persist());
        this.$watch('mode', () => this.persist());
        this.$watch('collapsed', () => this.persist());
    },
    persist() {
        try {
            localStorage.setItem(
                'vira.appearance',
                JSON.stringify({
                    palette: this.palette,
                    mode: this.mode,
                    collapsed: this.collapsed,
                }),
            );
        } catch {
            /* Appearance remains usable when storage is unavailable. */
        }
    },
}));

document.addEventListener('livewire:navigated', refreshIcons);
document.addEventListener('DOMContentLoaded', refreshIcons);
Livewire.start();
refreshIcons();
