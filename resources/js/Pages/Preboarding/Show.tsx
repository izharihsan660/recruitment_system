import ConfirmDialog from '@/Components/ConfirmDialog';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

interface PreboardingItem { id: number; title: string; status: string; pic?: { name?: string } | null }
interface ChecklistPayload { id: number; status: string; first_day: string; items: PreboardingItem[] }
interface EmployeePayload { full_name: string }
interface UserPayload { id: number; name: string }

export default function Show({ employee, checklist, users }: { employee: EmployeePayload; checklist: ChecklistPayload; users: UserPayload[] }): JSX.Element {
    const itemForm = useForm({ title: '', description: '' });
    const [deleteItemId, setDeleteItemId] = useState<number | null>(null);
    const days = Math.ceil((new Date(checklist.first_day).getTime() - Date.now()) / 86400000);

    function deleteItem(): void {
        if (!deleteItemId) {
            return;
        }

        router.delete(`/hr/preboarding/items/${deleteItemId}`, { onFinish: () => setDeleteItemId(null) });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Pre-boarding</h1>}>
            <Head title="Pre-boarding" />
            <div className="space-y-4">
                <div className="rounded-lg border bg-white p-4"><p className="font-semibold">{employee.full_name}</p><p>Status: {checklist.status} · First day dalam {days} hari</p><div className="mt-2 h-2 rounded bg-slate-100"><div className="h-2 rounded bg-blue-600" style={{ width: `${(checklist.items.filter((item) => item.status === 'done').length / Math.max(checklist.items.length, 1)) * 100}%` }} /></div></div>
                <form onSubmit={(event) => { event.preventDefault(); itemForm.post(`/hr/preboarding/${checklist.id}/items`); }} className="flex gap-2"><input className="rounded border p-2" placeholder="Tambah item" onChange={(event) => itemForm.setData('title', event.target.value)} /><button className="rounded bg-blue-600 px-3 text-white">Tambah Item</button></form>
                {checklist.items.map((item) => <div key={item.id} className="rounded-lg border bg-white p-4"><div className="flex items-center justify-between"><div><p className="font-medium">{item.title}</p><p className="text-sm text-slate-500">PIC: {item.pic?.name ?? '-'}</p></div><span>{item.status}</span></div><div className="mt-3 flex gap-2"><select className="rounded border p-2" onChange={(event) => router.post(`/hr/preboarding/items/${item.id}/assign`, { assigned_to: event.target.value })}><option>Pilih PIC</option>{users.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}</select><button className="rounded bg-green-600 px-3 text-white" onClick={() => router.post(`/hr/preboarding/items/${item.id}/complete`)}>Selesai</button><button className="rounded bg-red-600 px-3 text-white" onClick={() => setDeleteItemId(item.id)}>Hapus</button></div></div>)}
            </div>
            <ConfirmDialog open={deleteItemId !== null} title="Hapus item preboarding?" message="Item checklist ini akan dihapus." confirmLabel="Ya, Hapus" onConfirm={deleteItem} onCancel={() => setDeleteItemId(null)} />
        </AuthenticatedLayout>
    );
}
