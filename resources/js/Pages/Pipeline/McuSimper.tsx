import { Badge, Button, Card, FieldError, FormLabel, GlobalErrorAlert, PageHeader, SelectInput, TextArea, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApplicationItem } from '@/lib/recruitment';
import { toLocalDatetime } from '@/lib/utils';
import { PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';

type SectionType = 'mcu' | 'simper';
type ScheduleData = Record<`${SectionType}_scheduled_at` | `${SectionType}_location`, string>;
type ResultData = { result_file: File | null; status: 'passed' | 'failed'; notes: string; rejection_reason: string };
type McuSimperRecord = Partial<Record<`${SectionType}_status` | `${SectionType}_scheduled_at` | `${SectionType}_location`, string | null>>;

type Props = {
    application: ApplicationItem;
    record: McuSimperRecord | null;
    canProceed: boolean;
};

export default function McuSimper({ application, record, canProceed }: Props): JSX.Element {
    const { errors } = usePage<PageProps>().props;

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">MCU / SIMPER</h1>}>
            <Head title="MCU / SIMPER" />
            <PageHeader title="MCU / SIMPER" description={`${application.candidate?.name ?? 'Kandidat'} - ${application.job_posting?.position_name ?? ''}`} actions={<Link className="text-sm text-blue-600" href="/pipeline">Kembali ke pipeline</Link>} />
            <GlobalErrorAlert errors={errors} />
            <div className="space-y-4">
                <Card className="p-6">
                    <p className="font-semibold">{application.candidate?.name ?? 'Kandidat'}</p>
                    <p className="text-sm text-slate-500">{application.job_posting?.position_name ?? '-'}</p>
                    {!record && <Button type="button" className="mt-3" onClick={() => router.post(`/hr/mcu-simper/${application.id}`)}>Buat Record</Button>}
                </Card>
                <div className="grid gap-4 lg:grid-cols-2">
                    <Section type="mcu" title="MCU" application={application} record={record} />
                    <Section type="simper" title="SIMPER" application={application} record={record} />
                </div>
                <Button type="button" disabled={!canProceed} onClick={() => router.post(`/hr/mcu-simper/${application.id}/proceed`)}>Lanjut ke Hiring Decision</Button>
            </div>
        </AuthenticatedLayout>
    );
}

function Section({ type, title, application, record }: { type: SectionType; title: string; application: ApplicationItem; record: McuSimperRecord | null }): JSX.Element {
    const scheduledAtField = `${type}_scheduled_at` as const;
    const locationField = `${type}_location` as const;
    const schedule = useForm<ScheduleData>({ [scheduledAtField]: toLocalDatetime(record?.[scheduledAtField]), [locationField]: record?.[locationField] ?? '' } as ScheduleData);
    const result = useForm<ResultData>({ result_file: null, status: 'passed', notes: '', rejection_reason: '' });
    const status = record?.[`${type}_status`];
    const canInputSchedule = status === 'pending';
    const canInputResult = ['pending', 'scheduled'].includes(status ?? '');

    function submitSchedule(event: FormEvent): void {
        event.preventDefault();
        schedule.post(`/hr/mcu-simper/${application.id}/schedule-${type}`);
    }

    function submitResult(event: FormEvent): void {
        event.preventDefault();
        result.post(`/hr/mcu-simper/${application.id}/result-${type}`, { forceFormData: true });
    }

    return (
        <Card className="p-6">
            <div className="mb-3 flex items-center justify-between">
                <h2 className="font-semibold">{title}</h2>
                <Badge tone="blue">{status ?? 'Belum Ada'}</Badge>
            </div>
            {status === 'not_required' ? (
                <p className="rounded bg-slate-100 p-3 text-sm">Tidak Diperlukan</p>
            ) : (
                <div className="space-y-4">
                    {canInputSchedule && (
                        <form onSubmit={submitSchedule} className="space-y-3">
                            <div>
                                <FormLabel required>Tanggal & Waktu</FormLabel>
                                <TextInput type="datetime-local" value={schedule.data[scheduledAtField]} onChange={(event) => schedule.setData(scheduledAtField, event.target.value)} />
                                <FieldError message={schedule.errors[scheduledAtField]} />
                            </div>
                            <div>
                                <FormLabel required>Lokasi</FormLabel>
                                <TextInput value={schedule.data[locationField]} onChange={(event) => schedule.setData(locationField, event.target.value)} />
                                <FieldError message={schedule.errors[locationField]} />
                            </div>
                            <Button type="submit" disabled={schedule.processing}>{schedule.processing ? 'Menyimpan...' : 'Simpan & Kirim Notifikasi'}</Button>
                        </form>
                    )}
                    <p className="text-sm text-slate-500">Jadwal: {record?.[scheduledAtField] ?? '-'} di {record?.[locationField] ?? '-'}</p>
                    {canInputResult && (
                        <form onSubmit={submitResult} className="space-y-3">
                            <div>
                                <FormLabel>File Hasil</FormLabel>
                                <TextInput type="file" accept=".pdf,.jpg,.jpeg,.png" onChange={(event) => result.setData('result_file', event.target.files?.[0] ?? null)} />
                                <FieldError message={result.errors.result_file} />
                            </div>
                            <div>
                                <FormLabel required>Status</FormLabel>
                                <SelectInput value={result.data.status} onChange={(event) => result.setData('status', event.target.value as ResultData['status'])}>
                                    <option value="passed">Lulus</option>
                                    <option value="failed">Tidak Lulus</option>
                                </SelectInput>
                                <FieldError message={result.errors.status} />
                            </div>
                            <div>
                                <FormLabel>Catatan</FormLabel>
                                <TextArea rows={3} value={result.data.notes} onChange={(event) => result.setData('notes', event.target.value)} />
                                <FieldError message={result.errors.notes} />
                            </div>
                            {result.data.status === 'failed' && (
                                <div>
                                    <FormLabel required>Alasan Penolakan</FormLabel>
                                    <TextArea rows={3} value={result.data.rejection_reason} onChange={(event) => result.setData('rejection_reason', event.target.value)} />
                                    <FieldError message={result.errors.rejection_reason} />
                                </div>
                            )}
                            <Button type="submit" disabled={result.processing}>{result.processing ? 'Mengunggah...' : 'Upload'}</Button>
                        </form>
                    )}
                </div>
            )}
        </Card>
    );
}
