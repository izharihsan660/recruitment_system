import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';

type Props = { application: any; record: any; canProceed: boolean };

export default function McuSimper({ application, record, canProceed }: Props): JSX.Element {
    return <AuthenticatedLayout header={<h1 className="text-lg font-semibold">MCU / SIMPER</h1>}><Head title="MCU / SIMPER" />
        <div className="space-y-4">
            <div className="rounded-lg border bg-white p-4"><p className="font-semibold">{application.candidate.name}</p><p className="text-sm text-slate-500">{application.job_posting.position_name}</p>{!record && <button className="mt-3 rounded bg-blue-600 px-3 py-2 text-sm text-white" onClick={() => router.post(`/hr/mcu-simper/${application.id}`)}>Buat Record</button>}</div>
            <div className="grid gap-4 lg:grid-cols-2"><Section type="mcu" title="MCU" application={application} record={record} /><Section type="simper" title="SIMPER" application={application} record={record} /></div>
            <button disabled={!canProceed} onClick={() => router.post(`/hr/mcu-simper/${application.id}/proceed`)} className="rounded bg-green-600 px-4 py-2 text-sm font-medium text-white disabled:bg-slate-300">Lanjut ke Hiring Decision</button>
        </div></AuthenticatedLayout>;
}

function Section({ type, title, application, record }: { type: 'mcu'|'simper'; title: string; application: any; record: any }): JSX.Element {
    const schedule = useForm({ [`${type}_scheduled_at`]: '', [`${type}_location`]: '' });
    const result = useForm({ result_file: null as File | null, status: 'passed', notes: '', rejection_reason: '' });
    const status = record?.[`${type}_status`];
    return <div className="rounded-lg border bg-white p-4"><div className="mb-3 flex items-center justify-between"><h2 className="font-semibold">{title}</h2><span className="rounded bg-slate-100 px-2 py-1 text-xs">{status ?? 'Belum Ada'}</span></div>{status === 'not_required' ? <p className="rounded bg-slate-100 p-3 text-sm">Tidak Diperlukan</p> : <div className="space-y-4"><form onSubmit={(e) => { e.preventDefault(); schedule.post(`/hr/mcu-simper/${application.id}/schedule-${type}`); }} className="space-y-2"><input type="datetime-local" className="w-full rounded border p-2" onChange={(e) => schedule.setData(`${type}_scheduled_at` as any, e.target.value)} /><input className="w-full rounded border p-2" placeholder="Lokasi" onChange={(e) => schedule.setData(`${type}_location` as any, e.target.value)} /><button disabled={schedule.processing} className="rounded bg-blue-600 px-3 py-2 text-sm text-white">Simpan & Kirim Notifikasi</button></form><p className="text-sm text-slate-500">Jadwal: {record?.[`${type}_scheduled_at`] ?? '-'} di {record?.[`${type}_location`] ?? '-'}</p><form onSubmit={(e) => { e.preventDefault(); result.post(`/hr/mcu-simper/${application.id}/result-${type}`, { forceFormData: true }); }} className="space-y-2"><input type="file" accept=".pdf,.jpg,.jpeg,.png" className="w-full rounded border p-2" onChange={(e) => result.setData('result_file', e.target.files?.[0] ?? null)} /><select className="w-full rounded border p-2" value={result.data.status} onChange={(e) => result.setData('status', e.target.value)}><option value="passed">Lulus</option><option value="failed">Tidak Lulus</option></select><textarea className="w-full rounded border p-2" placeholder="Catatan" onChange={(e) => result.setData('notes', e.target.value)} />{result.data.status === 'failed' && <textarea className="w-full rounded border p-2" placeholder="Alasan penolakan" onChange={(e) => result.setData('rejection_reason', e.target.value)} />}<button disabled={result.processing} className="rounded bg-blue-600 px-3 py-2 text-sm text-white">Upload</button></form></div>}</div>;
}
