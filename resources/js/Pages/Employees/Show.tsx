import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    Badge,
    Button,
    FieldError,
    FormLabel,
    SelectInput,
    TextInput,
} from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Employee {
    id: number;
    full_name: string;
    employee_id: string;
    position_name: string;
    email: string;
    phone?: string;
    start_date: string;
    end_date?: string | null;
    contract_type: string;
    status: string;
    department?: { name: string } | null;
    entity?: { name: string } | null;
}

interface ChecklistItem {
    id: number;
    title: string;
    description?: string | null;
    status: 'pending' | 'in_progress' | 'done';
    assigned_to?: number | null;
    pic?: { id: number; name: string } | null;
}

interface Checklist {
    id: number;
    status: 'not_started' | 'in_progress' | 'completed';
    first_day?: string;
    items: ChecklistItem[];
}

type BadgeTone = 'slate' | 'green' | 'yellow' | 'red' | 'orange' | 'blue';

const preboardingItemStatusMap: Record<ChecklistItem['status'], { label: string; tone: BadgeTone }> = {
    pending: { label: 'Belum Dimulai', tone: 'slate' },
    in_progress: { label: 'Sedang Berjalan', tone: 'yellow' },
    done: { label: 'Selesai', tone: 'green' },
};

const preboardingChecklistStatusMap: Record<Checklist['status'], { label: string; tone: BadgeTone }> = {
    not_started: { label: 'Belum Dimulai', tone: 'slate' },
    in_progress: { label: 'Sedang Berjalan', tone: 'yellow' },
    completed: { label: 'Selesai', tone: 'green' },
};

interface ProbationEvaluation {
    id: number;
    milestone: 'day30' | 'day60' | 'day90' | 'extended';
    performance_notes: string;
    recommendation: string;
    evaluated_at: string;
    evaluator_id: number;
}

type ProbationMilestone = ProbationEvaluation['milestone'];

interface ProbationRecord {
    id: number;
    status: string;
    day30_due: string;
    day60_due: string;
    day90_due: string;
    extended_start_date?: string | null;
    extended_until?: string | null;
    evaluations?: ProbationEvaluation[];
}

interface UserOption {
    id: number;
    name: string;
}

type ActiveTab = 'info' | 'preboarding' | 'probation';

interface EvaluationFormData {
    milestone: string;
    performance_notes: string;
    recommendation?: string;
    extended_start_date?: string;
    extended_end_date?: string;
}

interface EmployeeFormData {
    employee_id: string;
    start_date: string;
    end_date: string;
    contract_type: string;
    status: string;
}

export default function Show({
    employee,
    checklist,
    users,
    probation,
}: {
    employee: Employee;
    checklist: Checklist;
    users: UserOption[];
    probation?: ProbationRecord | null;
}): JSX.Element {
    const { errors } = usePage<PageProps>().props;
    const [activeTab, setActiveTab] = useState<ActiveTab>('info');
    const [deleteItemId, setDeleteItemId] = useState<number | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const itemForm = useForm({ title: '', description: '' });
    const employeeForm = useForm<EmployeeFormData>({
        employee_id: employee.employee_id ?? '',
        start_date: dateInputValue(employee.start_date),
        end_date: dateInputValue(employee.end_date),
        contract_type: employee.contract_type,
        status: employee.status,
    });
    const activeMilestone = probation ? currentMilestone(probation.status) : null;
    const evalForm = useForm<EvaluationFormData>({ milestone: activeMilestone ?? '', performance_notes: '', recommendation: '', extended_start_date: '', extended_end_date: '' });

    function submitItem(event: FormEvent): void {
        event.preventDefault();
        itemForm.post(`/hr/preboarding/${checklist.id}/items`, {
            onSuccess: () => itemForm.reset(),
        });
    }

    function deleteItem(): void {
        if (!deleteItemId) {
            return;
        }

        router.delete(`/hr/preboarding/items/${deleteItemId}`, {
            preserveScroll: true,
            only: ['checklist'],
            onFinish: () => setDeleteItemId(null),
        });
    }

    function completeItem(itemId: number): void {
        router.post(`/hr/preboarding/items/${itemId}/complete`, {}, {
            preserveScroll: true,
            only: ['checklist'],
        });
    }

    function submitEvaluation(event: FormEvent): void {
        event.preventDefault();
        if (!probation) {
            return;
        }

        evalForm.post(`/hr/probation/${probation.id}/evaluate`);
    }

    function submitEmployee(event: FormEvent): void {
        event.preventDefault();

        employeeForm.put(`/hr/employees/${employee.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Detail Karyawan</h1>}>
            <Head title="Detail Karyawan" />
            {Object.keys(errors).length > 0 && (
                <div className="mb-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {Object.values(errors).map((error, index) => <p key={index}>{error}</p>)}
                </div>
            )}

            <div className="rounded-lg border bg-white p-6">
                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-4">
                        <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl font-semibold text-blue-700">
                            {initials(employee.full_name)}
                        </div>
                        <div>
                            <p className="text-2xl font-semibold text-slate-900">{employee.full_name}</p>
                            <p className="text-sm text-slate-500">{employee.employee_id} · {employee.position_name}</p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/hr/employees" className="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Back
                        </Link>
                        <Button type="button" variant="primary" onClick={() => setEditOpen(true)}>
                            Edit
                        </Button>
                    </div>
                </div>

                <div className="mb-6 flex border-b">
                    {(['info', 'preboarding', 'probation'] as ActiveTab[]).map((tab) => (
                        <button
                            key={tab}
                            type="button"
                            onClick={() => setActiveTab(tab)}
                            className={`border-b-2 px-6 py-3 text-sm font-medium transition-colors ${
                                activeTab === tab
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            {tabLabel(tab)}
                        </button>
                    ))}
                </div>

                {activeTab === 'info' && <EmployeeInfoTab employee={employee} />}
                {activeTab === 'preboarding' && (
                    <PreboardingTab
                        checklist={checklist}
                        users={users}
                        itemForm={itemForm}
                        onSubmitItem={submitItem}
                        onDeleteItem={setDeleteItemId}
                        onCompleteItem={completeItem}
                    />
                )}
                {activeTab === 'probation' && (
                    <ProbationTab
                        probation={probation}
                        evalForm={evalForm}
                        onSubmitEvaluation={submitEvaluation}
                    />
                )}
            </div>

            <AlertDialog open={deleteItemId !== null}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus item pre-boarding?</AlertDialogTitle>
                        <AlertDialogDescription>Yakin ingin menghapus item ini? Tindakan ini tidak dapat dibatalkan.</AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => setDeleteItemId(null)}>Batal</AlertDialogCancel>
                        <AlertDialogAction onClick={deleteItem}>Hapus</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
            {editOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                    <div className="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900">Edit Karyawan</h2>
                                <p className="text-sm text-slate-500">Perbarui data kontrak dan status karyawan.</p>
                            </div>
                            <Button type="button" variant="ghost" onClick={() => setEditOpen(false)}>Tutup</Button>
                        </div>

                        <form onSubmit={submitEmployee} className="space-y-5">
                            <div className="grid gap-4 md:grid-cols-2">
                                <ReadOnlyField label="Posisi" value={employee.position_name} />
                                <ReadOnlyField label="Departemen" value={employee.department?.name ?? '-'} />
                                <ReadOnlyField label="Entitas (PT)" value={employee.entity?.name ?? '-'} />
                                <ReadOnlyField label="Email" value={employee.email} />
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <FormLabel>Employee ID</FormLabel>
                                    <TextInput value={employeeForm.data.employee_id} onChange={(event) => employeeForm.setData('employee_id', event.target.value)} />
                                    <FieldError message={employeeForm.errors.employee_id} />
                                </div>
                                <div>
                                    <FormLabel required>Tanggal Mulai</FormLabel>
                                    <TextInput type="date" value={employeeForm.data.start_date} onChange={(event) => employeeForm.setData('start_date', event.target.value)} />
                                    <FieldError message={employeeForm.errors.start_date} />
                                </div>
                                <div>
                                    <FormLabel>Tanggal Selesai</FormLabel>
                                    <TextInput type="date" value={employeeForm.data.end_date} onChange={(event) => employeeForm.setData('end_date', event.target.value)} />
                                    <FieldError message={employeeForm.errors.end_date} />
                                </div>
                                <div>
                                    <FormLabel required>Tipe Kontrak</FormLabel>
                                    <SelectInput value={employeeForm.data.contract_type} onChange={(event) => employeeForm.setData('contract_type', event.target.value)}>
                                        <option value="permanent">Permanent</option>
                                        <option value="contract">Contract</option>
                                        <option value="internship">Internship</option>
                                    </SelectInput>
                                    <FieldError message={employeeForm.errors.contract_type} />
                                </div>
                                <div>
                                    <FormLabel required>Status</FormLabel>
                                    <SelectInput value={employeeForm.data.status} onChange={(event) => employeeForm.setData('status', event.target.value)}>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </SelectInput>
                                    <FieldError message={employeeForm.errors.status} />
                                </div>
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button type="button" variant="secondary" onClick={() => setEditOpen(false)}>Batal</Button>
                                <Button type="submit" disabled={employeeForm.processing}>{employeeForm.processing ? 'Menyimpan...' : 'Simpan'}</Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}

function EmployeeInfoTab({ employee }: { employee: Employee }): JSX.Element {
    const fields = [
        ['Posisi', employee.position_name],
        ['Departemen', employee.department?.name ?? '-'],
        ['Email', employee.email],
        ['Telepon', employee.phone ?? '-'],
        ['Tipe Kontrak', employee.contract_type],
        ['Tanggal Mulai', formatDate(employee.start_date)],
        ['Tanggal Selesai', employee.end_date ? formatDate(employee.end_date) : '-'],
        ['Entitas', employee.entity?.name ?? '-'],
    ];

    return (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            {fields.map(([label, value]) => (
                <div key={label} className="rounded-lg border border-slate-200 p-4">
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-400">{label}</p>
                    <p className="mt-1 font-medium text-slate-900">{value}</p>
                </div>
            ))}
            <div className="rounded-lg border border-slate-200 p-4">
                <p className="text-xs font-medium uppercase tracking-wide text-slate-400">Status</p>
                <div className="mt-2"><EmployeeStatusBadge status={employee.status} /></div>
            </div>
        </div>
    );
}

function ReadOnlyField({ label, value }: { label: string; value: string }): JSX.Element {
    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-400">{label}</p>
            <p className="mt-1 text-sm font-medium text-slate-900">{value}</p>
        </div>
    );
}

function EmployeeStatusBadge({ status }: { status: string }): JSX.Element {
    return <Badge tone={status === 'active' ? 'green' : 'red'}>{status === 'active' ? 'Aktif' : 'Tidak Aktif'}</Badge>;
}

function PreboardingTab({
    checklist,
    users,
    itemForm,
    onSubmitItem,
    onDeleteItem,
    onCompleteItem,
}: {
    checklist: Checklist;
    users: UserOption[];
    itemForm: ReturnType<typeof useForm<{ title: string; description: string }>>;
    onSubmitItem: (event: FormEvent) => void;
    onDeleteItem: (itemId: number) => void;
    onCompleteItem: (itemId: number) => void;
}): JSX.Element {
    const doneCount = checklist.items.filter((item) => item.status === 'done').length;
    const totalCount = checklist.items.length;
    const progress = (doneCount / Math.max(totalCount, 1)) * 100;

    return (
        <div className="space-y-4">
            <div className="rounded-lg border border-slate-200 p-4">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <p className="font-semibold text-slate-900">Progress Pre-boarding</p>
                        <p className="text-sm text-slate-500">{doneCount} dari {totalCount} selesai</p>
                    </div>
                    <PreboardingChecklistStatusBadge status={checklist.status} />
                </div>
                <div className="mt-3 h-2 rounded bg-slate-100">
                    <div className="h-2 rounded bg-blue-600" style={{ width: `${progress}%` }} />
                </div>
            </div>

            <form onSubmit={onSubmitItem} className="grid gap-2 rounded-lg border border-slate-200 p-4 md:grid-cols-[1fr_1fr_auto]">
                <input className="rounded border border-slate-300 p-2 text-sm" placeholder="Judul item" value={itemForm.data.title} onChange={(event) => itemForm.setData('title', event.target.value)} />
                <input className="rounded border border-slate-300 p-2 text-sm" placeholder="Deskripsi (opsional)" value={itemForm.data.description} onChange={(event) => itemForm.setData('description', event.target.value)} />
                <button type="submit" className="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" disabled={itemForm.processing}>Tambah Item</button>
            </form>

            {checklist.items.map((item) => (
                <div key={item.id} className="rounded-lg border border-slate-200 p-4">
                    <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p className="font-medium text-slate-900">{item.title}</p>
                            {item.description && <p className="mt-1 text-sm text-slate-500">{item.description}</p>}
                            <p className="mt-2 text-sm text-slate-500">PIC: {item.pic?.name ?? '-'}</p>
                        </div>
                        <PreboardingItemStatusBadge status={item.status} />
                    </div>
                    <div className="mt-4 flex flex-col gap-2 sm:flex-row">
                        <SelectInput
                            value={String(item.assigned_to ?? '')}
                            onChange={(event) => {
                                router.post(`/hr/preboarding/items/${item.id}/assign`, {
                                    assigned_to: event.target.value,
                                }, { preserveScroll: true, only: ['checklist'] });
                            }}
                        >
                            <option value="">Pilih PIC</option>
                            {users.map((user) => (
                                <option key={user.id} value={String(user.id)}>
                                    {user.name}
                                </option>
                            ))}
                        </SelectInput>
                        {['pending', 'in_progress'].includes(item.status) && (
                            <Button type="button" variant="primary" onClick={() => onCompleteItem(item.id)}>Selesai</Button>
                        )}
                        <Button type="button" variant="danger" onClick={() => onDeleteItem(item.id)}>Hapus</Button>
                    </div>
                </div>
            ))}
        </div>
    );
}

function ProbationTab({
    probation,
    evalForm,
    onSubmitEvaluation,
}: {
    probation?: ProbationRecord | null;
    evalForm: ReturnType<typeof useForm<EvaluationFormData>>;
    onSubmitEvaluation: (event: FormEvent) => void;
}): JSX.Element {
    if (!probation) {
        return <div className="rounded-lg border border-slate-200 p-4 text-sm text-slate-500">Data probation belum tersedia.</div>;
    }

    const day30Eval = probation.evaluations?.find((evaluation) => evaluation.milestone === 'day30');
    const day60Eval = probation.evaluations?.find((evaluation) => evaluation.milestone === 'day60');
    const day90Eval = probation.evaluations?.find((evaluation) => evaluation.milestone === 'day90');
    const extendedEval = probation.evaluations?.find((evaluation) => evaluation.milestone === 'extended');
    const activeMilestone = currentMilestone(probation.status);

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4">
                <div>
                    <p className="font-semibold text-slate-900">Status Probation</p>
                    <p className="text-sm text-slate-500">Pantau milestone 30, 60, dan 90 hari.</p>
                </div>
                <StatusBadge status={probation.status} />
            </div>

            {probation.extended_until && (
                <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                    Probation diperpanjang {probation.extended_start_date ? `mulai ${formatWITA(probation.extended_start_date)} ` : ''}sampai {formatWITA(probation.extended_until)}.
                </div>
            )}

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <MilestoneCard title="30 hari" milestone="day30" dueDate={probation.day30_due} evaluation={day30Eval} isCurrent={activeMilestone === 'day30'} evalForm={evalForm} onSubmitEvaluation={onSubmitEvaluation} />
                <MilestoneCard title="60 hari" milestone="day60" dueDate={probation.day60_due} evaluation={day60Eval} isCurrent={activeMilestone === 'day60'} evalForm={evalForm} onSubmitEvaluation={onSubmitEvaluation} />
                <MilestoneCard title="90 hari" milestone="day90" dueDate={probation.day90_due} evaluation={day90Eval} isCurrent={activeMilestone === 'day90'} evalForm={evalForm} onSubmitEvaluation={onSubmitEvaluation} />
            </div>

            {(probation.status === 'extended' || extendedEval) && (
                <MilestoneCard title="Evaluasi Extended" milestone="extended" dueDate={probation.extended_until ?? probation.day90_due} evaluation={extendedEval} isCurrent={activeMilestone === 'extended'} evalForm={evalForm} onSubmitEvaluation={onSubmitEvaluation} />
            )}
        </div>
    );
}

function MilestoneCard({
    title,
    milestone,
    dueDate,
    evaluation,
    isCurrent,
    evalForm,
    onSubmitEvaluation,
}: {
    title: string;
    milestone: ProbationMilestone;
    dueDate: string;
    evaluation?: ProbationEvaluation;
    isCurrent: boolean;
    evalForm: ReturnType<typeof useForm<EvaluationFormData>>;
    onSubmitEvaluation: (event: FormEvent) => void;
}): JSX.Element {
    const canEvaluate = isCurrent && isDue(dueDate);
    const badge = milestoneBadge(evaluation, canEvaluate, milestone);
    const isFinalMilestone = milestone === 'day90' || milestone === 'extended';
    const canExtend = milestone === 'day90';
    const submitLabel = isFinalMilestone ? (milestone === 'extended' ? 'Simpan & Tentukan Outcome Final' : 'Simpan & Tentukan Outcome') : 'Simpan Evaluasi';
    const isActiveForm = evalForm.data.milestone === milestone;

    return (
        <div className="space-y-3 rounded-lg border border-slate-200 p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-semibold text-slate-900">{title}</p>
                    <p className="mt-1 text-sm text-slate-500">Due: {formatWITA(dueDate)}</p>
                </div>
                <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${badge.className}`}>{badge.label}</span>
            </div>

            {evaluation && (
                <div className="space-y-2 rounded-md bg-slate-50 p-3 text-sm">
                    <p className="text-slate-500">Dievaluasi: {formatWITA(evaluation.evaluated_at)}</p>
                    <div>
                        <p className="font-medium text-slate-700">Catatan</p>
                        <p className="text-slate-600">{evaluation.performance_notes}</p>
                    </div>
                    {isFinalMilestone && (
                        <div>
                            <p className="font-medium text-slate-700">Outcome</p>
                            <p className="text-slate-600">{statusLabel(evaluation.recommendation)}</p>
                        </div>
                    )}
                </div>
            )}

            {!evaluation && canEvaluate && (
                <form onSubmit={onSubmitEvaluation} className="space-y-3">
                    <p className="font-semibold text-slate-900">Form Evaluasi</p>
                    <textarea
                        className="min-h-24 w-full rounded border border-slate-300 p-2 text-sm focus:border-blue-500 focus:outline-none"
                        placeholder="Catatan evaluasi hiring manager"
                        value={isActiveForm ? evalForm.data.performance_notes : ''}
                        onChange={(event) => {
                            evalForm.setData('milestone', milestone);
                            evalForm.setData('performance_notes', event.target.value);
                        }}
                    />
                    {isFinalMilestone && (
                        <div className="space-y-3">
                            <SelectInput
                                value={isActiveForm ? evalForm.data.recommendation ?? '' : ''}
                                onChange={(event) => {
                                    evalForm.setData('milestone', milestone);
                                    evalForm.setData('recommendation', event.target.value);
                                }}
                            >
                                <option value="">Pilih outcome</option>
                                <option value="permanent">Permanent</option>
                                {canExtend && <option value="extended">Extended</option>}
                                <option value="terminated">Terminated</option>
                            </SelectInput>
                            {canExtend && isActiveForm && evalForm.data.recommendation === 'extended' && (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <FormLabel required>Tanggal Mulai Extended</FormLabel>
                                        <TextInput type="date" value={evalForm.data.extended_start_date ?? ''} onChange={(event) => evalForm.setData('extended_start_date', event.target.value)} />
                                    </div>
                                    <div>
                                        <FormLabel required>Tanggal Selesai Extended</FormLabel>
                                        <TextInput type="date" value={evalForm.data.extended_end_date ?? ''} onChange={(event) => evalForm.setData('extended_end_date', event.target.value)} />
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                    <button type="submit" className="rounded bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700" disabled={evalForm.processing || !isActiveForm}>{submitLabel}</button>
                </form>
            )}
        </div>
    );
}

function milestoneBadge(evaluation: ProbationEvaluation | undefined, isCurrent: boolean, milestone?: ProbationMilestone): { label: string; className: string } {
    if (evaluation) {
        if (milestone === 'extended') {
            return { label: 'Extended', className: 'bg-blue-100 text-blue-700' };
        }

        return { label: 'Selesai', className: 'bg-green-100 text-green-700' };
    }

    if (isCurrent) {
        return { label: 'Perlu Evaluasi', className: 'bg-yellow-100 text-yellow-700' };
    }

    return { label: 'Belum', className: 'bg-slate-100 text-slate-500' };
}

function PreboardingItemStatusBadge({ status }: { status: ChecklistItem['status'] }): JSX.Element {
    const config = preboardingItemStatusMap[status];

    return <Badge tone={config.tone}>{config.label}</Badge>;
}

function PreboardingChecklistStatusBadge({ status }: { status: Checklist['status'] }): JSX.Element {
    const config = preboardingChecklistStatusMap[status];

    return <Badge tone={config.tone}>{config.label}</Badge>;
}

function StatusBadge({ status }: { status: string }): JSX.Element {
    const color = statusColor(status);

    return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${color}`}>{statusLabel(status)}</span>;
}

function statusColor(status: string): string {
    if (['done', 'completed', 'permanent', 'selesai'].includes(status)) {
        return 'bg-green-100 text-green-700';
    }

    if (['in_progress', 'day30_review', 'day60_review', 'day90_review', '90_day_review', 'extended'].includes(status)) {
        return 'bg-blue-100 text-blue-700';
    }

    if (['terminated', 'rejected'].includes(status)) {
        return 'bg-red-100 text-red-700';
    }

    return 'bg-slate-100 text-slate-700';
}

function statusLabel(status: string): string {
    const labels: Record<string, string> = {
        in_progress: 'Berjalan',
        day30_review: '30-Day Review',
        day60_review: '60-Day Review',
        day90_review: '90-Day Review',
        extended: 'Extended',
        permanent: 'Permanent',
        terminated: 'Terminated',
    };

    return labels[status] ?? status.replaceAll('_', ' ');
}

function currentMilestone(status: string): ProbationMilestone | null {
    const milestones: Record<string, ProbationMilestone> = {
        day30_review: 'day30',
        day60_review: 'day60',
        day90_review: 'day90',
        extended: 'extended',
    };

    return milestones[status] ?? null;
}

function tabLabel(tab: ActiveTab): string {
    return tab === 'info' ? 'Info Karyawan' : tab === 'preboarding' ? 'Pre-boarding' : 'Probation';
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

function dateInputValue(date?: string | null): string {
    if (!date) {
        return '';
    }

    return date.slice(0, 10);
}

function isDue(date?: string | null): boolean {
    if (!date) {
        return false;
    }

    return new Date(date).setHours(0, 0, 0, 0) <= new Date().setHours(0, 0, 0, 0);
}

function formatWITA(isoString?: string | null): string {
    if (!isoString) {
        return '-';
    }

    return new Date(isoString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Makassar',
    });
}
