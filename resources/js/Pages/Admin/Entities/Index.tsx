import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge, Button, Card, EmptyState, FieldError, FormLabel, PageHeader, TextInput } from '@/Components/shared/ui';
import { Entity, Paginated, rows } from '@/lib/recruitment';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

export default function EntityIndex({ entities }: { entities: Paginated<Entity> }): JSX.Element {
    const [editing, setEditing] = useState<Entity | null>(null);
    const [open, setOpen] = useState(false);
    const form = useForm({ name: '', short_name: '', is_active: true as boolean });

    function startCreate(): void {
        setEditing(null);
        form.setData({ name: '', short_name: '', is_active: true });
        setOpen(true);
    }

    function startEdit(entity: Entity): void {
        setEditing(entity);
        form.setData({ name: entity.name, short_name: entity.short_name ?? '', is_active: entity.is_active ?? true });
        setOpen(true);
    }

    function submit(event: FormEvent): void {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        editing ? form.put(`/admin/entities/${editing.id}`, options) : form.post('/admin/entities', options);
    }

    function toggle(entity: Entity): void {
        if (confirm(`Yakin ingin ${entity.is_active ? 'nonaktifkan' : 'aktifkan'} entitas ini?`)) {
            router.put(`/admin/entities/${entity.id}`, { is_active: !entity.is_active }, { preserveScroll: true });
        }
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Entitas (PT)</h1>}>
            <Head title="Entitas" />
            <PageHeader title="Entitas (PT)" description="Kelola daftar legal entity perusahaan." actions={<Button onClick={startCreate}>Tambah Entitas</Button>} />
            <Card className="overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-left text-slate-600"><tr><th className="p-4">Nama</th><th className="p-4">Singkatan</th><th className="p-4">Status</th><th className="p-4 text-right">Aksi</th></tr></thead>
                    <tbody className="divide-y">
                        {rows(entities).map((entity) => <tr key={entity.id}><td className="p-4 font-medium">{entity.name}</td><td className="p-4">{entity.short_name}</td><td className="p-4"><Badge tone={entity.is_active ? 'green' : 'slate'}>{entity.is_active ? 'Aktif' : 'Nonaktif'}</Badge></td><td className="space-x-2 p-4 text-right"><Button variant="secondary" onClick={() => startEdit(entity)}>Edit</Button><Button variant="ghost" onClick={() => toggle(entity)}>Toggle Aktif</Button></td></tr>)}
                    </tbody>
                </table>
                {rows(entities).length === 0 && <div className="p-4"><EmptyState title="Belum ada entitas" description="Klik Tambah Entitas untuk membuat data pertama." /></div>}
            </Card>
            {open && <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><Card className="w-full max-w-lg p-6"><h2 className="mb-4 text-lg font-semibold">{editing ? 'Edit Entitas' : 'Tambah Entitas'}</h2><form onSubmit={submit} className="space-y-4"><div><FormLabel>Nama</FormLabel><TextInput value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} /><FieldError message={form.errors.name} /></div><div><FormLabel>Singkatan</FormLabel><TextInput value={form.data.short_name} onChange={(event) => form.setData('short_name', event.target.value)} /><FieldError message={form.errors.short_name} /></div><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} /> Status aktif</label><div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={() => setOpen(false)}>Batal</Button><Button disabled={form.processing}>{form.processing ? 'Menyimpan...' : 'Simpan'}</Button></div></form></Card></div>}
        </AuthenticatedLayout>
    );
}
