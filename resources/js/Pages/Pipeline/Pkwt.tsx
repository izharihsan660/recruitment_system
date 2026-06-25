import { Button, Card, FieldError, FormLabel, GlobalErrorAlert, PageHeader, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApplicationItem } from '@/lib/recruitment';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent } from 'react';

export default function Pkwt({ application }: { application: ApplicationItem }): JSX.Element {
    const { errors } = usePage<PageProps>().props;
    const pkwt = application.pkwt_contract as (ApplicationItem['pkwt_contract'] & { start_date?: string | null; end_date?: string | null; candidate_signing_url?: string | null; company_signing_url?: string | null; sharepoint_url?: string | null; archive_status?: string | null }) | null | undefined;
    const form = useForm({ start_date: pkwt?.start_date ?? '', end_date: pkwt?.end_date ?? '' });
    const isDraft = pkwt?.status === 'draft';

    function create(): void {
        router.post(`/hr/pkwt/${application.id}`, {}, {});
    }

    function submit(event: FormEvent): void {
        event.preventDefault();
        form.put(`/hr/pkwt/${application.id}`);
    }

    function send(): void {
        router.post(`/hr/pkwt/${application.id}/send`, {}, {});
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">PKWT</h1>}>
            <Head title="PKWT" />
            <PageHeader
                title="PKWT / Kontrak Kerja"
                description={`${application.candidate?.name ?? 'Kandidat'} - ${application.job_posting?.position_name ?? ''}`}
                actions={<Link className="text-sm text-blue-600" href="/pipeline">Kembali ke pipeline</Link>}
            />
            <GlobalErrorAlert errors={errors} />
            <div className="space-y-4">
                <Card className="space-y-4 p-6">
                    <p className="text-sm text-slate-600">Data utama otomatis dari offering: posisi, department, lokasi, kompensasi.</p>
                    {!pkwt?.id ? (
                        <Button type="button" onClick={create}>Buat Draft PKWT</Button>
                    ) : (
                        <form onSubmit={submit} className="grid gap-4 md:grid-cols-2">
                            <div>
                                <FormLabel required>Tanggal Mulai</FormLabel>
                                <TextInput type="date" value={form.data.start_date} onChange={(event) => form.setData('start_date', event.target.value)} />
                                <FieldError message={form.errors.start_date} />
                            </div>
                            <div>
                                <FormLabel required>Tanggal Selesai</FormLabel>
                                <TextInput type="date" value={form.data.end_date} onChange={(event) => form.setData('end_date', event.target.value)} />
                                <FieldError message={form.errors.end_date} />
                            </div>
                            <div className="flex items-end gap-2 md:col-span-2">
                                <a className="rounded-md bg-slate-100 px-3 py-2 text-sm" href={`/hr/pkwt/${application.id}/preview`} target="_blank">Preview PDF</a>
                                <Button type="submit" disabled={form.processing}>{form.processing ? 'Menyimpan...' : 'Simpan'}</Button>
                                {isDraft && <Button type="button" onClick={send}>Kirim untuk Ditandatangani</Button>}
                            </div>
                        </form>
                    )}
                </Card>
                {pkwt?.status && (
                    <Card className="space-y-3 p-6 text-sm">
                        <p>Status: <span className="font-semibold">{pkwt.status}</span>{pkwt.signed_at ? ` • Signed ${pkwt.signed_at}` : ''}</p>
                        {pkwt.candidate_signing_url && <p>Kandidat: <a className="text-blue-600" href={pkwt.candidate_signing_url}>Signing URL</a></p>}
                        {pkwt.company_signing_url && <p>Company signer: <a className="text-blue-600" href={pkwt.company_signing_url}>Signing URL</a></p>}
                        {pkwt.status === 'signed' && (
                            <>
                                <a className="text-blue-600" href={`/hr/pkwt/${application.id}/preview`}>Download PDF Signed</a>
                                <p>Arsip SharePoint: {pkwt.sharepoint_url ? <a className="text-blue-600" href={pkwt.sharepoint_url}>{pkwt.sharepoint_url}</a> : pkwt.archive_status}</p>
                            </>
                        )}
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
