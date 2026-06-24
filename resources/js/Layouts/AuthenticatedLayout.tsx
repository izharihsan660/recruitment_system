import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    Briefcase,
    Building2,
    ChevronLeft,
    ClipboardList,
    Database,
    FolderCog,
    LayoutDashboard,
    LogOut,
    Mail,
    Menu,
    Network,
    ShieldCheck,
    Users,
    UserSquare2,
    Workflow,
    X,
} from 'lucide-react';
import { PropsWithChildren, ReactNode, useMemo, useState } from 'react';

import { PageProps } from '@/types';

interface MenuItem {
    label: string;
    href?: string;
    routeName?: string;
    icon?: ReactNode;
    children?: MenuItem[];
}

export default function AuthenticatedLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>): JSX.Element {
    const { auth, unread_notifications_count } = usePage<PageProps>().props;
    const user = auth.user;

    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

    const role = useMemo((): string => {
        if (!user?.roles || user.roles.length === 0) {
            return '';
        }

        return user.roles[0];
    }, [user]);

    const menuItems = useMemo(() => getMenuItemsByRole(role), [role]);

    const isActive = (item: MenuItem): boolean => {
        if (item.routeName && route().current(item.routeName)) {
            return true;
        }

        if (item.href && currentPathStartsWith(item.href)) {
            return true;
        }

        if (item.children) {
            return item.children.some((child) => isActive(child));
        }

        return false;
    };

    const renderMenuItem = (item: MenuItem): JSX.Element => {
        if (item.children && item.children.length > 0) {
            const expanded = isActive(item);

            return (
                <div key={item.label} className="space-y-1">
                    <div className="px-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        {item.label}
                    </div>
                    <div className="space-y-1">
                        {item.children.map((child) => renderMenuItem(child))}
                    </div>
                    {expanded && <div className="pt-1" />}
                </div>
            );
        }

        const active = isActive(item);

        return (
            <Link
                key={item.label}
                href={item.href ?? '#'}
                className={`flex items-center gap-3 rounded-md px-3 py-2 text-sm transition ${
                    active
                        ? 'bg-blue-600 text-white hover:bg-blue-700'
                        : 'text-slate-700 hover:bg-slate-100'
                }`}
                onClick={() => setMobileSidebarOpen(false)}
            >
                <span className="shrink-0">{item.icon}</span>
                <span>{item.label}</span>
            </Link>
        );
    };

    return (
        <div className="min-h-screen bg-slate-50">
            <div className="lg:hidden">
                <div className="fixed left-0 right-0 top-0 z-40 border-b bg-white px-4 py-3">
                    <div className="flex items-center justify-between">
                        <button
                            type="button"
                            className="rounded-md border border-slate-200 p-2 text-slate-700"
                            onClick={() => setMobileSidebarOpen(true)}
                        >
                            <Menu className="h-5 w-5" />
                        </button>
                        <span className="text-sm font-semibold text-slate-900">
                            Sistem Rekrutmen
                        </span>
                        <Link
                            href={route('notifications.index')}
                            className="relative rounded-md border border-slate-200 p-2 text-slate-700"
                        >
                            <Bell className="h-5 w-5" />
                            {(unread_notifications_count ?? 0) > 0 && (
                                <span className="absolute -right-1 -top-1 rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                    {unread_notifications_count}
                                </span>
                            )}
                        </Link>
                    </div>
                </div>
            </div>

            {mobileSidebarOpen && (
                <button
                    type="button"
                    className="fixed inset-0 z-40 bg-black/30 lg:hidden"
                    onClick={() => setMobileSidebarOpen(false)}
                    aria-label="Tutup sidebar"
                />
            )}

            <aside
                className={`fixed inset-y-0 left-0 z-50 flex w-60 flex-col border-r border-slate-200 bg-white transition-transform duration-200 ${
                    mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
                }`}
            >
                <div className="flex h-16 items-center justify-between border-b px-4">
                    <Link href={route('dashboard')} className="text-sm font-bold text-slate-900">
                        Rekrutmen Internal
                    </Link>
                    <button
                        type="button"
                        className="rounded-md p-1 text-slate-500 lg:hidden"
                        onClick={() => setMobileSidebarOpen(false)}
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <nav className="flex-1 space-y-4 overflow-y-auto px-3 py-4">
                    {menuItems.map((item) => renderMenuItem(item))}
                </nav>

                <div className="border-t px-4 py-4">
                    <div className="mb-3 rounded-md bg-slate-100 p-3">
                        <p className="text-sm font-semibold text-slate-900">{user?.name}</p>
                        <p className="text-xs capitalize text-slate-600">{formatRoleLabel(role)}</p>
                    </div>

                    <button
                        type="button"
                        onClick={() => router.post(route('logout'))}
                        className="flex w-full items-center justify-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"
                    >
                        <LogOut className="h-4 w-4" />
                        <span>Logout</span>
                    </button>
                </div>
            </aside>

            <div className="lg:pl-60">
                <header className="sticky top-0 z-30 border-b bg-white/95 backdrop-blur">
                    <div className="flex h-16 items-center justify-between px-4 lg:px-6">
                        <div className="flex items-center gap-3">
                            <div className="hidden text-sm text-slate-500 lg:flex">
                                <ChevronLeft className="h-4 w-4 rotate-180" />
                            </div>
                            {header ? (
                                <div>{header}</div>
                            ) : (
                                <h1 className="text-lg font-semibold text-slate-900">Dashboard</h1>
                            )}
                        </div>

                        <Link
                            href={route('notifications.index')}
                            className="relative rounded-md border border-slate-200 p-2 text-slate-700 hover:bg-slate-100"
                        >
                            <Bell className="h-5 w-5" />
                            {(unread_notifications_count ?? 0) > 0 && (
                                <span className="absolute -right-1 -top-1 rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                    {unread_notifications_count}
                                </span>
                            )}
                        </Link>
                    </div>
                </header>

                <main className="h-[calc(100vh-4rem)] overflow-y-auto p-4 lg:p-6">{children}</main>
            </div>
        </div>
    );
}

function currentPathStartsWith(path: string): boolean {
    if (typeof window !== 'undefined') {
        return window.location.pathname.startsWith(path);
    }

    return false;
}

function formatRoleLabel(role: string): string {
    return role.replaceAll('_', ' ');
}

function getMenuItemsByRole(role: string): MenuItem[] {
    const adminMenus: MenuItem[] = [
        {
            label: 'Dashboard',
            routeName: 'dashboard',
            href: route('dashboard'),
            icon: <LayoutDashboard className="h-4 w-4" />,
        },
        {
            label: 'Master Data',
            children: [
                {
                    label: 'Entitas (PT)',
                    href: '/admin/entities',
                    icon: <Building2 className="h-4 w-4" />,
                },
                {
                    label: 'Departemen',
                    href: '/admin/departments',
                    icon: <Database className="h-4 w-4" />,
                },
                {
                    label: 'Approval Chain',
                    href: '/admin/approval-chains',
                    icon: <Workflow className="h-4 w-4" />,
                },
                {
                    label: 'Company Signer',
                    href: '/admin/company-signers',
                    icon: <ShieldCheck className="h-4 w-4" />,
                },
                {
                    label: 'Source Kandidat',
                    href: '/admin/candidate-sources',
                    icon: <Users className="h-4 w-4" />,
                },
            ],
        },
        {
            label: 'Konfigurasi',
            children: [
                {
                    label: 'SMTP',
                    href: '/admin/smtp',
                    icon: <Mail className="h-4 w-4" />,
                },
                {
                    label: 'Microsoft Graph API',
                    href: '/admin/graph-api',
                    icon: <Network className="h-4 w-4" />,
                },
                {
                    label: 'DocuSeal',
                    href: '/admin/docuseal',
                    icon: <FolderCog className="h-4 w-4" />,
                },
                {
                    label: 'CMS Portal',
                    href: '/admin/cms',
                    icon: <FolderCog className="h-4 w-4" />,
                },
            ],
        },
    ];

    const hrMenus: MenuItem[] = [
        {
            label: 'Dashboard',
            routeName: 'dashboard',
            href: route('dashboard'),
            icon: <LayoutDashboard className="h-4 w-4" />,
        },
        {
            label: 'Recruitment Request (FPK)',
            href: '/fpk',
            icon: <ClipboardList className="h-4 w-4" />,
        },
        {
            label: 'Lowongan Kerja',
            href: '/job-postings',
            icon: <Briefcase className="h-4 w-4" />,
        },
        {
            label: 'Pipeline Kandidat',
            href: '/pipeline',
            icon: <Workflow className="h-4 w-4" />,
        },
        {
            label: 'Input Kandidat',
            href: '/hr/candidates/input',
            icon: <UserSquare2 className="h-4 w-4" />,
        },
        {
            label: 'Email Intake',
            href: '/hr/email-intake',
            icon: <Mail className="h-4 w-4" />,
        },
        {
            label: 'Talent Pool',
            href: '/hr/talent-pool',
            icon: <Users className="h-4 w-4" />,
        },
        {
            label: 'Karyawan Aktif',
            href: '/hr/employees',
            icon: <Users className="h-4 w-4" />,
        },
        {
            label: 'Pre-boarding',
            href: '/hr/preboarding',
            icon: <ClipboardList className="h-4 w-4" />,
        },
        {
            label: 'Probation',
            href: '/hr/probation',
            icon: <ClipboardList className="h-4 w-4" />,
        },
    ];

    const hiringManagerMenus: MenuItem[] = [
        {
            label: 'Dashboard',
            routeName: 'dashboard',
            href: route('dashboard'),
            icon: <LayoutDashboard className="h-4 w-4" />,
        },
        {
            label: 'Recruitment Request (FPK)',
            href: '/fpk',
            icon: <ClipboardList className="h-4 w-4" />,
        },
        {
            label: 'Pipeline Kandidat',
            href: '/pipeline',
            icon: <Workflow className="h-4 w-4" />,
        },
        {
            label: 'Probation',
            href: '/hr/probation',
            icon: <ClipboardList className="h-4 w-4" />,
        },
    ];

    const preboardingMenus: MenuItem[] = [
        {
            label: 'Pre-boarding Tasks',
            href: '/preboarding',
            icon: <ClipboardList className="h-4 w-4" />,
        },
    ];

    if (role === 'admin') {
        return adminMenus;
    }

    if (role === 'hr_recruiter' || role === 'hr_manager') {
        return hrMenus;
    }

    if (role === 'hiring_manager' || role === 'approver') {
        return hiringManagerMenus;
    }

    if (role === 'pic_preboarding') {
        return preboardingMenus;
    }

    return [
        {
            label: 'Dashboard',
            routeName: 'dashboard',
            href: route('dashboard'),
            icon: <LayoutDashboard className="h-4 w-4" />,
        },
    ];
}
