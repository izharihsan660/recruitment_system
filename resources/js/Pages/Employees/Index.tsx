import { Badge, Card, EmptyState, FormLabel, SelectInput, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

interface Department {
    id: number;
    name: string;
}

interface Entity {
    id: number;
    name: string;
}

interface PreboardingProgress {
    done: number;
    total: number;
}

interface ProbationRecord {
    status: string;
}

interface Employee {
    id: number;
    full_name: string;
    employee_id: string | null;
    position_name: string;
    start_date: string;
    status: 'active' | 'inactive';
    department?: Department | null;
    entity?: Entity | null;
    preboarding_progress?: PreboardingProgress | null;
    probation_record?: ProbationRecord | null;
}

interface PaginatedEmployees {
    data: Employee[];
}

interface EmployeeFilters {
    search: string;
    department_id: string;
    entity_id: string;
    status: string;
}

interface IndexProps {
    employees: PaginatedEmployees;
    filters: EmployeeFilters;
    departments: Department[];
    entities: Entity[];
}

export default function Index({ employees, filters, departments, entities }: IndexProps): JSX.Element {
    function updateFilter(key: keyof EmployeeFilters, value: string): void {
        const nextFilters = { ...filters, [key]: value };

        router.get('/hr/employees', compactFilters(nextFilters), {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Karyawan Aktif</h1>}>
            <Head title="Karyawan Aktif" />

            <div className="space-y-4">
                <Card className="grid gap-4 p-4 md:grid-cols-4">
                    <div className="md:col-span-1">
                        <FormLabel>Cari</FormLabel>
                        <TextInput
                            value={filters.search}
                            onChange={(event) => updateFilter('search', event.target.value)}
                            placeholder="Nama atau Employee ID"
                        />
                    </div>
                    <div>
                        <FormLabel>Departemen</FormLabel>
                        <SelectInput value={filters.department_id} onChange={(event) => updateFilter('department_id', event.target.value)}>
                            <option value="">Semua Departemen</option>
                            {departments.map((department) => (
                                <option key={department.id} value={String(department.id)}>{department.name}</option>
                            ))}
                        </SelectInput>
                    </div>
                    <div>
                        <FormLabel>Entitas/PT</FormLabel>
                        <SelectInput value={filters.entity_id} onChange={(event) => updateFilter('entity_id', event.target.value)}>
                            <option value="">Semua Entitas</option>
                            {entities.map((entity) => (
                                <option key={entity.id} value={String(entity.id)}>{entity.name}</option>
                            ))}
                        </SelectInput>
                    </div>
                    <div>
                        <FormLabel>Status</FormLabel>
                        <SelectInput value={filters.status} onChange={(event) => updateFilter('status', event.target.value)}>
                            <option value="all">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </SelectInput>
                    </div>
                </Card>

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
                                    <p className="text-sm text-slate-500">{employee.entity?.name ?? '-'}</p>
                                    <p className="mt-0.5 text-xs text-slate-400">
                                        {employee.employee_id ?? '-'} · Mulai: {formatDate(employee.start_date)}
                                    </p>
                                </div>
                                <div className="flex shrink-0 flex-col items-end gap-2 text-right">
                                    <EmployeeStatusBadge status={employee.status} />
                                    <div className="flex flex-col items-end gap-1 text-xs text-slate-500 sm:flex-row sm:items-center sm:gap-2">
                                        {employee.preboarding_progress && (
                                            <span>
                                                Pre-boarding: {employee.preboarding_progress.done}/{employee.preboarding_progress.total}
                                            </span>
                                        )}
                                        <span>Probation: {probationLabel(employee.probation_record?.status)}</span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    ))}

                    {employees.data.length === 0 && <EmptyState title="Tidak ada karyawan" description="Ubah filter untuk melihat data karyawan lain." />}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function compactFilters(filters: EmployeeFilters): Partial<EmployeeFilters> {
    return Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '')) as Partial<EmployeeFilters>;
}

function EmployeeStatusBadge({ status }: { status: 'active' | 'inactive' }): JSX.Element {
    return <Badge tone={status === 'active' ? 'green' : 'red'}>{status === 'active' ? 'Aktif' : 'Tidak Aktif'}</Badge>;
}

function probationLabel(status?: string): string {
    if (!status) {
        return '-';
    }

    if (status === 'permanent') {
        return 'Permanent';
    }

    return status
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
        .replace('Day30', '30-Day')
        .replace('Day60', '60-Day')
        .replace('Day90', '90-Day');
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
