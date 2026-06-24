import { Head, router } from '@inertiajs/react';
import CandidateLayout from '@/Layouts/CandidateLayout';
import DocumentUpload from '@/Components/Candidate/DocumentUpload';
import { Badge, Button, Card, PageHeader } from '@/Components/shared/ui';
import { ApplicationItem, formatDate } from '@/lib/recruitment';

type DocumentItem = { id: number; document_type?: string; original_name?: string; uploaded_at?: string; file_path?: string };

export default function ApplicationShow({ application }: { application: { data: ApplicationItem & { documents?: { data: DocumentItem[] } | DocumentItem[] } } }): JSX.Element {
    const item = application.data;
    const job = item.job_posting;
    const documents = Array.isArray(item.documents) ? item.documents : item.documents?.data ?? [];
    const stages = ['Sedang Diproses', 'Interview', 'Verifikasi', 'Penawaran', 'Diterima'];
    const activeIndex = stageIndex(item.status);
    const canAct = !['rejected', 'withdrawn', 'hired'].includes(item.status ?? '');
    function withdraw(): void { if (confirm('Batalkan lamaran ini?')) router.post(`/candidate/jobs/${item.job_posting_id}/withdraw`, {}, { preserveScroll: true }); }
    return <CandidateLayout><Head title={`Lamaran ${job?.position_name ?? ''}`} /><PageHeader title={job?.position_name ?? 'Detail Lamaran'} description={`${job?.entity?.name ?? '-'} · ${job?.department?.name ?? '-'} · Apply ${formatDate(item.created_at)}`} /><div className="grid gap-6 lg:grid-cols-[1fr_360px]"><div className="space-y-6"><Card className="p-5"><h2 className="font-semibold">Status Saat Ini</h2><div className="mt-3"><Badge tone={item.status === 'rejected' ? 'red' : item.status === 'hired' ? 'green' : 'blue'}>{item.status_label ?? 'Lamaran Sedang Diproses'}</Badge></div>{item.status === 'rejected' && <div className="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">Terima kasih atas ketertarikan Anda. Lamaran belum dapat dilanjutkan pada tahap {item.rejection_stage ?? '-'}{item.rejection_reason ? ` karena ${item.rejection_reason}` : '.'}</div>}{item.status === 'withdrawn' && <div className="mt-4 rounded-lg bg-slate-100 p-4 text-sm text-slate-700">Lamaran dibatalkan pada {formatDate(item.withdrawn_at)}.</div>}</Card><Card className="p-5"><h2 className="mb-4 font-semibold">Timeline Progress</h2><div className="grid gap-3 md:grid-cols-5">{stages.map((stage, index) => <div key={stage} className={`rounded-lg border p-3 text-sm ${index <= activeIndex ? 'border-blue-200 bg-blue-50 text-blue-800' : 'bg-white text-slate-500'}`}><div className="font-semibold">{index < activeIndex ? '✓ ' : ''}{stage}</div></div>)}</div></Card><Card className="p-5"><h2 className="mb-4 font-semibold">Dokumen yang Diupload</h2>{documents.length === 0 ? <p className="text-sm text-slate-500">Belum ada dokumen tambahan.</p> : <div className="space-y-2">{documents.map((document) => <div key={document.id} className="flex items-center justify-between rounded-lg border p-3 text-sm"><span>{document.original_name}</span>{document.file_path && <a className="font-semibold text-blue-600" href={`/storage/${document.file_path}`} target="_blank">Download</a>}</div>)}</div>}</Card></div><aside className="space-y-4"><Card className="p-5"><h2 className="font-semibold">Aksi</h2>{canAct && <div className="mt-4 space-y-3"><DocumentUpload action={`/candidate/applications/${item.id}/documents`} /><Button type="button" variant="danger" onClick={withdraw} className="w-full">Batalkan Lamaran</Button></div>}{!canAct && <p className="mt-3 text-sm text-slate-500">Tidak ada aksi lanjutan untuk status ini.</p>}</Card></aside></div></CandidateLayout>;
}

function stageIndex(status?: string): number {
    if (['interview_hr', 'interview_user'].includes(status ?? '')) return 1;
    if (['background_check', 'mcu_simper'].includes(status ?? '')) return 2;
    if (['offering', 'hiring_decision', 'pkwt'].includes(status ?? '')) return 3;
    if (status === 'hired') return 4;
    return 0;
}
