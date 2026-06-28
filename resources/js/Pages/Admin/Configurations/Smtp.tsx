import { Badge, Button, Card, FormLabel, PageHeader, SelectInput, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Smtp {
    id: number;
    host: string;
    port: number;
    username: string;
    encryption?: string | null;
    from_address: string;
    from_name: string;
    is_active: boolean;
    has_password?: boolean;
}

export default function Smtp({ smtpSettings }: { smtpSettings: Smtp[] }): JSX.Element {
    const current = smtpSettings[0];
    const [result, setResult] = useState('');
    const form = useForm({ host: current?.host ?? '', port: String(current?.port ?? 587), username: current?.username ?? '', password: '', encryption: current?.encryption ?? 'tls', from_address: current?.from_address ?? '', from_name: current?.from_name ?? '', is_active: current?.is_active ?? true });

    function submit(event: FormEvent): void {
        event.preventDefault();

        const payload = { ...form.data, port: Number(form.data.port) };
        current ? router.put(`/admin/smtp-settings/${current.id}`, payload, {}) : router.post('/admin/smtp-settings', payload, {});
    }

    function test(): void {
        if (! current) {
            setResult('Simpan konfigurasi dulu sebelum test koneksi.');

            return;
        }

        router.post(`/admin/smtp-settings/${current.id}/test-connection`, { email: form.data.from_address }, { onSuccess: () => setResult('Test koneksi berhasil.'), onError: () => setResult('Test koneksi gagal. Periksa konfigurasi SMTP.') });
    }

    return <AuthenticatedLayout header={<h1 className="text-lg font-semibold">SMTP</h1>}><Head title="SMTP" /><PageHeader title="SMTP Setting" description="Konfigurasi email keluar recruitment system." /><Card className="p-6"><form onSubmit={submit} className="grid gap-4 md:grid-cols-2"><div><FormLabel>Host</FormLabel><TextInput value={form.data.host} onChange={(event) => form.setData('host', event.target.value)} /></div><div><FormLabel>Port</FormLabel><TextInput value={form.data.port} onChange={(event) => form.setData('port', event.target.value)} /></div><div><FormLabel>Username</FormLabel><TextInput value={form.data.username} onChange={(event) => form.setData('username', event.target.value)} /></div><div><FormLabel>Password</FormLabel><TextInput type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} placeholder={current?.has_password ? 'Masked' : 'Masukkan password SMTP'} />{current?.has_password && <p className="mt-1 text-xs font-medium text-green-600">✓ Sudah dikonfigurasi</p>}</div><div><FormLabel>Encryption</FormLabel><SelectInput value={form.data.encryption} onChange={(event) => form.setData('encryption', event.target.value)}><option value="">None</option><option value="tls">TLS</option><option value="ssl">SSL</option><option value="smtp">SMTP</option><option value="smtps">SMTPS</option></SelectInput></div><div><FormLabel>From Address</FormLabel><TextInput value={form.data.from_address} onChange={(event) => form.setData('from_address', event.target.value)} /></div><div><FormLabel>From Name</FormLabel><TextInput value={form.data.from_name} onChange={(event) => form.setData('from_name', event.target.value)} /></div><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} /> Status aktif</label><div className="flex flex-wrap items-center justify-end gap-2 md:col-span-2"><Button type="button" variant="secondary" onClick={test}>Test Koneksi</Button><Button type="submit" disabled={form.processing}>Simpan</Button></div>{result && <div className="md:col-span-2"><Badge tone={result.includes('berhasil') ? 'green' : 'red'}>{result}</Badge></div>}</form></Card></AuthenticatedLayout>;
}
