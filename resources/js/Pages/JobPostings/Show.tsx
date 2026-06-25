import ConfirmDialog from '@/Components/ConfirmDialog';
import { Badge, Button, Card, PageHeader } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApplicationItem, humanize, JobPosting, jobStatusTone } from '@/lib/recruitment';
import { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

type PendingAction = 'close' | 'cancel' | null;

export default function JobPostingShow({ jobPosting, applications }: { jobPosting: JobPosting; applications: ApplicationItem[] }): JSX.Element {
    const [pendingAction, setPendingAction] = useState<PendingAction>(null);
    const { errors } = usePage<PageProps>().props;
    const summary = applications.reduce<Record<string, number>>((carry, app) => ({ ...carry, [app.status ?? 'unknown']: (carry[app.status ?? 'unknown'] ?? 0) + 1 }), {});
    const isDraft = jobPosting.status === 'draft';
    const isOpen = jobPosting.status === 'open';
    const isCancelled = jobPosting.status === 'cancelled';

    function confirmAction(): void {
        if (!pendingAction) {
            return;
        }

        router.post(`/job-postings/${jobPosting.id}/${pendingAction}`, {}, { onFinish: () => setPendingAction(null) });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Detail Lowongan</h1>}>
            <Head title="Detail Lowongan" />
            <PageHeader
                title={jobPosting.position_name ?? 'Lowongan'}
                description={`${jobPosting.department?.name ?? '-'} • ${jobPosting.entity?.name ?? '-'}`}
                actions={
                    <div className="flex gap-2">
                        {isDraft && (
                            <Link href={`/job-postings/${jobPosting.id}/edit`}>
                                <Button variant="secondary">Edit</Button>
                            </Link>
                        )}
                        {isDraft && <Button onClick={() => router.post(`/job-postings/${jobPosting.id}/open`)}>Buka</Button>}
                        {isOpen && <Button variant="secondary" onClick={() => setPendingAction('close')}>Tutup</Button>}
                        {!isCancelled && <Button variant="danger" onClick={() => setPendingAction('cancel')}>Cancel</Button>}
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
                    <Badge tone={jobStatusTone(jobPosting.status)}>{humanize(jobPosting.status)}</Badge>
                    <h2 className="mt-4 font-semibold">Deskripsi</h2>
                    <p className="whitespace-pre-line text-sm text-slate-700">{jobPosting.job_description}</p>
                    <h2 className="mt-4 font-semibold">Persyaratan</h2>
                    <p className="whitespace-pre-line text-sm text-slate-700">{jobPosting.requirements}</p>
                </Card>
                <Card className="p-6">
                    <h2 className="mb-4 font-semibold">Summary Kandidat per Stage</h2>
                    <div className="space-y-2">
                        {Object.entries(summary).map(([stage, count]) => <div key={stage} className="flex justify-between rounded-md bg-slate-50 p-3 text-sm"><span>{humanize(stage)}</span><strong>{count}</strong></div>)}
                    </div>
                </Card>
            </div>
            <ConfirmDialog
                open={pendingAction !== null}
                title={pendingAction === 'cancel' ? 'Cancel job posting?' : 'Tutup job posting?'}
                message={pendingAction === 'cancel' ? 'Lowongan akan dibatalkan dan tidak diproses lagi.' : 'Lowongan akan ditutup dari proses rekrutmen aktif.'}
                confirmLabel={pendingAction === 'cancel' ? 'Ya, Cancel' : 'Ya, Tutup'}
                variant={pendingAction === 'cancel' ? 'danger' : 'warning'}
                onConfirm={confirmAction}
                onCancel={() => setPendingAction(null)}
            />
        </AuthenticatedLayout>
    );
}
