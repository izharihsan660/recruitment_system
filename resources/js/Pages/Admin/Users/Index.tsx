import ConfirmDialog from '@/Components/ConfirmDialog';
import { Badge, Button, Card, EmptyState, FieldError, FormLabel, PageHeader, SelectInput, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Department, Paginated, rows } from '@/lib/recruitment';
import { PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface UserRole {
    id?: number;
    name: string;
}

interface AdminUser {
    id: number;
    name: string;
    email: string;
    department_id?: number | null;
    is_active?: boolean;
    department?: Department | null;
    roles?: UserRole[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface UsersIndexProps {
    users: Paginated<AdminUser> & { links?: PaginationLink[] };
    departments: Department[];
    roles: string[];
}

interface FlashProps {
    flash?: {
        success?: string;
        error?: string;
    };
}

const emptyForm = {
    name: '',
    email: '',
    password: '',
    department_id: '',
    is_active: true as boolean,
    roles: [] as string[],
};

export default function UsersIndex({ users, departments, roles }: UsersIndexProps): JSX.Element {
    const [editing, setEditing] = useState<AdminUser | null>(null);
    const [open, setOpen] = useState(false);
    const [deleting, setDeleting] = useState<AdminUser | null>(null);
    const { flash } = usePage<PageProps & FlashProps>().props;
    const paginationLinks = users.links as PaginationLink[] | undefined;
    const form = useForm(emptyForm);

    function start(user?: AdminUser): void {
        setEditing(user ?? null);
        form.clearErrors();
        form.setData({
            name: user?.name ?? '',
            email: user?.email ?? '',
            password: '',
            department_id: user?.department_id ? String(user.department_id) : '',
            is_active: user?.is_active ?? true,
            roles: user?.roles?.map((role) => role.name) ?? [],
        });
        setOpen(true);
    }

    function submit(event: FormEvent): void {
        event.preventDefault();

        const payload = {
            ...form.data,
            department_id: form.data.department_id ? Number(form.data.department_id) : null,
            password: form.data.password || null,
        };

        if (editing) {
            router.put(`/admin/users/${editing.id}`, payload, { onSuccess: () => setOpen(false) });
            return;
        }

        router.post('/admin/users', payload, { onSuccess: () => setOpen(false) });
    }

    function toggleRole(role: string): void {
        form.setData('roles', form.data.roles.includes(role)
            ? form.data.roles.filter((selectedRole) => selectedRole !== role)
            : [...form.data.roles, role]);
    }

    function confirmDelete(): void {
        if (!deleting) {
            return;
        }

        router.delete(`/admin/users/${deleting.id}`, { onSuccess: () => setDeleting(null) });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Manajemen User</h1>}>
            <Head title="Manajemen User" />
            <PageHeader
                title="Manajemen User"
                description="Kelola akun, role, departemen, dan status akses user internal."
                actions={<Button onClick={() => start()}>Tambah User</Button>}
            />

            {(flash?.success || flash?.error) && (
                <div className={`mb-4 rounded-md px-4 py-3 text-sm ${flash.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
                    {flash.success ?? flash.error}
                </div>
            )}

            <Card className="overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50 text-left">
                        <tr>
                            <th className="p-4">Nama</th>
                            <th className="p-4">Email</th>
                            <th className="p-4">Role</th>
                            <th className="p-4">Department</th>
                            <th className="p-4">Status</th>
                            <th className="p-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {rows(users).map((user) => (
                            <tr key={user.id}>
                                <td className="p-4 font-medium">{user.name}</td>
                                <td className="p-4 text-slate-600">{user.email}</td>
                                <td className="p-4">
                                    <div className="flex flex-wrap gap-1">
                                        {(user.roles ?? []).map((role) => <Badge key={role.name} tone="blue">{roleLabel(role.name)}</Badge>)}
                                        {(user.roles ?? []).length === 0 && '-'}
                                    </div>
                                </td>
                                <td className="p-4">{departmentLabel(user.department)}</td>
                                <td className="p-4"><Badge tone={user.is_active ? 'green' : 'red'}>{user.is_active ? 'Aktif' : 'Nonaktif'}</Badge></td>
                                <td className="p-4">
                                    <div className="flex justify-end gap-2">
                                        <Button variant="secondary" onClick={() => start(user)}>Edit</Button>
                                        <Button variant="danger" onClick={() => setDeleting(user)}>Hapus</Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {rows(users).length === 0 && <div className="p-4"><EmptyState title="Belum ada user" description="Tambahkan user internal untuk mulai mengatur akses sistem." /></div>}
            </Card>

            {paginationLinks && paginationLinks.length > 3 && (
                <div className="mt-4 flex flex-wrap gap-2">
                    {paginationLinks.map((link, index) => (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-2 text-sm ${link.active ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'} ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}

            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <Card className="w-full max-w-2xl p-6">
                        <div className="mb-5 flex items-center justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900">{editing ? 'Edit User' : 'Tambah User'}</h2>
                                <p className="text-sm text-slate-500">Password wajib diisi saat membuat user dan boleh dikosongkan saat edit.</p>
                            </div>
                            <Button variant="ghost" onClick={() => setOpen(false)}>Tutup</Button>
                        </div>

                        <form onSubmit={submit} className="space-y-4">
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
                                    <FormLabel required={!editing}>Password</FormLabel>
                                    <TextInput type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} />
                                    <FieldError message={form.errors.password} />
                                </div>
                                <div>
                                    <FormLabel>Department</FormLabel>
                                    <SelectInput value={form.data.department_id} onChange={(event) => form.setData('department_id', event.target.value)}>
                                        <option value="">Tanpa department</option>
                                        {departments.map((department) => <option key={department.id} value={department.id}>{departmentLabel(department)}</option>)}
                                    </SelectInput>
                                    <FieldError message={form.errors.department_id} />
                                </div>
                            </div>

                            <div>
                                <FormLabel required>Role</FormLabel>
                                <div className="grid gap-2 rounded-md border border-slate-200 p-3 md:grid-cols-2">
                                    {roles.map((role) => (
                                        <label key={role} className="flex items-center gap-2 text-sm text-slate-700">
                                            <input type="checkbox" className="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked={form.data.roles.includes(role)} onChange={() => toggleRole(role)} />
                                            <span>{roleLabel(role)}</span>
                                        </label>
                                    ))}
                                </div>
                                <FieldError message={form.errors.roles} />
                            </div>

                            <label className="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" className="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} />
                                <span>User aktif</span>
                            </label>
                            <FieldError message={form.errors.is_active} />

                            <div className="flex justify-end gap-2 pt-2">
                                <Button type="button" variant="secondary" onClick={() => setOpen(false)}>Batal</Button>
                                <Button type="submit" disabled={form.processing}>{editing ? 'Simpan Perubahan' : 'Simpan User'}</Button>
                            </div>
                        </form>
                    </Card>
                </div>
            )}

            <ConfirmDialog
                open={Boolean(deleting)}
                title="Hapus User"
                message={`Yakin ingin menghapus user ${deleting?.name ?? 'ini'}? Aksi ini tidak dapat dibatalkan.`}
                confirmLabel="Ya, Hapus"
                onConfirm={confirmDelete}
                onCancel={() => setDeleting(null)}
            />
        </AuthenticatedLayout>
    );
}

function departmentLabel(department?: Department | null): string {
    if (!department) {
        return '-';
    }

    return department.entity?.name ? `${department.name} - ${department.entity.name}` : department.name;
}

function roleLabel(role: string): string {
    return role.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}
