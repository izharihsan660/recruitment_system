import { ChangeEvent, FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Button, FieldError, TextInput } from '@/Components/shared/ui';

export default function DocumentUpload({ action }: { action: string }): JSX.Element {
    const form = useForm<{ document_type: string; file: File | null }>({ document_type: 'Dokumen Tambahan', file: null });
    const [clientError, setClientError] = useState<string>('');

    function pickFile(event: ChangeEvent<HTMLInputElement>): void {
        const file = event.target.files?.[0] ?? null;
        setClientError('');
        if (file && !['application/pdf', 'image/jpeg', 'image/png'].includes(file.type)) {
            setClientError('Format file harus PDF, JPG, atau PNG.');
            return;
        }
        if (file && file.size > 5 * 1024 * 1024) {
            setClientError('Ukuran file maksimal 5MB.');
            return;
        }
        form.setData('file', file);
    }

    function submit(event: FormEvent): void {
        event.preventDefault();
        form.post(action, { forceFormData: true, onSuccess: () => form.reset('file') });
    }

    return <form onSubmit={submit} className="space-y-3 rounded-lg border border-dashed bg-white p-4"><div><label className="text-sm font-medium">Jenis Dokumen</label><TextInput value={form.data.document_type} onChange={(event) => form.setData('document_type', event.target.value)} /></div><label className="flex cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed p-6 text-center text-sm text-slate-600 hover:bg-slate-50"><span>{form.data.file ? form.data.file.name : 'Klik atau drag file PDF/JPG/PNG maksimal 5MB'}</span><input type="file" accept=".pdf,.jpg,.jpeg,.png" className="sr-only" onChange={pickFile} /></label><FieldError message={clientError || form.errors.file || form.errors.document_type} />{form.progress && <div className="h-2 overflow-hidden rounded-full bg-slate-100"><div className="h-full bg-blue-600" style={{ width: `${form.progress.percentage}%` }} /></div>}<Button disabled={form.processing || !form.data.file}>{form.processing ? 'Mengunggah...' : 'Upload Dokumen'}</Button></form>;
}
