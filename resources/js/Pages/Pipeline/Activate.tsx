import ConfirmDialog from '@/Components/ConfirmDialog';
import { Button, Card, FieldError, FormLabel, GlobalErrorAlert, PageHeader, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApplicationItem } from '@/lib/recruitment';
import { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type ActivateApplication = ApplicationItem & {
    pkwt_contract?: { start_date?: string | null; position_name?: string | null; contract_type?: string | null } | null;
};

export default function Activate({ application }: { application: ActivateApplication }): JSX.Element {
    const { errors } = usePage<PageProps>().props;
    const [confirmOpen, setConfirmOpen] = useState(false);
    const form = useForm({ employee_id: '', start_date: application.pkwt_contract?.start_date ?? '' });

    function submit(event: FormEvent): void {
        event.preventDefault();
        setConfirmOpen(true);
    }

    function confirmActivate(): void {
        setConfirmOpen(false);
        form.post(`/hr/employees/${application.id}/activate`);
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Aktivasi Karyawan</h1>}>
            <Head title="Aktivasi Karyawan" />
            <PageHeader title="Aktivasi Karyawan" description={`${application.candidate?.name ?? 'Kandidat'} - ${application.job_posting?.position_name ?? ''}`} actions={<Link className="text-sm text-blue-600" href="/pipeline">Kembali ke pipeline</Link>} />
            <GlobalErrorAlert errors={errors} />
            <Card className="max-w-2xl p-6">
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <FormLabel required>Employee ID</FormLabel>
                        <TextInput value={form.data.employee_id} onChange={(event) => form.setData('employee_id', event.target.value)} />
                        <FieldError message={form.errors.employee_id} />
                    </div>
                    <div>
                        <FormLabel required>Tanggal Mulai</FormLabel>
                        <TextInput type="date" value={form.data.start_date} onChange={(event) => form.setData('start_date', event.target.value)} />
                        <FieldError message={form.errors.start_date} />
                    </div>
                    <div className="rounded bg-slate-50 p-3 text-sm">
                        <p>Nama: {application.candidate?.name ?? '-'}</p>
                        <p>Posisi: {application.pkwt_contract?.position_name ?? '-'}</p>
                        <p>Dept/PT: {application.job_posting?.department?.name ?? '-'} / {application.job_posting?.entity?.name ?? '-'}</p>
                        <p>Tipe: {application.pkwt_contract?.contract_type ?? '-'}</p>
                    </div>
                    <Button type="submit" disabled={form.processing}>{form.processing ? 'Mengaktifkan...' : 'Aktifkan'}</Button>
                </form>
            </Card>
            <ConfirmDialog open={confirmOpen} title="Aktifkan sebagai karyawan?" message="Data kandidat akan diproses menjadi karyawan aktif." confirmLabel="Ya, Aktifkan" cancelLabel="Batal" variant="warning" onConfirm={confirmActivate} onCancel={() => setConfirmOpen(false)} />
        </AuthenticatedLayout>
    );
}
