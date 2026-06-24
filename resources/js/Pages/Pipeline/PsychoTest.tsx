import { Badge, Button, Card, FieldError, FormLabel, PageHeader, TextArea, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApplicationItem } from '@/lib/recruitment';
import { Head, Link, useForm } from '@inertiajs/react';

interface PsychoTestRecord {
    id?: number;
    test_type?: string;
    scheduled_at?: string;
    notes?: string;
    decision?: 'passed' | 'failed' | null;
    rejection_reason?: string | null;
}

export default function PsychoTest({ application, psychoTest, notRequired }: { application: ApplicationItem; psychoTest?: PsychoTestRecord | null; notRequired: boolean }): JSX.Element {
    const scheduleForm = useForm({ test_type: psychoTest?.test_type ?? '', scheduled_at: psychoTest?.scheduled_at ?? '', notes: psychoTest?.notes ?? '' });
    const resultForm = useForm({ decision: psychoTest?.decision ?? 'passed', notes: psychoTest?.notes ?? '', rejection_reason: psychoTest?.rejection_reason ?? '' });

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Test Psikotes</h1>}>
            <Head title="Test Psikotes" />
            <PageHeader title="Test Psikotes" description={`${application.candidate?.name ?? 'Kandidat'} - ${application.job_posting?.position_name ?? ''}`} actions={<Link className="text-sm text-blue-600" href="/pipeline">Kembali</Link>} />
            {notRequired && <div className="mb-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">Test tidak diperlukan untuk posisi ini.</div>}
            <div className="grid gap-4 lg:grid-cols-2">
                <Card className="p-6">
                    <div className="mb-4 flex items-center justify-between"><h2 className="font-semibold">Jadwal Test</h2>{psychoTest?.id && <Badge tone="blue">Tersimpan</Badge>}</div>
                    <form onSubmit={(event) => { event.preventDefault(); scheduleForm.post(`/hr/psycho-test/${application.id}/schedule`); }} className="space-y-4">
                        <div><FormLabel>Jenis Test</FormLabel><TextInput value={scheduleForm.data.test_type} onChange={(event) => scheduleForm.setData('test_type', event.target.value)} /><FieldError message={scheduleForm.errors.test_type} /></div>
                        <div><FormLabel>Tanggal & Waktu</FormLabel><TextInput type="datetime-local" value={scheduleForm.data.scheduled_at} onChange={(event) => scheduleForm.setData('scheduled_at', event.target.value)} /><FieldError message={scheduleForm.errors.scheduled_at} /></div>
                        <div><FormLabel>Catatan</FormLabel><TextArea rows={3} value={scheduleForm.data.notes} onChange={(event) => scheduleForm.setData('notes', event.target.value)} /></div>
                        <Button type="submit" disabled={scheduleForm.processing}>{scheduleForm.processing ? 'Menyimpan...' : 'Simpan Jadwal'}</Button>
                    </form>
                </Card>
                <Card className="p-6">
                    <h2 className="mb-4 font-semibold">Hasil Test</h2>
                    <form onSubmit={(event) => { event.preventDefault(); resultForm.post(`/hr/psycho-test/${application.id}/result`); }} className="space-y-4">
                        <div><FormLabel>Keputusan</FormLabel><div className="flex gap-3 text-sm"><label><input type="radio" checked={resultForm.data.decision === 'passed'} onChange={() => resultForm.setData('decision', 'passed')} /> Lulus</label><label><input type="radio" checked={resultForm.data.decision === 'failed'} onChange={() => resultForm.setData('decision', 'failed')} /> Tidak Lulus</label></div></div>
                        <div><FormLabel>Catatan</FormLabel><TextArea rows={3} value={resultForm.data.notes} onChange={(event) => resultForm.setData('notes', event.target.value)} /></div>
                        {resultForm.data.decision === 'failed' && <div><FormLabel>Alasan Penolakan</FormLabel><TextArea rows={3} value={resultForm.data.rejection_reason} onChange={(event) => resultForm.setData('rejection_reason', event.target.value)} /><FieldError message={resultForm.errors.rejection_reason} /></div>}
                        <Button type="submit" disabled={!psychoTest?.id || resultForm.processing}>{resultForm.processing ? 'Menyimpan...' : 'Simpan Hasil'}</Button>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
