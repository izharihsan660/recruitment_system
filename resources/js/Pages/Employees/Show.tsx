import ConfirmDialog from '@/Components/ConfirmDialog';
import { SelectInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
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
    status: string;
    first_day?: string;
    items: ChecklistItem[];
}

interface ProbationEvaluation {
    id: number;
    milestone: 'day30' | 'day60' | 'day90';
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
    recommendation: string;
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
    const [confirmTerminate, setConfirmTerminate] = useState(false);
    const itemForm = useForm({ title: '', description: '' });
    const activeMilestone = probation ? currentMilestone(probation.status) : null;
    const evalForm = useForm<EvaluationFormData>({ milestone: activeMilestone ?? '', performance_notes: '', recommendation: 'permanent' });
    const outcomeForm = useForm({ outcome: 'permanent', extended_until: '' });

    const canSubmitOutcome = probation ? ['90_day_review', 'day90_review', 'extended'].includes(probation.status) : false;

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

        router.delete(`/hr/preboarding/items/${deleteItemId}`, { onFinish: () => setDeleteItemId(null) });
    }

    function submitEvaluation(event: FormEvent): void {
        event.preventDefault();
        if (!probation) {
            return;
        }

        evalForm.post(`/hr/probation/${probation.id}/evaluate`);
    }

    function submitOutcome(event?: FormEvent): void {
        event?.preventDefault();
        if (!probation) {
            return;
        }

        outcomeForm.post(`/hr/probation/${probation.id}/outcome`, { onFinish: () => setConfirmTerminate(false) });
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
                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl font-semibold text-blue-700">
                        {initials(employee.full_name)}
                    </div>
                    <div>
                        <p className="text-2xl font-semibold text-slate-900">{employee.full_name}</p>
                        <p className="text-sm text-slate-500">{employee.employee_id} · {employee.position_name}</p>
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
                    />
                )}
                {activeTab === 'probation' && (
                    <ProbationTab
                        probation={probation}
                        evalForm={evalForm}
                        outcomeForm={outcomeForm}
                        canSubmitOutcome={canSubmitOutcome}
                        onSubmitEvaluation={submitEvaluation}
                        onSubmitOutcome={(event) => {
                            event.preventDefault();
                            outcomeForm.data.outcome === 'terminated' ? setConfirmTerminate(true) : submitOutcome();
                        }}
                    />
                )}
            </div>

            <ConfirmDialog open={deleteItemId !== null} title="Hapus item preboarding?" message="Item checklist ini akan dihapus." confirmLabel="Ya, Hapus" onConfirm={deleteItem} onCancel={() => setDeleteItemId(null)} />
            <ConfirmDialog open={confirmTerminate} title="Terminate probation?" message="Karyawan akan ditandai terminasi dari proses probation." confirmLabel="Ya, Terminasi" onConfirm={() => submitOutcome()} onCancel={() => setConfirmTerminate(false)} />
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
        ['Status', employee.status],
    ];

    return (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            {fields.map(([label, value]) => (
                <div key={label} className="rounded-lg border border-slate-200 p-4">
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-400">{label}</p>
                    <p className="mt-1 font-medium text-slate-900">{value}</p>
                </div>
            ))}
        </div>
    );
}

function PreboardingTab({
    checklist,
    users,
    itemForm,
    onSubmitItem,
    onDeleteItem,
}: {
    checklist: Checklist;
    users: UserOption[];
    itemForm: ReturnType<typeof useForm<{ title: string; description: string }>>;
    onSubmitItem: (event: FormEvent) => void;
    onDeleteItem: (itemId: number) => void;
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
                    <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">{checklist.status}</span>
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
                        <StatusBadge status={item.status} />
                    </div>
                    <div className="mt-4 flex flex-col gap-2 sm:flex-row">
                        <SelectInput
                            value={String(item.assigned_to ?? '')}
                            onChange={(event) => {
                                router.post(`/hr/preboarding/items/${item.id}/assign`, {
                                    user_id: event.target.value,
                                }, { preserveScroll: true });
                            }}
                        >
                            <option value="">Pilih PIC</option>
                            {users.map((user) => (
                                <option key={user.id} value={String(user.id)}>
                                    {user.name}
                                </option>
                            ))}
                        </SelectInput>
                        {item.status !== 'done' && (
                            <button type="button" className="rounded bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700" onClick={() => router.post(`/hr/preboarding/items/${item.id}/complete`)}>Selesai</button>
                        )}
                        <button type="button" className="rounded bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700" onClick={() => onDeleteItem(item.id)}>Hapus</button>
                    </div>
                </div>
            ))}
        </div>
    );
}

function ProbationTab({
    probation,
    evalForm,
    outcomeForm,
    canSubmitOutcome,
    onSubmitEvaluation,
    onSubmitOutcome,
}: {
    probation?: ProbationRecord | null;
    evalForm: ReturnType<typeof useForm<EvaluationFormData>>;
    outcomeForm: ReturnType<typeof useForm<{ outcome: string; extended_until: string }>>;
    canSubmitOutcome: boolean;
    onSubmitEvaluation: (event: FormEvent) => void;
    onSubmitOutcome: (event: FormEvent) => void;
}): JSX.Element {
    if (!probation) {
        return <div className="rounded-lg border border-slate-200 p-4 text-sm text-slate-500">Data probation belum tersedia.</div>;
    }

    const day30Eval = probation.evaluations?.find((evaluation) => evaluation.milestone === 'day30');
    const day60Eval = probation.evaluations?.find((evaluation) => evaluation.milestone === 'day60');
    const day90Eval = probation.evaluations?.find((evaluation) => evaluation.milestone === 'day90');
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
                <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                    Probation diperpanjang sampai {formatWITA(probation.extended_until)}.
                </div>
            )}

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <MilestoneCard title="30 hari" milestone="day30" dueDate={probation.day30_due} evaluation={day30Eval} isCurrent={activeMilestone === 'day30'} evalForm={evalForm} onSubmitEvaluation={onSubmitEvaluation} />
                <MilestoneCard title="60 hari" milestone="day60" dueDate={probation.day60_due} evaluation={day60Eval} isCurrent={activeMilestone === 'day60'} evalForm={evalForm} onSubmitEvaluation={onSubmitEvaluation} />
                <MilestoneCard title="90 hari" milestone="day90" dueDate={probation.day90_due} evaluation={day90Eval} isCurrent={activeMilestone === 'day90'} evalForm={evalForm} onSubmitEvaluation={onSubmitEvaluation} />
            </div>

            {canSubmitOutcome && (
                <form onSubmit={onSubmitOutcome} className="space-y-3 rounded-lg border border-slate-200 p-4">
                    <p className="font-semibold text-slate-900">Input Outcome Probation</p>
                    <select className="rounded border border-slate-300 p-2 text-sm" value={outcomeForm.data.outcome} onChange={(event) => outcomeForm.setData('outcome', event.target.value)}>
                        <option value="permanent">Permanent</option>
                        <option value="extended">Extended</option>
                        <option value="terminated">Terminated</option>
                    </select>
                    {outcomeForm.data.outcome === 'extended' && (
                        <input type="date" className="rounded border border-slate-300 p-2 text-sm" value={outcomeForm.data.extended_until} onChange={(event) => outcomeForm.setData('extended_until', event.target.value)} />
                    )}
                    <button type="submit" className="rounded bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700" disabled={outcomeForm.processing}>Submit Outcome</button>
                </form>
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
    const badge = milestoneBadge(evaluation, isCurrent);

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
                    <div>
                        <p className="font-medium text-slate-700">Rekomendasi</p>
                        <p className="text-slate-600">{evaluation.recommendation}</p>
                    </div>
                </div>
            )}

            {!evaluation && isCurrent && (
                <form onSubmit={onSubmitEvaluation} className="space-y-3">
                    <p className="font-semibold text-slate-900">Form Evaluasi</p>
                    <textarea className="w-full rounded border border-slate-300 p-2 text-sm" placeholder="Catatan performance" value={evalForm.data.performance_notes} onChange={(event) => evalForm.setData('performance_notes', event.target.value)} />
                    <select className="rounded border border-slate-300 p-2 text-sm" value={evalForm.data.recommendation} onChange={(event) => evalForm.setData('recommendation', event.target.value)}>
                        <option value="permanent">Permanent</option>
                        <option value="extended">Extended</option>
                        <option value="terminated">Terminated</option>
                    </select>
                    <button type="submit" className="rounded bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700" disabled={evalForm.processing || evalForm.data.milestone !== milestone}>Simpan Evaluasi</button>
                </form>
            )}
        </div>
    );
}

function milestoneBadge(evaluation: ProbationEvaluation | undefined, isCurrent: boolean): { label: string; className: string } {
    if (evaluation) {
        return { label: 'Selesai', className: 'bg-green-100 text-green-700' };
    }

    if (isCurrent) {
        return { label: 'Perlu Evaluasi', className: 'bg-yellow-100 text-yellow-700' };
    }

    return { label: 'Belum', className: 'bg-slate-100 text-slate-500' };
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
    return status.replaceAll('_', ' ');
}

function currentMilestone(status: string): ProbationMilestone | null {
    const milestones: Record<string, ProbationMilestone> = {
        day30_review: 'day30',
        day60_review: 'day60',
        day90_review: 'day90',
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
