import { FormEvent } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import CandidateLayout from '@/Layouts/CandidateLayout';
import { Button, Card, FieldError, PageHeader } from '@/Components/shared/ui';
import { JobPosting } from '@/lib/recruitment';

export default function Apply({ job, hasCv, existingApplicationId }: { job: { data: JobPosting }; hasCv: boolean; existingApplicationId?: number | null }): JSX.Element {
    const form = useForm({ consent: false });
    const item = job.data;
    function submit(event: FormEvent): void { event.preventDefault(); form.post(`/candidate/jobs/${item.id}/apply`); }
    return <CandidateLayout><Head title={`Lamar ${item.position_name}`} /><PageHeader title={`Lamar ${item.position_name}`} description={`${item.department?.name ?? '-'} · ${item.entity?.name ?? '-'} · ${item.work_location ?? '-'}`} /><div className="grid gap-6 lg:grid-cols-[1fr_360px]"><Card className="p-6"><h2 className="font-semibold">Info Lowongan</h2><p className="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{item.job_description}</p></Card><Card className="p-6"><form onSubmit={submit} className="space-y-4">{!hasCv && <div className="rounded-lg border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800">CV belum diupload. <Link className="font-semibold underline" href="/candidate/profile">Upload CV Sekarang</Link></div>}{existingApplicationId && <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Anda sudah pernah melamar lowongan ini. <Link className="font-semibold underline" href={`/candidate/applications/${existingApplicationId}`}>Lihat lamaran</Link></div>}<label className="flex gap-2 text-sm text-slate-700"><input type="checkbox" checked={form.data.consent} onChange={(event) => form.setData('consent', event.target.checked)} className="mt-1 rounded border-slate-300" />Saya menyetujui data saya digunakan untuk proses rekrutmen posisi ini.</label><FieldError message={form.errors.consent || (form.errors as Record<string, string>).cv || (form.errors as Record<string, string>).job} /><Button disabled={form.processing || !hasCv || !form.data.consent || Boolean(existingApplicationId)} className="w-full">{form.processing ? 'Mengirim...' : 'Kirim Lamaran'}</Button></form></Card></div></CandidateLayout>;
}
