import { Button, Card, FormLabel, PageHeader, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface EmailIntakeSetting {
    tenant_id?: string;
    client_id?: string;
    mailbox_address?: string;
    is_active?: boolean;
    sync_interval_minutes?: number;
    last_synced_at?: string | null;
    last_received_at?: string | null;
}

export default function EmailIntakeConfiguration({ setting }: { setting?: EmailIntakeSetting | null }): JSX.Element {
    const form = useForm({
        tenant_id: setting?.tenant_id ?? '',
        client_id: setting?.client_id ?? '',
        client_secret: '',
        mailbox_address: setting?.mailbox_address ?? 'karir@nusantaraabadijaya.com',
        is_active: setting?.is_active ?? true,
        sync_interval_minutes: setting?.sync_interval_minutes ?? 10,
    });

    function submit(event: FormEvent): void {
        event.preventDefault();
        form.put('/admin/email-intake-settings', { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Email Applicant Intake</h1>}>
            <Head title="Email Applicant Intake" />
            <PageHeader title="Microsoft Graph Email Intake" description="Inbound Mail.Read khusus shared mailbox kandidat, terpisah dari Graph Mail Sender." />
            <Card className="p-6">
                <form onSubmit={submit} className="grid gap-4 md:grid-cols-2">
                    <div><FormLabel required>Tenant ID</FormLabel><TextInput value={form.data.tenant_id} onChange={(event) => form.setData('tenant_id', event.target.value)} /></div>
                    <div><FormLabel required>Client ID</FormLabel><TextInput value={form.data.client_id} onChange={(event) => form.setData('client_id', event.target.value)} /></div>
                    <div><FormLabel>Client Secret</FormLabel><TextInput type="password" value={form.data.client_secret} onChange={(event) => form.setData('client_secret', event.target.value)} placeholder={setting ? 'Kosongkan jika tidak berubah' : 'Masukkan client secret'} /></div>
                    <div><FormLabel required>Shared Mailbox</FormLabel><TextInput type="email" value={form.data.mailbox_address} onChange={(event) => form.setData('mailbox_address', event.target.value)} /></div>
                    <div><FormLabel required>Interval Sinkronisasi (menit)</FormLabel><TextInput type="number" min="10" max="1440" value={form.data.sync_interval_minutes} onChange={(event) => form.setData('sync_interval_minutes', Number(event.target.value))} /></div>
                    <label className="flex items-center gap-2 self-end text-sm"><input type="checkbox" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} /> Integrasi aktif</label>
                    <div className="rounded-md bg-slate-50 p-4 text-sm text-slate-600 md:col-span-2">
                        <p>Sinkronisasi terakhir: {setting?.last_synced_at ?? '-'}</p>
                        <p>Watermark email terakhir: {setting?.last_received_at ?? '-'}</p>
                    </div>
                    <div className="flex justify-end md:col-span-2"><Button type="submit" disabled={form.processing}>{form.processing ? 'Menyimpan...' : 'Simpan Konfigurasi'}</Button></div>
                </form>
            </Card>
        </AuthenticatedLayout>
    );
}
