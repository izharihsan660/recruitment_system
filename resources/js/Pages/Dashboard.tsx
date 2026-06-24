import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import {
    Briefcase,
    ClipboardList,
    GitPullRequest,
    Hourglass,
    Timer,
    UserCheck,
    Users,
} from 'lucide-react';

import { PageProps } from '@/types';

interface KpiCardItem {
    label: string;
    value: number;
    changeText?: string;
    icon: JSX.Element;
    highlightDanger?: boolean;
}

const mockHrKpis: KpiCardItem[] = [
    {
        label: 'Total FPK Aktif',
        value: 18,
        icon: <ClipboardList className="h-5 w-5" />,
        changeText: '+2 dari bulan lalu',
    },
    {
        label: 'Menunggu Approval',
        value: 6,
        icon: <GitPullRequest className="h-5 w-5" />,
    },
    {
        label: 'Lowongan Aktif',
        value: 12,
        icon: <Briefcase className="h-5 w-5" />,
    },
    {
        label: 'Kandidat di Pipeline',
        value: 77,
        icon: <Users className="h-5 w-5" />,
    },
    {
        label: 'Hired Bulan Ini',
        value: 9,
        icon: <UserCheck className="h-5 w-5" />,
    },
    {
        label: 'Ukuran Talent Pool',
        value: 143,
        icon: <Users className="h-5 w-5" />,
    },
    {
        label: 'Probation Berjalan',
        value: 11,
        icon: <Hourglass className="h-5 w-5" />,
    },
    {
        label: 'Probation Jatuh Tempo ≤ 7 Hari',
        value: 3,
        icon: <Timer className="h-5 w-5" />,
        highlightDanger: true,
    },
];

const mockHiringManagerKpis: KpiCardItem[] = [
    {
        label: 'FPK Saya',
        value: 4,
        icon: <ClipboardList className="h-5 w-5" />,
    },
    {
        label: 'Menunggu Approval Saya',
        value: 2,
        icon: <GitPullRequest className="h-5 w-5" />,
    },
    {
        label: 'Pipeline Dept Saya',
        value: 19,
        icon: <Users className="h-5 w-5" />,
    },
    {
        label: 'Hired Bulan Ini Dept Saya',
        value: 2,
        icon: <UserCheck className="h-5 w-5" />,
    },
];

export default function Dashboard(): JSX.Element {
    const { auth } = usePage<PageProps>().props;

    const role = auth.user?.roles?.[0] ?? '';

    const isHrRole = role === 'hr_recruiter' || role === 'hr_manager' || role === 'admin';
    const cards = isHrRole ? mockHrKpis : mockHiringManagerKpis;

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold text-slate-900">Dashboard</h1>}>
            <Head title="Dashboard" />

            <section className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                {cards.map((item) => (
                    <KpiCard key={item.label} item={item} />
                ))}
            </section>
        </AuthenticatedLayout>
    );
}

function KpiCard({ item }: { item: KpiCardItem }): JSX.Element {
    return (
        <article
            className={`rounded-lg border bg-white p-4 shadow-sm ${
                item.highlightDanger ? 'border-red-300' : 'border-slate-200'
            }`}
        >
            <div className="mb-3 flex items-center justify-between">
                <div className="rounded-md bg-blue-50 p-2 text-blue-600">{item.icon}</div>
                {item.changeText && (
                    <span className="text-[11px] font-medium text-slate-500">{item.changeText}</span>
                )}
            </div>
            <div className="space-y-1">
                <p className="text-xs font-medium text-slate-500">{item.label}</p>
                <p
                    className={`text-2xl font-bold ${
                        item.highlightDanger && item.value > 0
                            ? 'text-red-600'
                            : 'text-slate-900'
                    }`}
                >
                    {item.value}
                </p>
            </div>
        </article>
    );
}
