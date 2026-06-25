import { Button, Card, FormLabel, PageHeader, SelectInput, TextArea, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { JobPosting, RecruitmentRequest } from '@/lib/recruitment';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function JobPostingForm({ mode, jobPosting, approvedFpk }: { mode: 'create' | 'edit'; jobPosting?: JobPosting | null; approvedFpk: RecruitmentRequest[] }): JSX.Element {
    const form = useForm({
        recruitment_request_id: String(jobPosting?.recruitment_request_id ?? ''),
        position_name: jobPosting?.position_name ?? '',
        job_description: jobPosting?.job_description ?? '',
        requirements: jobPosting?.requirements ?? '',
        work_location: jobPosting?.work_location ?? '',
        mcu_required: jobPosting?.mcu_required ?? false,
        simper_required: jobPosting?.simper_required ?? false,
        test_required: jobPosting?.test_required ?? true,
    });

    const isEditable = mode === 'create' || jobPosting?.status === 'draft';
    const selectedFpkName = jobPosting?.recruitment_request?.position_name ?? form.data.position_name ?? '-';

    function submit(event: FormEvent, openAfter = false): void {
        event.preventDefault();
        const payload = { ...form.data, recruitment_request_id: Number(form.data.recruitment_request_id) };
        const options = {
            onSuccess: () => {
                if (openAfter && jobPosting?.id) router.post(`/job-postings/${jobPosting.id}/open`);
            },
        };
        mode === 'edit' && jobPosting ? router.put(`/job-postings/${jobPosting.id}`, payload, options) : router.post('/job-postings', payload, options);
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">{mode === 'edit' ? 'Edit Lowongan' : 'Buat Lowongan'}</h1>}>
            <Head title="Form Lowongan" />
            <PageHeader title={mode === 'edit' ? 'Edit Lowongan' : 'Buat Lowongan'} description="Isi informasi lowongan kerja dari FPK approved." />
            <Card className="p-6">
                <form onSubmit={(e) => submit(e)} className="space-y-4">
                    <div>
                        <FormLabel>FPK</FormLabel>
                        {isEditable ? (
                            <SelectInput
                                value={form.data.recruitment_request_id}
                                onChange={(e) => {
                                    const selectedId = e.target.value;
                                    form.setData('recruitment_request_id', selectedId);
                                    const selectedFpk = approvedFpk.find((item) => String(item.id) === selectedId);
                                    if (selectedFpk) {
                                        form.setData({
                                            ...form.data,
                                            recruitment_request_id: selectedId,
                                            position_name: selectedFpk.position_name ?? '',
                                            work_location: selectedFpk.work_location ?? '',
                                            job_description: selectedFpk.job_description ?? '',
                                            requirements: `Pendidikan: ${selectedFpk.min_education ?? ''}\nPengalaman: ${selectedFpk.min_experience ?? ''}\nSkill: ${selectedFpk.required_skills ?? ''}`,
                                        });
                                    }
                                }}
                            >
                                <option value="">Pilih FPK approved</option>
                                {approvedFpk.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.position_name}
                                    </option>
                                ))}
                            </SelectInput>
                        ) : (
                            <div className="rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700">{selectedFpkName}</div>
                        )}
                    </div>
                    <div>
                        <FormLabel>Posisi</FormLabel>
                        <TextInput value={form.data.position_name} onChange={(e) => form.setData('position_name', e.target.value)} />
                    </div>
                    <div>
                        <FormLabel>Deskripsi</FormLabel>
                        <TextArea rows={5} value={form.data.job_description} onChange={(e) => form.setData('job_description', e.target.value)} />
                    </div>
                    <div>
                        <FormLabel>Persyaratan</FormLabel>
                        <TextArea rows={5} value={form.data.requirements} onChange={(e) => form.setData('requirements', e.target.value)} />
                    </div>
                    <div>
                        <FormLabel>Lokasi Kerja</FormLabel>
                        <TextInput value={form.data.work_location} onChange={(e) => form.setData('work_location', e.target.value)} />
                    </div>
                    <div className="grid gap-3 md:grid-cols-3">
                        {(['mcu_required', 'simper_required', 'test_required'] as const).map((field) => (
                            <label key={field} className="flex items-center justify-between rounded-md border p-3 text-sm">
                                <span>{field.replaceAll('_', ' ')}</span>
                                <input type="checkbox" checked={form.data[field]} onChange={(e) => form.setData(field, e.target.checked)} />
                            </label>
                        ))}
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button type="submit" variant="secondary">
                            Simpan Draft
                        </Button>
                        <Button type="button" onClick={(e) => submit(e, true)}>
                            Buka Lowongan
                        </Button>
                    </div>
                </form>
            </Card>
        </AuthenticatedLayout>
    );
}
