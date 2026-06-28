import ConfirmDialog from '@/Components/ConfirmDialog';
import { Button, Card, FormLabel, PageHeader, SelectInput, TextArea, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { JobPosting, TalentPoolItem } from '@/lib/recruitment';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function TalentPoolShow({ talentPool, jobPostings }: { talentPool: TalentPoolItem; jobPostings: JobPosting[] }): JSX.Element {
    const { errors } = usePage<PageProps>().props;
    const form = useForm({ status: talentPool.status ?? 'active', tags: (talentPool.tags ?? []).join(', '), notes: talentPool.notes ?? '', job_posting_id: '' });
    const [confirmAssign, setConfirmAssign] = useState(false);

    function assignToJob(): void {
        router.post(`/hr/talent-pool/${talentPool.id}/assign-to-job`, { job_posting_id: Number(form.data.job_posting_id) }, { onFinish: () => setConfirmAssign(false) });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Detail Talent Pool</h1>}>
            <Head title="Detail Talent Pool" />
            {Object.keys(errors).length > 0 && (
                <div className="mb-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {Object.values(errors).map((error, index) => <p key={index}>{error}</p>)}
                </div>
            )}
            <PageHeader title={talentPool.candidate?.name ?? 'Kandidat'} description={talentPool.candidate?.email} />
            <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                <Card className="p-6">
                    <h2 className="font-semibold">Info Kandidat</h2>
                    <p className="mt-2 text-sm text-slate-600">No HP: {talentPool.candidate?.phone ?? '-'}</p>
                    <p className="mt-2 text-sm text-slate-600">Catatan: {talentPool.notes ?? '-'}</p>
                </Card>
                <Card className="p-6">
                    <div className="space-y-4">
                        <div><FormLabel>Status</FormLabel><SelectInput value={form.data.status} onChange={(event) => form.setData('status', event.target.value)}><option value="active">Active</option><option value="passive">Passive</option><option value="hot_prospect">Hot Prospect</option><option value="on_hold">On Hold</option><option value="do_not_contact">Do Not Contact</option><option value="hired_elsewhere">Hired Elsewhere</option><option value="archived">Archived</option></SelectInput></div>
                        <div><FormLabel>Tags</FormLabel><TextInput value={form.data.tags} onChange={(event) => form.setData('tags', event.target.value)} /></div>
                        <TextArea rows={3} value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
                        <Button onClick={() => router.put(`/hr/talent-pool/${talentPool.id}`, { status: form.data.status, tags: form.data.tags.split(',').map((tag) => tag.trim()).filter(Boolean), notes: form.data.notes })}>Simpan Status/Tags/Catatan</Button>
                        <div>
                            <FormLabel>Tarik ke Lowongan</FormLabel>
                            <SelectInput value={form.data.job_posting_id} onChange={(event) => form.setData('job_posting_id', event.target.value)}><option value="">Pilih lowongan aktif</option>{jobPostings.map((job) => <option key={job.id} value={job.id}>{job.position_name}</option>)}</SelectInput>
                        </div>
                        <Button variant="secondary" onClick={() => setConfirmAssign(true)}>Tarik ke Lowongan</Button>
                    </div>
                </Card>
            </div>
            <ConfirmDialog open={confirmAssign} title="Override talent pool?" message="Kandidat akan ditarik dari talent pool ke lowongan yang dipilih." confirmLabel="Ya, Tarik" variant="warning" onConfirm={assignToJob} onCancel={() => setConfirmAssign(false)} />
        </AuthenticatedLayout>
    );
}
