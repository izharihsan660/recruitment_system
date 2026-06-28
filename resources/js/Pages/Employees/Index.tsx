import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

interface Department {
    name: string;
}

interface PreboardingProgress {
    done: number;
    total: number;
}

interface Employee {
    id: number;
    full_name: string;
    employee_id: string;
    position_name: string;
    start_date: string;
    department?: Department | null;
    preboarding_progress?: PreboardingProgress | null;
}

interface PaginatedEmployees {
    data: Employee[];
}

export default function Index({ employees }: { employees: PaginatedEmployees }): JSX.Element {
    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Karyawan Aktif</h1>}>
            <Head title="Karyawan Aktif" />

            <div className="space-y-3">
                {employees.data.map((employee) => (
                    <Link key={employee.id} href={`/hr/employees/${employee.id}`} className="block">
                        <div className="flex items-center gap-4 rounded-lg border bg-white p-4 transition hover:bg-slate-50">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                                {initials(employee.full_name)}
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="font-semibold text-slate-900">{employee.full_name}</p>
                                <p className="text-sm text-slate-500">
                                    {employee.position_name} · {employee.department?.name ?? '-'}
                                </p>
                                <p className="mt-0.5 text-xs text-slate-400">
                                    {employee.employee_id} · Mulai: {formatDate(employee.start_date)}
                                </p>
                            </div>
                            <div className="flex shrink-0 flex-col items-end gap-2 text-right">
                                <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Aktif</span>
                                {employee.preboarding_progress && (
                                    <span className="text-xs text-slate-500">
                                        Pre-boarding: {employee.preboarding_progress.done}/{employee.preboarding_progress.total}
                                    </span>
                                )}
                            </div>
                        </div>
                    </Link>
                ))}
            </div>
        </AuthenticatedLayout>
    );
}

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
