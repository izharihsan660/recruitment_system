import { Button, Card, FieldError, FormLabel, PageHeader, SelectInput, TextArea, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { CandidateSource, JobPosting } from '@/lib/recruitment';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type CandidateInputForm = {
    job_posting_id: string;
    name: string;
    email: string;
    phone: string;
    source_id: string;
    referral_name: string;
    referral_department: string;
    referral_phone: string;
    referral_relation: string;
    referral_notes: string;
    consent: boolean;
    notes: string;
    cv: File | null;
    education_level: string;
    education_major: string;
    education_institution: string;
    experience_company: string;
    experience_position: string;
    experience_years: string;
};

const educationLevels = ['SD', 'SMP', 'SMA/SMK', 'D3', 'S1', 'S2', 'S3'];

export default function CandidateInput({ jobPostings, candidateSources }: { jobPostings: JobPosting[]; candidateSources: CandidateSource[] }): JSX.Element {
    const [tab, setTab] = useState<'pipeline' | 'talent'>('pipeline');
    const form = useForm<CandidateInputForm>({
        job_posting_id: '',
        name: '',
        email: '',
        phone: '',
        source_id: '',
        referral_name: '',
        referral_department: '',
        referral_phone: '',
        referral_relation: '',
        referral_notes: '',
        consent: false,
        notes: '',
        cv: null,
        education_level: '',
        education_major: '',
        education_institution: '',
        experience_company: '',
        experience_position: '',
        experience_years: '',
    });

    function submit(event: FormEvent): void {
        event.preventDefault();

        form.post(tab === 'pipeline' ? '/hr/candidates/input-to-job' : '/hr/candidates/input-to-talent-pool', {
            forceFormData: true,
        });
    }

    const isReferral = candidateSources.find((source) => String(source.id) === form.data.source_id)?.name.toLowerCase() === 'referral';

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Input Kandidat</h1>}>
            <Head title="Input Kandidat" />
            <PageHeader title="HR Input Kandidat" description="Input manual kandidat ke pipeline atau talent pool." />

            <Card className="p-6">
                <div className="mb-4 flex gap-2">
                    <Button variant={tab === 'pipeline' ? 'primary' : 'secondary'} onClick={() => setTab('pipeline')}>Ke Pipeline</Button>
                    <Button variant={tab === 'talent' ? 'primary' : 'secondary'} onClick={() => setTab('talent')}>Ke Talent Pool</Button>
                </div>

                <form onSubmit={submit}>
                    <div className="border-t pt-4">
                        <h3 className="mb-3 text-sm font-semibold text-slate-700">Data Pribadi</h3>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <FormLabel required>Nama</FormLabel>
                                <TextInput value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                                <FieldError message={form.errors.name} />
                            </div>
                            <div>
                                <FormLabel required>Email</FormLabel>
                                <TextInput type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} />
                                <FieldError message={form.errors.email} />
                            </div>
                            <div>
                                <FormLabel required>No HP</FormLabel>
                                <TextInput value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} />
                                <FieldError message={form.errors.phone} />
                            </div>
                            <div>
                                <FormLabel>Source</FormLabel>
                                <SelectInput value={form.data.source_id} onChange={(event) => form.setData('source_id', event.target.value)}>
                                    <option value="">Pilih source</option>
                                    {candidateSources.map((source) => <option key={source.id} value={source.id}>{source.name}</option>)}
                                </SelectInput>
                                <FieldError message={form.errors.source_id} />
                            </div>
                        </div>

                        {isReferral && (
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <TextInput placeholder="Nama referral" value={form.data.referral_name} onChange={(event) => form.setData('referral_name', event.target.value)} />
                                <TextInput placeholder="Departemen referral" value={form.data.referral_department} onChange={(event) => form.setData('referral_department', event.target.value)} />
                                <TextInput placeholder="HP referral" value={form.data.referral_phone} onChange={(event) => form.setData('referral_phone', event.target.value)} />
                                <TextInput placeholder="Relasi referral" value={form.data.referral_relation} onChange={(event) => form.setData('referral_relation', event.target.value)} />
                            </div>
                        )}
                    </div>

                    <div className="mt-4 border-t pt-4">
                        <h3 className="mb-3 text-sm font-semibold text-slate-700">Upload CV</h3>
                        <div>
                            <FormLabel required>Upload CV</FormLabel>
                            <input
                                type="file"
                                accept=".pdf,.doc,.docx"
                                onChange={(event) => form.setData('cv', event.target.files?.[0] ?? null)}
                                className="w-full rounded-md border border-slate-300 text-sm file:mr-4 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700"
                            />
                            <FieldError message={form.errors.cv} />
                        </div>
                    </div>

                    <div className="mt-4 border-t pt-4">
                        <h3 className="mb-3 text-sm font-semibold text-slate-700">Pendidikan Terakhir</h3>
                        <div className="grid gap-4 lg:grid-cols-3">
                            <div>
                                <FormLabel required>Level Pendidikan</FormLabel>
                                <SelectInput value={form.data.education_level} onChange={(event) => form.setData('education_level', event.target.value)}>
                                    <option value="">Pilih level</option>
                                    {educationLevels.map((level) => <option key={level} value={level}>{level}</option>)}
                                </SelectInput>
                                <FieldError message={form.errors.education_level} />
                            </div>
                            <div>
                                <FormLabel>Jurusan</FormLabel>
                                <TextInput value={form.data.education_major} onChange={(event) => form.setData('education_major', event.target.value)} />
                                <FieldError message={form.errors.education_major} />
                            </div>
                            <div>
                                <FormLabel>Institusi</FormLabel>
                                <TextInput value={form.data.education_institution} onChange={(event) => form.setData('education_institution', event.target.value)} />
                                <FieldError message={form.errors.education_institution} />
                            </div>
                        </div>
                    </div>

                    <div className="mt-4 border-t pt-4">
                        <h3 className="mb-3 text-sm font-semibold text-slate-700">Pengalaman Kerja Terakhir (opsional)</h3>
                        <div className="grid gap-4 lg:grid-cols-3">
                            <div>
                                <FormLabel>Nama Perusahaan</FormLabel>
                                <TextInput value={form.data.experience_company} onChange={(event) => form.setData('experience_company', event.target.value)} />
                                <FieldError message={form.errors.experience_company} />
                            </div>
                            <div>
                                <FormLabel>Posisi/Jabatan</FormLabel>
                                <TextInput value={form.data.experience_position} onChange={(event) => form.setData('experience_position', event.target.value)} />
                                <FieldError message={form.errors.experience_position} />
                            </div>
                            <div>
                                <FormLabel>Lama Kerja (tahun)</FormLabel>
                                <TextInput type="number" min="0" value={form.data.experience_years} onChange={(event) => form.setData('experience_years', event.target.value)} />
                                <FieldError message={form.errors.experience_years} />
                            </div>
                        </div>
                    </div>

                    {tab === 'pipeline' && (
                        <div className="mt-4 border-t pt-4">
                            <h3 className="mb-3 text-sm font-semibold text-slate-700">Lowongan</h3>
                            <div>
                                <FormLabel required>Lowongan</FormLabel>
                                <SelectInput value={form.data.job_posting_id} onChange={(event) => form.setData('job_posting_id', event.target.value)}>
                                    <option value="">Pilih lowongan</option>
                                    {jobPostings.map((job) => <option key={job.id} value={job.id}>{job.position_name}</option>)}
                                </SelectInput>
                                <FieldError message={form.errors.job_posting_id} />
                            </div>
                        </div>
                    )}

                    {tab === 'talent' && (
                        <div className="mt-4 border-t pt-4">
                            <h3 className="mb-3 text-sm font-semibold text-slate-700">Catatan Talent Pool</h3>
                            <TextArea placeholder="Catatan" value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
                        </div>
                    )}

                    <div className="mt-4 border-t pt-4">
                        <h3 className="mb-3 text-sm font-semibold text-slate-700">Consent</h3>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={form.data.consent} onChange={(event) => form.setData('consent', event.target.checked)} /> Kandidat memberikan consent pemrosesan data
                        </label>
                        <FieldError message={form.errors.consent} />
                    </div>

                    <div className="mt-4 flex justify-end border-t pt-4">
                        <Button type="submit" disabled={form.processing}>Simpan</Button>
                    </div>
                </form>
            </Card>
        </AuthenticatedLayout>
    );
}
