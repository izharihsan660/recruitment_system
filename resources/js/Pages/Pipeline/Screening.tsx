import { Button, Card, FieldError, FormLabel, GlobalErrorAlert, PageHeader, TextArea } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApplicationItem } from '@/lib/recruitment';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

interface ScreeningData {
    education_match: boolean;
    experience_match: boolean;
    document_complete: boolean;
    notes: string;
    decision: 'passed' | 'failed' | 'pending_info';
    rejection_reason: string;
}

export default function Screening({ application, screening }: { application: ApplicationItem; screening?: Partial<ScreeningData> | null }): JSX.Element {
    const { errors } = usePage<PageProps>().props;
    const form = useForm<ScreeningData>({
        education_match: Boolean(screening?.education_match),
        experience_match: Boolean(screening?.experience_match),
        document_complete: Boolean(screening?.document_complete),
        notes: screening?.notes ?? '',
        decision: screening?.decision ?? 'passed',
        rejection_reason: screening?.rejection_reason ?? '',
    });

    function submit(event: React.FormEvent): void {
        event.preventDefault();
        const url = `/hr/screening/${application.id}`;
        screening?.decision === 'pending_info' ? form.put(url) : form.post(url);
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Screening Kandidat</h1>}>
            <Head title="Screening" />
            <PageHeader title="Screening Kandidat" description={`${application.candidate?.name ?? 'Kandidat'} - ${application.job_posting?.position_name ?? ''}`} actions={<Link className="text-sm text-blue-600" href="/pipeline">Kembali ke pipeline</Link>} />
            <GlobalErrorAlert errors={errors} />
            <Card className="p-6">
                <form onSubmit={submit} className="space-y-5">
                    {[
                        ['education_match', 'Kesesuaian Pendidikan'],
                        ['experience_match', 'Kesesuaian Pengalaman'],
                        ['document_complete', 'Kelengkapan Dokumen'],
                    ].map(([key, label]) => (
                        <label key={key} className="flex items-center gap-3 rounded-md border border-slate-200 p-3 text-sm font-medium text-slate-700">
                            <input type="checkbox" checked={Boolean(form.data[key as keyof ScreeningData])} onChange={(event) => form.setData(key as keyof ScreeningData, event.target.checked as never)} />
                            {label}
                        </label>
                    ))}
                    <div>
                        <FormLabel>Notes</FormLabel>
                        <TextArea rows={3} value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
                        <FieldError message={form.errors.notes} />
                    </div>
                    <div>
                        <FormLabel required>Keputusan</FormLabel>
                        <div className="flex flex-wrap gap-3 text-sm">
                            {[['passed', 'Lolos'], ['failed', 'Tidak Lolos'], ['pending_info', 'Pending Info']].map(([value, label]) => (
                                <label key={value} className="flex items-center gap-2"><input type="radio" checked={form.data.decision === value} onChange={() => form.setData('decision', value as ScreeningData['decision'])} />{label}</label>
                            ))}
                        </div>
                        <FieldError message={form.errors.decision} />
                    </div>
                    {form.data.decision === 'failed' && (
                        <div>
                            <FormLabel required>Alasan Penolakan</FormLabel>
                            <TextArea rows={3} value={form.data.rejection_reason} onChange={(event) => form.setData('rejection_reason', event.target.value)} />
                            <FieldError message={form.errors.rejection_reason} />
                        </div>
                    )}
                    <Button type="submit" disabled={form.processing}>{form.processing ? 'Menyimpan...' : 'Simpan'}</Button>
                </form>
            </Card>
        </AuthenticatedLayout>
    );
}
