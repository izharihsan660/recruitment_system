import { FormEvent, useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';

import { Button, Card, FieldError, FormLabel, PageHeader, SelectInput, TextArea, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Department, Entity, RecruitmentRequest } from '@/lib/recruitment';

const steps = ['Informasi Umum', 'Detail Kebutuhan', 'Alasan Kebutuhan', 'Kualifikasi', 'Tugas & Tanggung Jawab', 'Kompensasi & Fasilitas'];

const facilityLabels = {
    salary_gross: 'Gaji Gross',
    salary_nett: 'Gaji Nett',
    transport: 'Transportasi',
    health: 'Kesehatan',
    communication: 'Komunikasi',
    meal: 'Makan',
    overtime: 'Lembur',
    vehicle: 'Kendaraan',
    laptop: 'Laptop',
    mess: 'Mess',
    apd: 'APD',
    uniform: 'Seragam',
} as const;

const facilityKeys = Object.keys(facilityLabels) as Array<keyof typeof facilityLabels>;

type FacilityKey = keyof typeof facilityLabels;
type FacilityData = Record<FacilityKey, boolean>;

const emptyFacilities = facilityKeys.reduce((facilities, key) => ({ ...facilities, [key]: false }), {} as FacilityData);

const errorStepMap: Record<string, number> = {
    entity_id: 0,
    department_id: 0,
    requester_position: 0,
    requested_at: 0,
    position_name: 1,
    headcount: 1,
    employment_status: 1,
    job_title: 1,
    work_location: 1,
    required_at: 1,
    reason_type: 2,
    reason_notes: 2,
    min_education: 3,
    min_experience: 3,
    required_skills: 3,
    age_min: 3,
    age_max: 3,
    gender: 3,
    job_description: 4,
    facilities: 5,
};

function fieldStep(field: string): number {
    if (field.startsWith('facilities.')) {
        return 5;
    }

    return errorStepMap[field] ?? 0;
}

export default function FpkForm({ mode, fpk, entities, departments }: { mode: 'create' | 'edit'; fpk?: RecruitmentRequest | null; entities: Entity[]; departments: Department[] }): JSX.Element {
    const [step, setStep] = useState(0);
    const form = useForm({
        entity_id: String(fpk?.entity_id ?? ''),
        department_id: String(fpk?.department_id ?? ''),
        requester_position: fpk?.requester_position ?? '',
        requested_at: fpk?.requested_at ?? new Date().toISOString().slice(0, 10),
        position_name: fpk?.position_name ?? '',
        headcount: String(fpk?.headcount ?? 1),
        employment_status: fpk?.employment_status ?? 'contract',
        job_title: fpk?.job_title ?? '',
        work_location: fpk?.work_location ?? '',
        required_at: fpk?.required_at ?? '',
        reason_type: fpk?.reason_type ?? 'replacement',
        reason_notes: fpk?.reason_notes ?? '',
        min_education: fpk?.min_education ?? '',
        min_experience: fpk?.min_experience ?? '',
        required_skills: fpk?.required_skills ?? '',
        age_min: String(fpk?.age_min ?? ''),
        age_max: String(fpk?.age_max ?? ''),
        gender: fpk?.gender ?? 'any',
        job_description: fpk?.job_description ?? '',
        facilities: { ...emptyFacilities, ...(fpk?.facilities ?? {}) },
    });

    useEffect(() => {
        const firstError = Object.keys(form.errors)[0];

        if (firstError) {
            setStep(fieldStep(firstError));
        }
    }, [form.errors]);

    function payload() {
        return {
            ...form.data,
            entity_id: Number(form.data.entity_id),
            department_id: Number(form.data.department_id),
            headcount: Number(form.data.headcount),
            age_min: form.data.age_min ? Number(form.data.age_min) : null,
            age_max: form.data.age_max ? Number(form.data.age_max) : null,
        };
    }

    function submit(event: FormEvent, shouldSubmit = false): void {
        event.preventDefault();

        if (mode === 'edit' && fpk) {
            router.put(`/fpk/${fpk.id}`, payload(), {
                onSuccess: () => {
                    if (shouldSubmit) {
                        router.post(`/fpk/${fpk.id}/submit`);
                    }
                },
            });

            return;
        }

        router.post('/fpk', payload(), {
            onSuccess: () => {
                if (shouldSubmit) {
                    const newId = window.location.pathname.split('/')[2];

                    if (newId) {
                        router.post(`/fpk/${newId}/submit`);
                    }
                }
            },
        });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">{mode === 'edit' ? 'Edit FPK' : 'Buat FPK'}</h1>}>
            <Head title="Form FPK" />
            <PageHeader title={mode === 'edit' ? 'Edit FPK' : 'Buat FPK'} description="Lengkapi wizard 6 step sesuai section A-F FSD." />
            <Card className="p-4">
                <div className="mb-6 grid grid-cols-2 gap-2 md:grid-cols-6">
                    {steps.map((label, index) => (
                        <button type="button" key={label} onClick={() => setStep(index)} className={`rounded-md px-3 py-2 text-xs font-semibold ${step === index ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'}`}>
                            {index + 1}. {label}
                        </button>
                    ))}
                </div>
                <form onSubmit={(event) => submit(event)} className="space-y-4">
                    {step === 0 && (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div><FormLabel required>PT</FormLabel><SelectInput value={form.data.entity_id} onChange={(event) => form.setData('entity_id', event.target.value)}><option value="">Pilih PT</option>{entities.map((entity) => <option key={entity.id} value={entity.id}>{entity.name}</option>)}</SelectInput><FieldError message={form.errors.entity_id} /></div>
                            <div><FormLabel required>Departemen</FormLabel><SelectInput value={form.data.department_id} onChange={(event) => form.setData('department_id', event.target.value)}><option value="">Pilih Departemen</option>{departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}</SelectInput><FieldError message={form.errors.department_id} /></div>
                            <div><FormLabel required>Jabatan Requester</FormLabel><TextInput value={form.data.requester_position} onChange={(event) => form.setData('requester_position', event.target.value)} /><FieldError message={form.errors.requester_position} /></div>
                            <div><FormLabel required>Tanggal</FormLabel><TextInput type="date" value={form.data.requested_at} onChange={(event) => form.setData('requested_at', event.target.value)} /><FieldError message={form.errors.requested_at} /></div>
                        </div>
                    )}
                    {step === 1 && (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div><FormLabel required>Posisi</FormLabel><TextInput value={form.data.position_name} onChange={(event) => form.setData('position_name', event.target.value)} /><FieldError message={form.errors.position_name} /></div>
                            <div><FormLabel required>Headcount</FormLabel><TextInput type="number" value={form.data.headcount} onChange={(event) => form.setData('headcount', event.target.value)} /><FieldError message={form.errors.headcount} /></div>
                            <div><FormLabel required>Status Karyawan</FormLabel><SelectInput value={form.data.employment_status} onChange={(event) => form.setData('employment_status', event.target.value)}><option value="permanent">Permanent</option><option value="contract">Contract</option><option value="intern">Intern</option></SelectInput><FieldError message={form.errors.employment_status} /></div>
                            <div><FormLabel required>Lokasi Kerja</FormLabel><TextInput value={form.data.work_location} onChange={(event) => form.setData('work_location', event.target.value)} /><FieldError message={form.errors.work_location} /></div>
                            <div><FormLabel required>Job Title</FormLabel><TextInput value={form.data.job_title} onChange={(event) => form.setData('job_title', event.target.value)} /><FieldError message={form.errors.job_title} /></div>
                            <div><FormLabel required>Dibutuhkan Tanggal</FormLabel><TextInput type="date" value={form.data.required_at} onChange={(event) => form.setData('required_at', event.target.value)} /><FieldError message={form.errors.required_at} /></div>
                        </div>
                    )}
                    {step === 2 && (
                        <div className="space-y-4">
                            <div><FormLabel required>Alasan Kebutuhan</FormLabel><SelectInput value={form.data.reason_type} onChange={(event) => form.setData('reason_type', event.target.value)}><option value="replacement">Replacement</option><option value="addition">Addition</option><option value="new_project">New Project</option><option value="other">Other</option></SelectInput><FieldError message={form.errors.reason_type} /></div>
                            <div><FormLabel required>Catatan Alasan</FormLabel><TextArea rows={5} value={form.data.reason_notes} onChange={(event) => form.setData('reason_notes', event.target.value)} /><FieldError message={form.errors.reason_notes} /></div>
                        </div>
                    )}
                    {step === 3 && (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div><FormLabel required>Pendidikan Minimal</FormLabel><TextInput value={form.data.min_education} onChange={(event) => form.setData('min_education', event.target.value)} /><FieldError message={form.errors.min_education} /></div>
                            <div><FormLabel required>Pengalaman Minimal</FormLabel><TextInput value={form.data.min_experience} onChange={(event) => form.setData('min_experience', event.target.value)} /><FieldError message={form.errors.min_experience} /></div>
                            <div><FormLabel>Usia Min</FormLabel><TextInput type="number" value={form.data.age_min} onChange={(event) => form.setData('age_min', event.target.value)} /><FieldError message={form.errors.age_min} /></div>
                            <div><FormLabel>Usia Max</FormLabel><TextInput type="number" value={form.data.age_max} onChange={(event) => form.setData('age_max', event.target.value)} /><FieldError message={form.errors.age_max} /></div>
                            <div><FormLabel>Gender</FormLabel><SelectInput value={form.data.gender} onChange={(event) => form.setData('gender', event.target.value)}><option value="any">Semua</option><option value="male">Pria</option><option value="female">Wanita</option></SelectInput><FieldError message={form.errors.gender} /></div>
                            <div className="md:col-span-2"><FormLabel required>Skill Wajib</FormLabel><TextArea rows={4} value={form.data.required_skills} onChange={(event) => form.setData('required_skills', event.target.value)} /><FieldError message={form.errors.required_skills} /></div>
                        </div>
                    )}
                    {step === 4 && <div><FormLabel required>Tugas & Tanggung Jawab</FormLabel><TextArea rows={8} value={form.data.job_description} onChange={(event) => form.setData('job_description', event.target.value)} /><FieldError message={form.errors.job_description} /></div>}
                    {step === 5 && (
                        <div>
                            <div className="grid gap-3 md:grid-cols-2">
                                {facilityKeys.map((key) => (
                                    <label key={key} className="flex items-center justify-between rounded-md border p-3 text-sm">
                                        <span>{facilityLabels[key]} <span className="text-red-600">*</span></span>
                                        <input type="checkbox" checked={form.data.facilities[key]} onChange={(event) => form.setData('facilities', { ...form.data.facilities, [key]: event.target.checked })} />
                                    </label>
                                ))}
                            </div>
                            <FieldError message={form.errors.facilities} />
                            {facilityKeys.map((key) => <FieldError key={key} message={form.errors[`facilities.${key}` as keyof typeof form.errors]} />)}
                        </div>
                    )}
                    <div className="flex justify-between pt-4">
                        <Button type="button" variant="secondary" disabled={step === 0} onClick={() => setStep((current) => Math.max(0, current - 1))}>Sebelumnya</Button>
                        <div className="space-x-2">
                            {step < steps.length - 1 ? <Button type="button" onClick={() => setStep((current) => current + 1)}>Lanjut</Button> : <><Button type="submit" variant="secondary">Simpan Draft</Button><Button type="button" onClick={(event) => submit(event, true)}>Simpan & Submit</Button></>}
                        </div>
                    </div>
                </form>
            </Card>
        </AuthenticatedLayout>
    );
}
