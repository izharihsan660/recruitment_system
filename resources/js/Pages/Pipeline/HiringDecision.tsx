import ConfirmDialog from '@/Components/ConfirmDialog';
import { Button, Card, FieldError, FormLabel, GlobalErrorAlert, PageHeader, TextArea } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApplicationItem } from '@/lib/recruitment';
import { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type HiringDecisionApplication = ApplicationItem & {
    hiring_decision?: { id?: number } | null;
    hiringDecision?: { id?: number } | null;
    mcu_simper_record?: { mcu_status?: string | null; simper_status?: string | null } | null;
};

export default function HiringDecision({ application }: { application: HiringDecisionApplication }): JSX.Element {
    const { errors } = usePage<PageProps>().props;
    const [confirmOpen, setConfirmOpen] = useState(false);
    const form = useForm({ decision: 'approved', reason: '', notes: '' });
    const hasDecision = Boolean(application.hiring_decision?.id ?? application.hiringDecision?.id);

    function submit(event: FormEvent): void {
        event.preventDefault();
        setConfirmOpen(true);
    }

    function confirmSubmit(): void {
        setConfirmOpen(false);
        form.post(`/hr/hiring-decision/${application.id}`);
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Hiring Decision</h1>}>
            <Head title="Hiring Decision" />
            <PageHeader title="Hiring Decision" description={`${application.candidate?.name ?? 'Kandidat'} - ${application.job_posting?.position_name ?? ''}`} actions={<Link className="text-sm text-blue-600" href="/pipeline">Kembali ke pipeline</Link>} />
            <GlobalErrorAlert errors={errors} />
            <Card className="max-w-2xl space-y-4 p-6">
                <div>
                    <p className="font-semibold">{application.candidate?.name ?? 'Kandidat'}</p>
                    <p className="text-sm text-slate-500">{application.job_posting?.position_name ?? '-'}</p>
                    <p className="text-sm">MCU: {application.mcu_simper_record?.mcu_status ?? '-'} / SIMPER: {application.mcu_simper_record?.simper_status ?? '-'}</p>
                </div>
                {hasDecision ? (
                    <p className="rounded bg-slate-100 p-3 text-sm">Keputusan hiring sudah diinput.</p>
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <FormLabel required>Keputusan</FormLabel>
                            <div className="flex flex-wrap gap-3 text-sm">
                                <label className="flex items-center gap-2"><input type="radio" checked={form.data.decision === 'approved'} onChange={() => form.setData('decision', 'approved')} /> Disetujui</label>
                                <label className="flex items-center gap-2"><input type="radio" checked={form.data.decision === 'rejected'} onChange={() => form.setData('decision', 'rejected')} /> Ditolak</label>
                            </div>
                            <FieldError message={form.errors.decision} />
                        </div>
                        {form.data.decision === 'rejected' && (
                            <div>
                                <FormLabel required>Alasan</FormLabel>
                                <TextArea rows={3} value={form.data.reason} onChange={(event) => form.setData('reason', event.target.value)} />
                                <FieldError message={form.errors.reason} />
                            </div>
                        )}
                        <div>
                            <FormLabel>Catatan</FormLabel>
                            <TextArea rows={3} value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
                            <FieldError message={form.errors.notes} />
                        </div>
                        <Button type="submit" disabled={form.processing}>{form.processing ? 'Menyimpan...' : 'Submit Keputusan'}</Button>
                    </form>
                )}
            </Card>
            <ConfirmDialog open={confirmOpen} title="Submit keputusan hiring?" message="Pastikan keputusan sudah benar sebelum dikirim." confirmLabel="Ya, Submit" cancelLabel="Batal" variant="warning" onConfirm={confirmSubmit} onCancel={() => setConfirmOpen(false)} />
        </AuthenticatedLayout>
    );
}
