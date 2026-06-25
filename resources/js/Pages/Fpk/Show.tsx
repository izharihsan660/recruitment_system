import ConfirmDialog from '@/Components/ConfirmDialog';
import { Badge, Button, Card, PageHeader, TextArea } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { fpkStatusTone, formatDate, humanize, RecruitmentRequest } from '@/lib/recruitment';
import { PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type PendingAction = 'reject' | 'close' | null;

export default function FpkShow({ fpk }: { fpk: RecruitmentRequest }): JSX.Element {
    const actionForm = useForm({ comment: '' });
    const { errors } = usePage<PageProps>().props;
    const [pendingAction, setPendingAction] = useState<PendingAction>(null);
    const isDraft = ['draft', 'need_revision'].includes(fpk.status ?? '');
    const isInApproval = fpk.status === 'in_approval';
    const isApproved = fpk.status === 'approved';

    function action(event: FormEvent, endpoint: string): void {
        event.preventDefault();
        router.post(endpoint, actionForm.data, {});
    }

    function confirmDestructive(): void {
        if (!pendingAction) {
            return;
        }

        router.post(`/fpk/${fpk.id}/${pendingAction}`, actionForm.data, {
            onFinish: () => setPendingAction(null),
        });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Detail FPK</h1>}>
            <Head title="Detail FPK" />
            <PageHeader
                title={`FPK-${String(fpk.id).padStart(5, '0')}`}
                description={fpk.position_name}
                actions={
                    <div className="flex gap-2">
                        {isDraft && (
                            <Link href={`/fpk/${fpk.id}/edit`}>
                                <Button variant="secondary">Edit</Button>
                            </Link>
                        )}
                        {isDraft && <Button onClick={() => router.post(`/fpk/${fpk.id}/submit`)}>Submit</Button>}
                        {isApproved && <Button variant="danger" onClick={() => setPendingAction('close')}>Tutup FPK</Button>}
                    </div>
                }
            />
            {Object.keys(errors).length > 0 && (
                <div className="mb-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {Object.values(errors).map((error, index) => <p key={index}>{error}</p>)}
                </div>
            )}
            <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                <Card className="p-6">
                    <div className="mb-4 flex items-center gap-2">
                        <Badge tone={fpkStatusTone(fpk.status)}>{humanize(fpk.status)}</Badge>
                        <span className="text-sm text-slate-500">{formatDate(fpk.requested_at)}</span>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Info label="PT" value={fpk.entity?.name} />
                        <Info label="Departemen" value={fpk.department?.name} />
                        <Info label="Posisi" value={fpk.position_name} />
                        <Info label="Headcount" value={String(fpk.headcount ?? '-')} />
                        <Info label="Lokasi Kerja" value={fpk.work_location} />
                        <Info label="Dibutuhkan" value={formatDate(fpk.required_at)} />
                        <Info label="Alasan" value={humanize(fpk.reason_type)} />
                        <Info label="Pendidikan" value={fpk.min_education} />
                        <Info label="Pengalaman" value={fpk.min_experience} />
                        <Info label="Skill" value={fpk.required_skills} />
                    </div>
                    <div className="mt-6">
                        <h2 className="mb-2 font-semibold">Tugas & Tanggung Jawab</h2>
                        <p className="whitespace-pre-line text-sm text-slate-700">{fpk.job_description}</p>
                    </div>
                </Card>
                <Card className="p-6">
                    <h2 className="mb-4 font-semibold">Timeline Approval</h2>
                    <div className="space-y-4">
                        {fpk.approval_records?.map((record) => (
                            <div key={record.id} className="rounded-md border p-3">
                                <div className="flex items-center justify-between">
                                    <span className="font-semibold">Level {record.level}</span>
                                    <Badge tone={record.action ? 'green' : 'yellow'}>{record.action ? humanize(record.action) : 'Menunggu'}</Badge>
                                </div>
                                <p className="mt-1 text-sm text-slate-600">{record.approver?.name ?? 'Approver'}</p>
                                {record.comment && <p className="mt-2 text-sm text-slate-500">{record.comment}</p>}
                            </div>
                        ))}
                    </div>
                    {isInApproval && (
                        <form className="mt-6 space-y-3">
                            <TextArea rows={3} placeholder="Komentar approval/reject/revision" value={actionForm.data.comment} onChange={(event) => actionForm.setData('comment', event.target.value)} />
                            <div className="grid grid-cols-3 gap-2">
                                <Button type="button" onClick={(event) => action(event, `/fpk/${fpk.id}/approve`)}>Approve</Button>
                                <Button type="button" variant="danger" onClick={() => setPendingAction('reject')}>Reject</Button>
                                <Button type="button" variant="secondary" onClick={(event) => action(event, `/fpk/${fpk.id}/need-revision`)}>Need Revision</Button>
                            </div>
                        </form>
                    )}
                </Card>
            </div>
            <ConfirmDialog
                open={pendingAction !== null}
                title={pendingAction === 'close' ? 'Tutup FPK?' : 'Reject FPK?'}
                message={pendingAction === 'close' ? 'FPK yang ditutup tidak dapat diproses lagi.' : 'Pastikan alasan reject sudah diisi sebelum melanjutkan.'}
                confirmLabel={pendingAction === 'close' ? 'Ya, Tutup FPK' : 'Ya, Reject FPK'}
                onConfirm={confirmDestructive}
                onCancel={() => setPendingAction(null)}
            />
        </AuthenticatedLayout>
    );
}

function Info({ label, value }: { label: string; value?: string | null }): JSX.Element {
    return (
        <div>
            <p className="text-xs font-medium uppercase text-slate-500">{label}</p>
            <p className="text-sm font-semibold text-slate-900">{value || '-'}</p>
        </div>
    );
}
