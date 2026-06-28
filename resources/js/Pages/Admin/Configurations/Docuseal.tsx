import { Badge, Button, Card, FormLabel, PageHeader, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface DocusealConfig {
    id: number;
    api_url: string;
    offering_template_id?: string | null;
    pkwt_template_id?: string | null;
    is_active: boolean;
    has_api_key?: boolean;
    has_webhook_secret?: boolean;
}

export default function Docuseal({ docusealConfigs }: { docusealConfigs: DocusealConfig[] }): JSX.Element {
    const current = docusealConfigs[0];
    const [result, setResult] = useState('');
    const form = useForm({
        api_url: current?.api_url ?? 'https://api.docuseal.com',
        api_key: '',
        webhook_secret: '',
        offering_template_id: current?.offering_template_id ?? '',
        pkwt_template_id: current?.pkwt_template_id ?? '',
        is_active: current?.is_active ?? true,
    });

    function submit(event: FormEvent): void {
        event.preventDefault();

        current
            ? router.put(`/admin/docuseal-configs/${current.id}`, form.data, {})
            : router.post('/admin/docuseal-configs', form.data, {});
    }

    function test(): void {
        if (! current) {
            setResult('Simpan konfigurasi dulu sebelum test koneksi.');

            return;
        }

        router.post(`/admin/docuseal-configs/${current.id}/test-connection`, {}, {
            onSuccess: () => setResult('Koneksi DocuSeal berhasil.'),
            onError: () => setResult('Koneksi DocuSeal gagal. Periksa API URL dan API Key.'),
        });
    }

    return <AuthenticatedLayout header={<h1 className="text-lg font-semibold">DocuSeal</h1>}><Head title="DocuSeal" /><PageHeader title="DocuSeal" description="Konfigurasi DocuSeal Cloud untuk e-sign Offering dan PKWT." /><Card className="p-6"><form onSubmit={submit} className="grid gap-4 md:grid-cols-2"><div><FormLabel>API URL</FormLabel><TextInput value={form.data.api_url} onChange={(event) => form.setData('api_url', event.target.value)} /></div><div><FormLabel>API Key</FormLabel><TextInput type="password" value={form.data.api_key} onChange={(event) => form.setData('api_key', event.target.value)} placeholder={current?.has_api_key ? 'Masked' : 'Masukkan API Key'} />{current?.has_api_key && <p className="mt-1 text-xs font-medium text-green-600">✓ Sudah dikonfigurasi</p>}</div><div><FormLabel>Webhook Secret</FormLabel><TextInput type="password" value={form.data.webhook_secret} onChange={(event) => form.setData('webhook_secret', event.target.value)} placeholder={current?.has_webhook_secret ? 'Masked' : 'Opsional'} /></div><div><FormLabel>Offering Template ID</FormLabel><TextInput value={form.data.offering_template_id} onChange={(event) => form.setData('offering_template_id', event.target.value)} /></div><div><FormLabel>PKWT Template ID</FormLabel><TextInput value={form.data.pkwt_template_id} onChange={(event) => form.setData('pkwt_template_id', event.target.value)} /></div><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} /> Status aktif</label><div className="flex justify-end gap-2 md:col-span-2"><Button type="button" variant="secondary" onClick={test}>Test Koneksi</Button><Button type="submit" disabled={form.processing}>{form.processing ? 'Menyimpan...' : 'Simpan'}</Button></div>{result && <div className="md:col-span-2"><Badge tone={result.includes('berhasil') ? 'green' : 'red'}>{result}</Badge></div>}</form></Card></AuthenticatedLayout>;
}
