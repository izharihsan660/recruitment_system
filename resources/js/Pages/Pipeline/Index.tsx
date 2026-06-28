import ConfirmDialog from '@/Components/ConfirmDialog';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    Badge,
    Button,
    Card,
    EmptyState,
    PageHeader,
    SelectInput,
    TextArea,
} from '@/Components/shared/ui';
import {
    ApplicationItem,
    CandidateSource,
    Department,
    humanize,
    JobPosting,
} from '@/lib/recruitment';
import {
    DndContext,
    DragEndEvent,
    DragStartEvent,
    PointerSensor,
    useDraggable,
    useDroppable,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const stages = [
    'applied',
    'screening',
    'test_psikotes',
    'interview_hr',
    'interview_user',
    'background_check',
    'offering',
    'mcu_simper',
    'hiring_decision',
    'pkwt',
    'hired',
] as const;

type Stage = (typeof stages)[number];
const inactiveCandidateStatuses = ['rejected', 'withdrawn'];
const finalMoveStatuses = ['hired', ...inactiveCandidateStatuses];

interface PendingMove {
    application: ApplicationItem;
    fromStage: Stage;
    toStage: Stage;
}

export default function PipelineIndex({
    applications,
    jobPostings,
    departments,
    sources,
}: {
    applications: ApplicationItem[];
    jobPostings: JobPosting[];
    departments: Department[];
    sources: CandidateSource[];
}): JSX.Element {
    const [selected, setSelected] = useState<ApplicationItem | null>(null);
    const [activeApplicationId, setActiveApplicationId] = useState<number | null>(null);
    const [pendingMove, setPendingMove] = useState<PendingMove | null>(null);
    const [invalidDropMessage, setInvalidDropMessage] = useState<string | null>(null);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8,
            },
        }),
    );

    const grouped = useMemo(
        () =>
            stages.map((stage) => ({
                stage,
                items: applications.filter((item) => item.status === stage),
            })),
        [applications],
    );

    const applicationsById = useMemo(
        () => new Map(applications.map((application) => [String(application.id), application])),
        [applications],
    );

    function handleDragStart(event: DragStartEvent): void {
        setInvalidDropMessage(null);
        setActiveApplicationId(Number(event.active.id));
    }

    function handleDragEnd(event: DragEndEvent): void {
        setActiveApplicationId(null);

        const application = applicationsById.get(String(event.active.id));
        const targetStage = event.over?.id ? String(event.over.id) : null;

        if (!application || !isStage(application.status) || !isStage(targetStage) || !canMoveStage(application)) {
            return;
        }

        if (application.status === targetStage) {
            return;
        }

        if (!isNextStage(application.status, targetStage)) {
            setInvalidDropMessage('Kandidat hanya bisa dipindahkan ke stage berikutnya.');
            return;
        }

        setPendingMove({
            application,
            fromStage: application.status,
            toStage: targetStage,
        });
    }

    function confirmMove(): void {
        if (!pendingMove) {
            return;
        }

        router.post(`/hr/pipeline/${pendingMove.application.id}/move`, {}, {
            onFinish: () => setPendingMove(null),
        });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Pipeline Kandidat</h1>}>
            <Head title="Pipeline" />

            <PageHeader
                title="Pipeline Kandidat"
                description="Drag kandidat ke stage berikutnya, atau gunakan tombol Pindah Stage sebagai fallback."
            />

            <Card className="mb-4 p-4">
                <div className="grid gap-3 md:grid-cols-3">
                    <SelectInput>
                        <option>Lowongan</option>
                        {jobPostings.map((job) => (
                            <option key={job.id}>{job.position_name}</option>
                        ))}
                    </SelectInput>
                    <SelectInput>
                        <option>Departemen</option>
                        {departments.map((department) => (
                            <option key={department.id}>{department.name}</option>
                        ))}
                    </SelectInput>
                    <SelectInput>
                        <option>Source</option>
                        {sources.map((source) => (
                            <option key={source.id}>{source.name}</option>
                        ))}
                    </SelectInput>
                </div>
            </Card>

            {invalidDropMessage && (
                <div className="mb-4 rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-medium text-orange-700">
                    {invalidDropMessage}
                </div>
            )}

            <DndContext
                sensors={sensors}
                onDragStart={handleDragStart}
                onDragEnd={handleDragEnd}
                onDragCancel={() => setActiveApplicationId(null)}
            >
                <div className="flex gap-4 overflow-x-auto pb-4">
                    {grouped.map((column) => (
                        <StageColumn
                            key={column.stage}
                            stage={column.stage}
                            items={column.items}
                            activeApplicationId={activeApplicationId}
                            onSelect={setSelected}
                        />
                    ))}
                </div>
            </DndContext>

            {pendingMove && (
                <ConfirmMoveDialog
                    move={pendingMove}
                    onCancel={() => setPendingMove(null)}
                    onConfirm={confirmMove}
                />
            )}

            {selected && (
                <div className="fixed inset-y-0 right-0 z-50 w-full max-w-md overflow-y-auto border-l bg-white p-6 shadow-xl">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Detail Kandidat</h2>
                        <Button variant="ghost" onClick={() => setSelected(null)}>
                            Tutup
                        </Button>
                    </div>
                    <p className="text-xl font-bold">{selected.candidate?.name}</p>
                    <p className="text-sm text-slate-500">{selected.candidate?.email}</p>
                    <a className="mt-3 block text-sm text-blue-600" href={selected.candidate?.cv_path ?? '#'}>
                        Download CV
                    </a>
                    <div className="mt-6 space-y-3">
                        <PipelineStageActions application={selected} />
                        <PipelineSummary application={selected} />
                        {canMoveStage(selected) && <Button onClick={() => fallbackMove(selected)}>Pindah Stage</Button>}
                        {canRejectOrWithdraw(selected) && <RejectBox id={selected.id} />}
                    </div>
                    <h3 className="mt-6 font-semibold">Timeline Stage</h3>
                    <div className="mt-2 space-y-2">
                        {selected.pipeline_logs?.map((log) => (
                            <div key={log.id} className="rounded-md bg-slate-50 p-3 text-sm">
                                {humanize(log.from_stage)} → {humanize(log.to_stage)}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}

function StageColumn({
    stage,
    items,
    activeApplicationId,
    onSelect,
}: {
    stage: Stage;
    items: ApplicationItem[];
    activeApplicationId: number | null;
    onSelect: (application: ApplicationItem) => void;
}): JSX.Element {
    const { isOver, setNodeRef } = useDroppable({
        id: stage,
    });

    return (
        <div
            ref={setNodeRef}
            className={`w-80 shrink-0 rounded-lg border p-3 transition ${
                isOver ? 'border-blue-400 bg-blue-50' : 'border-slate-200 bg-slate-50'
            }`}
        >
            <div className="mb-3 flex items-center justify-between">
                <h2 className="font-semibold">{humanize(stage)}</h2>
                <Badge>{items.length}</Badge>
            </div>
            <div className="space-y-3">
                {items.map((application) => (
                    <CandidateCard
                        key={application.id}
                        application={application}
                        isDragging={activeApplicationId === application.id}
                        onSelect={onSelect}
                    />
                ))}
                {items.length === 0 && <EmptyState title="Kosong" />}
            </div>
        </div>
    );
}

function CandidateCard({
    application,
    isDragging,
    onSelect,
}: {
    application: ApplicationItem;
    isDragging: boolean;
    onSelect: (application: ApplicationItem) => void;
}): JSX.Element {
    const { attributes, listeners, setNodeRef, transform } = useDraggable({
        id: application.id,
    });

    return (
        <Card
            ref={setNodeRef}
            className={`cursor-grab p-4 transition hover:border-blue-300 active:cursor-grabbing ${
                isDragging ? 'opacity-50' : 'opacity-100'
            }`}
            style={{
                transform: CSS.Translate.toString(transform),
            }}
            onClick={() => onSelect(application)}
            {...listeners}
            {...attributes}
        >
            <div className="flex items-start gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-700">
                    {application.candidate?.name?.slice(0, 1)}
                </div>
                <div>
                    <p className="font-semibold">{application.candidate?.name}</p>
                    <p className="text-sm text-slate-500">
                        {application.job_posting?.position_name ?? application.jobPosting?.position_name}
                    </p>
                    <Badge tone="blue">{application.source ?? 'Source'}</Badge>
                </div>
            </div>
            {canMoveStage(application) && (
                <Button
                    className="mt-3 w-full"
                    variant="secondary"
                    onClick={(event) => {
                        event.stopPropagation();
                        fallbackMove(application);
                    }}
                >
                    Pindah Stage
                </Button>
            )}
        </Card>
    );
}

function ConfirmMoveDialog({
    move,
    onCancel,
    onConfirm,
}: {
    move: PendingMove;
    onCancel: () => void;
    onConfirm: () => void;
}): JSX.Element {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <Card className="w-full max-w-md p-6">
                <h2 className="text-lg font-semibold text-slate-900">Konfirmasi Pindah Stage</h2>
                <p className="mt-2 text-sm text-slate-600">
                    Pindahkan {move.application.candidate?.name ?? 'kandidat'} dari{' '}
                    <strong>{humanize(move.fromStage)}</strong> ke <strong>{humanize(move.toStage)}</strong>?
                </p>
                <div className="mt-6 flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={onCancel}>
                        Batal
                    </Button>
                    <Button type="button" onClick={onConfirm}>
                        Ya, Pindahkan
                    </Button>
                </div>
            </Card>
        </div>
    );
}

function RejectBox({ id }: { id: number }): JSX.Element {
    const [reason, setReason] = useState('');
    const [pendingAction, setPendingAction] = useState<'reject' | 'withdraw' | null>(null);

    function confirmAction(): void {
        if (pendingAction === 'reject') {
            router.post(`/hr/pipeline/${id}/reject`, { reason }, { onFinish: () => setPendingAction(null) });
        }

        if (pendingAction === 'withdraw') {
            router.post(`/hr/pipeline/${id}/withdraw`, {}, { onFinish: () => setPendingAction(null) });
        }
    }

    return (
        <div className="space-y-2">
            <TextArea
                rows={2}
                placeholder="Alasan reject"
                value={reason}
                onChange={(event) => setReason(event.target.value)}
            />
            <div className="flex gap-2">
                <Button variant="danger" onClick={() => setPendingAction('reject')}>
                    Reject
                </Button>
                <Button variant="secondary" onClick={() => setPendingAction('withdraw')}>
                    Withdraw
                </Button>
            </div>
            <ConfirmDialog
                open={pendingAction !== null}
                title={pendingAction === 'withdraw' ? 'Withdraw lamaran?' : 'Reject kandidat?'}
                message={pendingAction === 'withdraw' ? 'Lamaran kandidat akan ditandai withdraw.' : 'Kandidat akan ditolak dari tahap pipeline saat ini.'}
                confirmLabel={pendingAction === 'withdraw' ? 'Ya, Withdraw' : 'Ya, Reject'}
                onConfirm={confirmAction}
                onCancel={() => setPendingAction(null)}
            />
        </div>
    );
}

function PipelineStageActions({ application }: { application: ApplicationItem }): JSX.Element | null {
    console.log('application id:', application?.id, 'full:', application);

    if (inactiveCandidateStatuses.includes(application.status ?? '')) {
        return null;
    }

    const actions: Record<string, { href: string; labels: string[] }> = {
        screening: { href: `/hr/screening/${application.id}`, labels: ['Isi Screening'] },
        test_psikotes: { href: `/hr/psycho-test/${application.id}`, labels: application.psycho_test?.id ? ['Input Hasil Test'] : ['Input Jadwal Test'] },
        interview_hr: { href: `/hr/interview-hr/${application.id}`, labels: application.hr_interview?.id ? ['Isi Scorecard'] : ['Jadwalkan Interview HR'] },
        interview_user: { href: `/hr/interview-user/${application.id}`, labels: application.user_interview?.id ? ['Isi Scorecard'] : ['Jadwalkan Interview User'] },
        background_check: { href: `/hr/background-check/${application.id}`, labels: ['Isi Background Check'] },
        offering: { href: `/hr/offering/${application.id}`, labels: [application.offering_letter?.id ? 'Lihat Offering' : 'Buat Offering'] },
        mcu_simper: { href: `/hr/mcu-simper/${application.id}`, labels: ['Input MCU/SIMPER'] },
        hiring_decision: { href: `/hr/hiring-decision/${application.id}`, labels: ['Input Keputusan'] },
        pkwt: { href: `/hr/pkwt/${application.id}`, labels: [application.pkwt_contract?.id ? 'Lihat PKWT' : 'Buat PKWT'] },
        hired: { href: `/hr/employees/${application.id}/activate`, labels: ['Aktifkan sebagai Karyawan'] },
    };

    const action = application.status ? actions[application.status] : null;

    if (!action) {
        return null;
    }

    return (
        <div className="rounded-md border border-blue-100 bg-blue-50 p-3">
            <p className="mb-2 text-sm font-semibold text-blue-900">Aksi Stage Saat Ini</p>
            <div className="flex flex-wrap gap-2">
                {action.labels.map((label) => (
                    <a key={label} href={action.href} className="inline-flex rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        {label}
                    </a>
                ))}
            </div>
        </div>
    );
}

function PipelineSummary({ application }: { application: ApplicationItem }): JSX.Element | null {
    const summaries = [
        application.screening?.decision ? `Screening: ${decisionLabel(application.screening.decision)}` : null,
        application.psycho_test?.decision ? `Psikotes: ${decisionLabel(application.psycho_test.decision)}` : null,
        application.hr_interview?.recommendation ? `Interview HR: ${decisionLabel(application.hr_interview.recommendation)}` : null,
        application.user_interview?.recommendation ? `Interview User: ${decisionLabel(application.user_interview.recommendation)}` : null,
        application.background_check?.decision ? `Background Check: ${decisionLabel(application.background_check.decision)}` : null,
        application.offering_letter?.status ? `Offering: ${decisionLabel(application.offering_letter.status)}${application.offering_letter.signed_at ? ` - Signed ${application.offering_letter.signed_at}` : ''}` : null,
        application.pkwt_contract?.status ? `PKWT: ${decisionLabel(application.pkwt_contract.status)}${application.pkwt_contract.signed_at ? ` - Signed ${application.pkwt_contract.signed_at}` : ''}` : null,
    ].filter(Boolean);

    if (summaries.length === 0) {
        return null;
    }

    return (
        <div className="space-y-2 rounded-md border border-slate-200 p-3 text-sm text-slate-700">
            {summaries.map((summary) => <p key={summary}>{summary}</p>)}
        </div>
    );
}

function decisionLabel(value: string): string {
    const labels: Record<string, string> = {
        passed: 'Lolos',
        failed: 'Tidak Lolos',
        pending_info: 'Pending Info',
        recommended: 'Direkomendasikan',
        considered: 'Dipertimbangkan',
        not_recommended: 'Tidak Direkomendasikan',
        accepted: 'Diterima',
        rejected: 'Ditolak',
        clear: 'Clear',
        issue: 'Issue',
        draft: 'Draft',
        sent: 'Terkirim',
        viewed: 'Dilihat',
        negotiation: 'Negosiasi',
        expired: 'Expired',
        signed: 'Signed',
        partially_signed: 'Sebagian Signed',
    };

    return labels[value] ?? humanize(value);
}

function fallbackMove(application: ApplicationItem): void {
    if (!canMoveStage(application)) {
        return;
    }

    if (confirm('Pindahkan kandidat ke stage berikutnya?')) {
        router.post(`/hr/pipeline/${application.id}/move`, {}, {});
    }
}

function canRejectOrWithdraw(application: ApplicationItem): boolean {
    return !inactiveCandidateStatuses.includes(application.status ?? '');
}

function canMoveStage(application: ApplicationItem): boolean {
    return !finalMoveStatuses.includes(application.status ?? '');
}

function isStage(value: unknown): value is Stage {
    return typeof value === 'string' && stages.includes(value as Stage);
}

function isNextStage(currentStage: Stage, targetStage: Stage): boolean {
    return stages.indexOf(targetStage) === stages.indexOf(currentStage) + 1;
}
