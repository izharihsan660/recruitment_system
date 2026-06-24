import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge, Button, Card, EmptyState, FieldError, FormLabel, PageHeader, TextInput } from '@/Components/shared/ui';
import { CandidateSource, Paginated, rows } from '@/lib/recruitment';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

export default function CandidateSourcesIndex({ candidateSources }: { candidateSources: Paginated<CandidateSource> }): JSX.Element {
    const [editing, setEditing] = useState<CandidateSource | null>(null);
    const [open, setOpen] = useState(false);
    const form = useForm({ name: '', is_active: true as boolean });

    function submit(event: FormEvent): void {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        editing ? form.put(`/admin/candidate-sources/${editing.id}`, options) : form.post('/admin/candidate-sources', options);
    }

    function startEdit(source: CandidateSource): void {
        setEditing(source);
        form.setData({ name: source.name, is_active: source.is_active ?? true });
        setOpen(true);
    }

    return <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Source Kandidat</h1>}><Head title="Source Kandidat" /><PageHeader title="Source Kandidat" description="Kelola asal kandidat." actions={<Button onClick={() => { setEditing(null); form.setData({ name: '', is_active: true }); setOpen(true); }}>Tambah Source</Button>} /><Card className="overflow-hidden"><table className="w-full text-sm"><thead className="bg-slate-50 text-left"><tr><th className="p-4">Nama</th><th className="p-4">Status</th><th className="p-4 text-right">Aksi</th></tr></thead><tbody className="divide-y">{rows(candidateSources).map((source) => <tr key={source.id}><td className="p-4 font-medium">{source.name}</td><td className="p-4"><Badge tone={source.is_active ? 'green' : 'slate'}>{source.is_active ? 'Aktif' : 'Nonaktif'}</Badge></td><td className="space-x-2 p-4 text-right"><Button variant="secondary" onClick={() => startEdit(source)}>Edit</Button><Button variant="ghost" onClick={() => confirm('Yakin ubah status source ini?') && router.put(`/admin/candidate-sources/${source.id}`, { is_active: !source.is_active }, { preserveScroll: true })}>Toggle Aktif</Button></td></tr>)}</tbody></table>{rows(candidateSources).length === 0 && <div className="p-4"><EmptyState title="Belum ada source kandidat" /></div>}</Card>{open && <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><Card className="w-full max-w-lg p-6"><h2 className="mb-4 text-lg font-semibold">{editing ? 'Edit Source' : 'Tambah Source'}</h2><form onSubmit={submit} className="space-y-4"><div><FormLabel>Nama</FormLabel><TextInput value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} /><FieldError message={form.errors.name} /></div><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} /> Status aktif</label><div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={() => setOpen(false)}>Batal</Button><Button disabled={form.processing}>Simpan</Button></div></form></Card></div>}</AuthenticatedLayout>;
}
