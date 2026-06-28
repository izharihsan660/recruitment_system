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
    value: number | undefined;
    changeText?: string;
    icon: JSX.Element;
    highlightDanger?: boolean;
    href?: string;
}

const hrKpis: KpiCardItem[] = [
    {
        label: 'Total FPK Aktif',
        value: undefined,
        icon: <ClipboardList className="h-5 w-5" />,
    },
    {
        label: 'Menunggu Approval',
        value: undefined,
        icon: <GitPullRequest className="h-5 w-5" />,
    },
    {
        label: 'Lowongan Aktif',
        value: undefined,
        icon: <Briefcase className="h-5 w-5" />,
    },
    {
        label: 'Kandidat di Pipeline',
        value: undefined,
        icon: <Users className="h-5 w-5" />,
    },
    {
        label: 'Hired Bulan Ini',
        value: undefined,
        icon: <UserCheck className="h-5 w-5" />,
    },
    {
        label: 'Ukuran Talent Pool',
        value: undefined,
        icon: <Users className="h-5 w-5" />,
    },
    {
        label: 'Probation Berjalan',
        value: undefined,
        icon: <Hourglass className="h-5 w-5" />,
    },
    {
        label: 'Probation Jatuh Tempo ≤ 7 Hari',
        value: undefined,
        icon: <Timer className="h-5 w-5" />,
        highlightDanger: true,
    },
];

const hiringManagerKpis: KpiCardItem[] = [
    {
        label: 'FPK Saya',
        value: undefined,
        icon: <ClipboardList className="h-5 w-5" />,
    },
    {
        label: 'Menunggu Approval Saya',
        value: undefined,
        icon: <GitPullRequest className="h-5 w-5" />,
    },
    {
        label: 'Pipeline Dept Saya',
        value: undefined,
        icon: <Users className="h-5 w-5" />,
    },
    {
        label: 'Hired Bulan Ini Dept Saya',
        value: undefined,
        icon: <UserCheck className="h-5 w-5" />,
    },
];

export default function Dashboard({ kpis }: { kpis: Record<string, number> }): JSX.Element {
    const { auth } = usePage<PageProps>().props;

    const role = auth.user?.roles?.[0] ?? '';

    const isHrRole = role === 'hr_recruiter' || role === 'hr_manager' || role === 'admin';
    const cards = (isHrRole ? hrKpis : hiringManagerKpis).map((card) => ({
        ...card,
        value: kpis?.[toKpiKey(card.label)] ?? 0,
        href: card.label.startsWith('Probation') ? '/hr/probation' : undefined,
    }));

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
    const value = item.value ?? 0;

    const body = (
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
                        item.highlightDanger && value > 0
                            ? 'text-red-600'
                            : 'text-slate-900'
                    }`}
                >
                    {value}
                </p>
            </div>
        </article>
    );

    return item.href ? <a href={item.href}>{body}</a> : body;
}

function toKpiKey(label: string): string {
    if (label === 'Probation Berjalan') {
        return 'probation_berjalan';
    }

    if (label === 'Probation Jatuh Tempo ≤ 7 Hari') {
        return 'probation_jatuh_tempo';
    }

    return label.toLowerCase().replace('≤ 7 hari', 'jatuh tempo').replace(/[()]/g, '').replace(/\s+/g, '_');
}
