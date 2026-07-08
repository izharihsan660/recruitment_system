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
import { useEffect, useMemo, useState } from 'react';

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
    const [expandedLog, setExpandedLog] = useState<number | null>(null);
    const [pendingMove, setPendingMove] = useState<PendingMove | null>(null);
    const [invalidDropMessage, setInvalidDropMessage] = useState<string | null>(null);

    useEffect(() => setExpandedLog(null), [selected?.id]);

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
                    <div className="mb-4 flex items-center gap-3">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                            {selected.candidate?.name?.split(' ').slice(0, 2).map((name) => name[0]).join('').toUpperCase()}
                        </div>
                        <div>
                            <p className="font-semibold text-slate-900">{selected.candidate?.name}</p>
                            <p className="text-xs text-slate-500">{selected.candidate?.email}</p>
                        </div>
                    </div>
                    <div className="mb-4 grid grid-cols-2 gap-2">
                        {selected.candidate?.phone && (
                            <div className="rounded-lg bg-slate-50 p-2.5">
                                <p className="mb-0.5 text-xs text-slate-400">No HP</p>
                                <p className="text-xs font-medium text-slate-700">{selected.candidate.phone}</p>
                            </div>
                        )}

                        {selected.candidate?.gender && (
                            <div className="rounded-lg bg-slate-50 p-2.5">
                                <p className="mb-0.5 text-xs text-slate-400">Jenis Kelamin</p>
                                <p className="text-xs font-medium text-slate-700">
                                    {selected.candidate.gender === 'male' ? 'Laki-laki' : 'Perempuan'}
                                </p>
                            </div>
                        )}

                        {selected.candidate?.education && selected.candidate.education.length > 0 && (
                            <div className="col-span-2 rounded-lg bg-slate-50 p-2.5">
                                <p className="mb-0.5 text-xs text-slate-400">Pendidikan</p>
                                <p className="text-xs font-medium text-slate-700">
                                    {selected.candidate.education[0].level ?? selected.candidate.education[0].degree} – {selected.candidate.education[0].major} · {selected.candidate.education[0].institution}
                                </p>
                            </div>
                        )}

                        {selected.candidate?.experience && selected.candidate.experience.length > 0 && (
                            <div className="col-span-2 rounded-lg bg-slate-50 p-2.5">
                                <p className="mb-0.5 text-xs text-slate-400">Pengalaman</p>
                                <p className="text-xs font-medium text-slate-700">
                                    {selected.candidate.experience[0].position} · {selected.candidate.experience[0].company} · {selected.candidate.experience[0].years ?? '-'} thn
                                </p>
                            </div>
                        )}
                    </div>
                    <a className="mb-4 flex items-center gap-1 text-xs text-blue-600" href={selected.candidate?.cv_url ?? '#'}>
                        ↓ Download CV
                    </a>
                    <div className="mt-6 space-y-3">
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Aksi Stage Saat Ini</p>
                        <PipelineStageActions application={selected} />
                        {canMoveStage(selected) && <Button onClick={() => fallbackMove(selected)}>Pindah Stage</Button>}
                        {canRejectOrWithdraw(selected) && <RejectBox id={selected.id} />}
                    </div>
                    <p className="mb-2 mt-4 border-t border-slate-100 pt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Timeline Stage</p>
                    <div className="mt-2 space-y-2">
                        {(selected.pipeline_logs?.length ?? 0) > 0 ? (
                            selected.pipeline_logs?.map((log) => (
                                <div
                                    key={log.id}
                                    className="cursor-pointer overflow-hidden rounded-md border border-slate-200"
                                    onClick={() => setExpandedLog(expandedLog === log.id ? null : log.id)}
                                >
                                    <div className="flex items-center justify-between bg-slate-50 p-3">
                                        <div>
                                            <p className="text-sm font-medium">
                                                {humanize(log.from_stage)} → {humanize(log.to_stage)}
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                {log.created_at ? new Date(log.created_at).toLocaleDateString('id-ID') : ''}
                                            </p>
                                        </div>
                                        <span className="text-xs text-slate-400">
                                            {expandedLog === log.id ? '▼' : '▶'}
                                        </span>
                                    </div>
                                    {expandedLog === log.id && (
                                        <StageDetail stage={log.to_stage ?? ''} application={selected} />
                                    )}
                                </div>
                            ))
                        ) : (
                            <p className="text-sm text-slate-500">Belum ada perpindahan stage.</p>
                        )}
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}

function StageDetail({
    stage,
    application,
}: {
    stage: string;
    application: ApplicationItem;
}): JSX.Element {
    const formatRupiah = (value?: number | null): string =>
        value
            ? new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
            }).format(value)
            : '-';

    const yaTidak = (value?: boolean | null): string => (value ? 'Ya' : 'Tidak');

    const Row = ({ label, value }: { label: string; value: string }): JSX.Element => (
        <div>
            <p className="text-xs text-slate-500">{label}</p>
            <p className="text-sm font-medium text-slate-700">{value}</p>
        </div>
    );

    const content = (): JSX.Element => {
        switch (stage) {
            case 'screening': {
                const screening = application.screening;

                if (!screening) {
                    return <p className="text-xs text-slate-400">Belum ada data.</p>;
                }

                return (
                    <div className="grid grid-cols-2 gap-2">
                        <Row label="Pendidikan" value={yaTidak(screening.education_match)} />
                        <Row label="Pengalaman" value={yaTidak(screening.experience_match)} />
                        <Row label="Dokumen" value={yaTidak(screening.document_complete)} />
                        <Row label="Keputusan" value={screening.decision ?? '-'} />
                        {screening.notes && <div className="col-span-2"><Row label="Catatan" value={screening.notes} /></div>}
                    </div>
                );
            }
            case 'test_psikotes': {
                const test = application.psycho_test;

                if (!test) {
                    return <p className="text-xs text-slate-400">Belum ada data.</p>;
                }

                return (
                    <div className="grid grid-cols-2 gap-2">
                        <Row label="Jenis Test" value={test.test_type ?? '-'} />
                        <Row label="Keputusan" value={test.decision ?? '-'} />
                        {test.notes && <div className="col-span-2"><Row label="Catatan" value={test.notes} /></div>}
                    </div>
                );
            }
            case 'interview_hr': {
                const interview = application.hr_interview;

                if (!interview) {
                    return <p className="text-xs text-slate-400">Belum ada data.</p>;
                }

                return (
                    <div className="grid grid-cols-2 gap-2">
                        <Row label="Komunikasi" value={interview.score_communication ? `${interview.score_communication}/5` : '-'} />
                        <Row label="Kepribadian" value={interview.score_personality ? `${interview.score_personality}/5` : '-'} />
                        <Row label="Motivasi" value={interview.score_motivation ? `${interview.score_motivation}/5` : '-'} />
                        <Row label="Attitude" value={interview.score_attitude ? `${interview.score_attitude}/5` : '-'} />
                        <Row label="Culture Fit" value={interview.score_culture_fit ? `${interview.score_culture_fit}/5` : '-'} />
                        <Row label="Rekomendasi" value={interview.recommendation ?? '-'} />
                        {interview.salary_expectation && (
                            <div className="col-span-2">
                                <Row label="Ekspektasi Gaji" value={formatRupiah(interview.salary_expectation)} />
                            </div>
                        )}
                        {interview.strengths && <div className="col-span-2"><Row label="Kekuatan" value={interview.strengths} /></div>}
                        {interview.weaknesses && <div className="col-span-2"><Row label="Kelemahan" value={interview.weaknesses} /></div>}
                    </div>
                );
            }
            case 'interview_user': {
                const interview = application.user_interview;

                if (!interview) {
                    return <p className="text-xs text-slate-400">Belum ada data.</p>;
                }

                return (
                    <div className="grid grid-cols-2 gap-2">
                        <Row label="Kemampuan Teknis" value={interview.score_technical ? `${interview.score_technical}/5` : '-'} />
                        <Row label="Pengalaman Kerja" value={interview.score_experience ? `${interview.score_experience}/5` : '-'} />
                        <Row label="Problem Solving" value={interview.score_problem_solving ? `${interview.score_problem_solving}/5` : '-'} />
                        <Row label="Kesesuaian Tim" value={interview.score_team_fit ? `${interview.score_team_fit}/5` : '-'} />
                        <Row label="Rekomendasi" value={interview.recommendation ?? '-'} />
                    </div>
                );
            }
            case 'background_check': {
                const backgroundCheck = application.background_check;

                if (!backgroundCheck) {
                    return <p className="text-xs text-slate-400">Belum ada data.</p>;
                }

                return (
                    <div className="grid grid-cols-2 gap-2">
                        <Row label="KTP" value={yaTidak(backgroundCheck.ktp_verified)} />
                        <Row label="Ijazah" value={yaTidak(backgroundCheck.ijazah_verified)} />
                        <Row label="Sertifikat" value={yaTidak(backgroundCheck.certificate_verified)} />
                        <Row label="Referensi" value={yaTidak(backgroundCheck.reference_verified)} />
                        <Row label="Keputusan" value={backgroundCheck.decision ?? '-'} />
                    </div>
                );
            }
            case 'offering': {
                const offering = application.offering_letter;

                if (!offering) {
                    return <p className="text-xs text-slate-400">Belum ada data.</p>;
                }

                return (
                    <div className="grid grid-cols-2 gap-2">
                        <Row label="Status" value={offering.status ?? '-'} />
                        <Row label="Gaji Gross" value={formatRupiah(offering.salary_gross)} />
                        <Row label="Gaji Nett" value={formatRupiah(offering.salary_nett)} />
                    </div>
                );
            }
            default:
                return <p className="text-xs text-slate-400">Tidak ada detail untuk stage ini.</p>;
        }
    };

    return (
        <div className="border-t border-slate-200 bg-white p-3">
            {content()}
        </div>
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
            <div className="mt-2 grid grid-cols-2 gap-2">
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
