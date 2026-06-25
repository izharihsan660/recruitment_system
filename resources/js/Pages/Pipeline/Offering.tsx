import { Button, Card, FieldError, FormLabel, GlobalErrorAlert, PageHeader, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApplicationItem } from '@/lib/recruitment';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent } from 'react';

interface OfferingData {
    start_date: string;
    contract_duration: string;
    salary_gross: string;
    salary_nett: string;
    expiry_date: string;
    allowances: Record<string, string>;
}

export default function Offering({ application }: { application: ApplicationItem }): JSX.Element {
    const { errors } = usePage<PageProps>().props;
    const offering = application.offering_letter as (ApplicationItem['offering_letter'] & { start_date?: string | null; contract_duration?: string | null; salary_gross?: number | string | null; salary_nett?: number | string | null; expiry_date?: string | null; allowances?: Record<string, string>; hr_signing_url?: string | null; candidate_signing_url?: string | null; sharepoint_url?: string | null; archive_status?: string | null }) | null | undefined;
    const facilities = application.job_posting?.recruitment_request?.facilities ?? {};
    const allowanceKeys = Object.keys(facilities).filter((key) => facilities[key]);
    const form = useForm<OfferingData>({
        start_date: offering?.start_date ?? '',
        contract_duration: offering?.contract_duration ?? '',
        salary_gross: String(offering?.salary_gross ?? ''),
        salary_nett: String(offering?.salary_nett ?? ''),
        expiry_date: offering?.expiry_date ?? '',
        allowances: offering?.allowances ?? Object.fromEntries(allowanceKeys.map((key) => [key, ''])),
    });
    const isDraft = offering?.status === 'draft';
    const canRevise = ['sent', 'negotiation'].includes(offering?.status ?? '');

    function submit(event: FormEvent): void {
        event.preventDefault();

        const url = `/hr/offering/${application.id}`;
        offering?.id ? form.put(url) : form.post(url);
    }

    function send(): void {
        router.post(`/hr/offering/${application.id}/send`, {}, {});
    }

    function revise(): void {
        form.post(`/hr/offering/${application.id}/revise`);
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Offering Letter</h1>}>
            <Head title="Offering" />
            <PageHeader
                title="Offering Letter"
                description={`${application.candidate?.name ?? 'Kandidat'} - ${application.job_posting?.position_name ?? ''}`}
                actions={<Link className="text-sm text-blue-600" href="/pipeline">Kembali ke pipeline</Link>}
            />
            <GlobalErrorAlert errors={errors} />
            <div className="space-y-4">
                <Card className="p-6">
                    <form onSubmit={submit} className="grid gap-4 md:grid-cols-2">
                        <div>
                            <FormLabel required>Tanggal Mulai</FormLabel>
                            <TextInput type="date" value={form.data.start_date} onChange={(event) => form.setData('start_date', event.target.value)} />
                            <FieldError message={form.errors.start_date} />
                        </div>
                        <div>
                            <FormLabel>Durasi Kontrak</FormLabel>
                            <TextInput value={form.data.contract_duration} onChange={(event) => form.setData('contract_duration', event.target.value)} />
                        </div>
                        <div>
                            <FormLabel required>Gaji Gross</FormLabel>
                            <TextInput type="number" value={form.data.salary_gross} onChange={(event) => form.setData('salary_gross', event.target.value)} />
                            <FieldError message={form.errors.salary_gross} />
                        </div>
                        <div>
                            <FormLabel required>Gaji Nett</FormLabel>
                            <TextInput type="number" value={form.data.salary_nett} onChange={(event) => form.setData('salary_nett', event.target.value)} />
                            <FieldError message={form.errors.salary_nett} />
                        </div>
                        {allowanceKeys.map((key) => (
                            <div key={key}>
                                <FormLabel>Tunjangan {key}</FormLabel>
                                <TextInput type="number" value={form.data.allowances[key] ?? ''} onChange={(event) => form.setData('allowances', { ...form.data.allowances, [key]: event.target.value })} />
                            </div>
                        ))}
                        <div>
                            <FormLabel required>Tanggal Expiry</FormLabel>
                            <TextInput type="date" value={form.data.expiry_date} onChange={(event) => form.setData('expiry_date', event.target.value)} />
                            <FieldError message={form.errors.expiry_date} />
                        </div>
                        <div className="flex items-end gap-2 md:col-span-2">
                            {offering?.id && <a className="rounded-md bg-slate-100 px-3 py-2 text-sm" href={`/hr/offering/${application.id}/preview`} target="_blank">Preview PDF</a>}
                            <Button type="submit" disabled={form.processing}>{form.processing ? 'Menyimpan...' : 'Simpan Draft'}</Button>
                            {isDraft && <Button type="button" onClick={send}>Kirim untuk Ditandatangani</Button>}
                            {canRevise && <Button type="button" variant="secondary" onClick={revise}>Revisi Offering</Button>}
                        </div>
                    </form>
                </Card>
                {offering?.status && (
                    <Card className="space-y-3 p-6 text-sm">
                        <p>Status: <span className="font-semibold">{offering.status}</span>{offering.signed_at ? ` • Signed ${offering.signed_at}` : ''}</p>
                        {offering.hr_signing_url && <p>Link HR: <a className="text-blue-600" href={offering.hr_signing_url}>Buka signing URL</a></p>}
                        {offering.candidate_signing_url && <p>Menunggu tanda tangan kandidat: <a className="text-blue-600" href={offering.candidate_signing_url}>Buka link</a></p>}
                        {offering.status === 'signed' && (
                            <>
                                <a className="text-blue-600" href={`/hr/offering/${application.id}/preview`}>Download PDF Signed</a>
                                <p>Arsip SharePoint: {offering.sharepoint_url ? <a className="text-blue-600" href={offering.sharepoint_url}>{offering.sharepoint_url}</a> : offering.archive_status}</p>
                            </>
                        )}
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
