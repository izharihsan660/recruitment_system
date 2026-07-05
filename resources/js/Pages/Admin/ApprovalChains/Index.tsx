import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge, Button, Card, EmptyState, FormLabel, PageHeader, SelectInput } from '@/Components/shared/ui';
import { BasicUser, Department, rows } from '@/lib/recruitment';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';

interface Chain {
    id: number;
    department_id: number;
    approver_user_id?: number | null;
    approver_user?: BasicUser;
    department?: Department;
    has_records?: boolean;
}

export default function ApprovalChainsIndex({ approvalChains, departments, users }: { approvalChains: Chain[]; departments: Department[]; users: BasicUser[] }): JSX.Element {
    const [departmentId, setDepartmentId] = useState(String(departments[0]?.id ?? ''));
    const [editing, setEditing] = useState<Chain | null>(null);
    const [open, setOpen] = useState(false);

    const selectedChains = useMemo(
        () => rows(approvalChains).filter((chain) => String(chain.department_id) === departmentId),
        [approvalChains, departmentId],
    );
    const existingUserIds = useMemo(() => selectedChains.map((chain) => chain.approver_user_id).filter(Boolean), [selectedChains]);
    const availableUsers = useMemo(
        () => users.filter((user) => editing?.approver_user_id === user.id || ! existingUserIds.includes(user.id)),
        [editing?.approver_user_id, existingUserIds, users],
    );

    const form = useForm<{ department_id: string; user_ids: string[]; approver_user_id: string }>({
        department_id: departmentId,
        user_ids: [],
        approver_user_id: '',
    });

    function startCreate(): void {
        setEditing(null);
        form.clearErrors();
        form.setData({ department_id: departmentId, user_ids: [], approver_user_id: '' });
        setOpen(true);
    }

    function startEdit(chain: Chain): void {
        setEditing(chain);
        form.clearErrors();
        form.setData({ department_id: departmentId, user_ids: [], approver_user_id: String(chain.approver_user_id ?? '') });
        setOpen(true);
    }

    function toggleUser(userId: string): void {
        form.setData('user_ids', form.data.user_ids.includes(userId) ? form.data.user_ids.filter((id) => id !== userId) : [...form.data.user_ids, userId]);
    }

    function submit(event: FormEvent): void {
        event.preventDefault();

        if (editing) {
            router.put(`/admin/approval-chains/${editing.id}`, {
                department_id: Number(form.data.department_id),
                approver_user_id: Number(form.data.approver_user_id),
            }, { onSuccess: () => setOpen(false) });

            return;
        }

        router.post('/admin/approval-chains', {
            department_id: Number(form.data.department_id),
            user_ids: form.data.user_ids.map(Number),
        }, { onSuccess: () => setOpen(false) });
    }

    function destroy(chain: Chain): void {
        if (confirm(`Hapus ${chain.approver_user?.name ?? 'approver'} dari departemen ini?`)) {
            router.delete(`/admin/approval-chains/${chain.id}`);
        }
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Approval Chain</h1>}>
            <Head title="Approval Chain" />
            <PageHeader
                title="Approval Chain"
                description="Atur daftar user approver per departemen. Semua approver akan menerima approval bersamaan."
                actions={<Button onClick={startCreate}>Tambah Approver</Button>}
            />
            <Card className="mb-4 p-4">
                <FormLabel>Departemen</FormLabel>
                <SelectInput value={departmentId} onChange={(event) => setDepartmentId(event.target.value)}>
                    {departments.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}
                </SelectInput>
            </Card>
            <Card className="overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-left">
                        <tr>
                            <th className="p-4">Approver</th>
                            <th className="p-4">Email</th>
                            <th className="p-4">Status</th>
                            <th className="p-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {selectedChains.map((chain) => (
                            <tr key={chain.id}>
                                <td className="p-4 font-semibold">{chain.approver_user?.name}</td>
                                <td className="p-4 text-slate-600">{chain.approver_user?.email}</td>
                                <td className="p-4"><Badge tone="blue">User Approver</Badge></td>
                                <td className="space-x-2 p-4 text-right">
                                    <Button variant="secondary" onClick={() => startEdit(chain)}>Edit</Button>
                                    <Button variant="destructive" size="sm" onClick={() => destroy(chain)}>Hapus</Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {selectedChains.length === 0 && <div className="p-4"><EmptyState title="Belum ada approver" description="Tambahkan user approver untuk departemen ini." /></div>}
            </Card>
            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <Card className="w-full max-w-lg p-6">
                        <h2 className="mb-4 text-lg font-semibold">{editing ? 'Edit Approver' : 'Tambah Approver'}</h2>
                        <form onSubmit={submit} className="space-y-4">
                            {editing ? (
                                <div>
                                    <FormLabel>Approver</FormLabel>
                                    <SelectInput value={form.data.approver_user_id} onChange={(event) => form.setData('approver_user_id', event.target.value)}>
                                        <option value="">Pilih user</option>
                                        {availableUsers.map((user) => <option key={user.id} value={user.id}>{user.name} — {user.email}</option>)}
                                    </SelectInput>
                                    {form.errors.approver_user_id && <p className="mt-1 text-sm text-red-600">{form.errors.approver_user_id}</p>}
                                </div>
                            ) : (
                                <div>
                                    <FormLabel>Pilih User Approver</FormLabel>
                                    <div className="mt-2 max-h-72 space-y-2 overflow-y-auto rounded-md border border-slate-200 p-3">
                                        {availableUsers.map((user) => (
                                            <label key={user.id} className="flex items-start gap-3 rounded-md p-2 hover:bg-slate-50">
                                                <input type="checkbox" className="mt-1" checked={form.data.user_ids.includes(String(user.id))} onChange={() => toggleUser(String(user.id))} />
                                                <span>
                                                    <span className="block font-medium">{user.name}</span>
                                                    <span className="text-sm text-slate-500">{user.email}</span>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                    {availableUsers.length === 0 && <p className="mt-2 text-sm text-slate-500">Semua user aktif sudah menjadi approver departemen ini.</p>}
                                    {form.errors.user_ids && <p className="mt-1 text-sm text-red-600">{form.errors.user_ids}</p>}
                                </div>
                            )}
                            <div className="flex justify-end gap-2">
                                <Button type="button" variant="secondary" onClick={() => setOpen(false)}>Batal</Button>
                                <Button type="submit" disabled={editing ? form.data.approver_user_id === '' : form.data.user_ids.length === 0}>Simpan</Button>
                            </div>
                        </form>
                    </Card>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
