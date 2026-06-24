import { Badge, Button, Card, FormLabel, PageHeader, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface DocusealConfig { id: number; api_url: string; is_active: boolean; has_api_key?: boolean; has_webhook_secret?: boolean }

export default function Docuseal({ docusealConfigs }: { docusealConfigs: DocusealConfig[] }): JSX.Element {
    const current = docusealConfigs[0];
    const [result, setResult] = useState('');
    const form = useForm({ api_url: current?.api_url ?? 'https://api.docuseal.com', api_key: '', webhook_secret: '', is_active: current?.is_active ?? true });
    function submit(event: FormEvent): void { event.preventDefault(); current ? router.put(`/admin/docuseal-configs/${current.id}`, form.data, { preserveScroll: true }) : router.post('/admin/docuseal-configs', form.data, { preserveScroll: true }); }
    function test(): void { if (!current) { setResult('Simpan konfigurasi dulu sebelum test koneksi.'); return; } window.axios.post(`/admin/docuseal-configs/${current.id}/test-connection`, {}).then((response) => setResult(response.data.message ?? 'Koneksi DocuSeal berhasil.')).catch(() => setResult('Koneksi DocuSeal gagal. Periksa API URL dan API Key.')); }
    return <AuthenticatedLayout header={<h1 className="text-lg font-semibold">DocuSeal</h1>}><Head title="DocuSeal" /><PageHeader title="DocuSeal" description="Konfigurasi DocuSeal Cloud untuk e-sign Offering dan PKWT." /><Card className="p-6"><form onSubmit={submit} className="grid gap-4 md:grid-cols-2"><div><FormLabel>API URL</FormLabel><TextInput value={form.data.api_url} onChange={(e) => form.setData('api_url', e.target.value)} /></div><div><FormLabel>API Key</FormLabel><TextInput type="password" value={form.data.api_key} onChange={(e) => form.setData('api_key', e.target.value)} placeholder={current?.has_api_key ? 'Masked' : 'Masukkan API Key'} /></div><div><FormLabel>Webhook Secret</FormLabel><TextInput type="password" value={form.data.webhook_secret} onChange={(e) => form.setData('webhook_secret', e.target.value)} placeholder={current?.has_webhook_secret ? 'Masked' : 'Opsional'} /></div><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} /> Status aktif</label><div className="flex justify-end gap-2 md:col-span-2"><Button type="button" variant="secondary" onClick={test}>Test Koneksi</Button><Button disabled={form.processing}>{form.processing ? 'Menyimpan...' : 'Simpan'}</Button></div>{result && <div className="md:col-span-2"><Badge tone={result.includes('berhasil') ? 'green' : 'red'}>{result}</Badge></div>}</form></Card></AuthenticatedLayout>;
}
