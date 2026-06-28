import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button, Card, FormLabel, PageHeader, TextArea, TextInput } from '@/Components/shared/ui';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface ValueItem {
    title: string;
    description: string;
}

interface Profile {
    id?: number;
    company_name?: string;
    tagline?: string;
    about?: string;
    values?: ValueItem[];
    full_hero_image_url?: string | null;
    full_gallery_urls?: string[];
    address?: string;
    email?: string;
    phone?: string;
}

export default function Cms({ companyProfile }: { companyProfile?: Profile | null }): JSX.Element {
    const form = useForm({
        company_name: companyProfile?.company_name ?? '',
        tagline: companyProfile?.tagline ?? '',
        about: companyProfile?.about ?? '',
        values: companyProfile?.values?.length ? companyProfile.values : [{ title: '', description: '' }],
        address: companyProfile?.address ?? '',
        email: companyProfile?.email ?? '',
        phone: companyProfile?.phone ?? '',
    });

    function submit(event: FormEvent): void {
        event.preventDefault();
        router.put('/admin/company-profile', form.data as any, {});
    }

    function setValue(index: number, field: keyof ValueItem, value: string): void {
        const next = [...form.data.values];
        next[index] = { ...next[index], [field]: value };
        form.setData('values', next);
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">CMS Portal</h1>}>
            <Head title="CMS Portal" />
            <PageHeader title="CMS Portal" description="Kelola konten portal kandidat." />
            <form onSubmit={submit} className="space-y-6">
                <Card className="p-6">
                    <h2 className="mb-4 font-semibold">Hero</h2>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <FormLabel>Nama Perusahaan</FormLabel>
                            <TextInput value={form.data.company_name} onChange={(e) => form.setData('company_name', e.target.value)} />
                        </div>
                        <div>
                            <FormLabel>Tagline</FormLabel>
                            <TextInput value={form.data.tagline} onChange={(e) => form.setData('tagline', e.target.value)} />
                        </div>
                    </div>
                    {companyProfile?.full_hero_image_url && <img src={companyProfile.full_hero_image_url} alt="Hero" className="mt-4 h-40 rounded-md object-cover" />}
                    <div className="mt-3">
                        <FormLabel>Upload Hero Image</FormLabel>
                        <input
                            type="file"
                            accept="image/*"
                            onChange={(e) => {
                                const file = e.target.files?.[0];

                                if (!file) {
                                    return;
                                }

                                const formData = new FormData();
                                formData.append('hero_image', file);
                                router.post('/admin/company-profile/hero-image', formData, { forceFormData: true, onSuccess: () => router.reload() });
                            }}
                            className="mt-1 block"
                        />
                    </div>
                </Card>

                <Card className="p-6">
                    <h2 className="mb-4 font-semibold">Tentang Kami</h2>
                    <TextArea rows={5} value={form.data.about} onChange={(e) => form.setData('about', e.target.value)} />
                </Card>

                <Card className="p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="font-semibold">Nilai & Kultur</h2>
                        <Button type="button" variant="secondary" onClick={() => form.setData('values', [...form.data.values, { title: '', description: '' }])}>
                            Tambah Item
                        </Button>
                    </div>
                    <div className="space-y-4">
                        {form.data.values.map((item, index) => (
                            <div key={index} className="rounded-md border p-4">
                                <div className="grid gap-3 md:grid-cols-2">
                                    <TextInput placeholder="Judul" value={item.title} onChange={(e) => setValue(index, 'title', e.target.value)} />
                                    <TextInput placeholder="Deskripsi" value={item.description} onChange={(e) => setValue(index, 'description', e.target.value)} />
                                </div>
                                <Button type="button" variant="ghost" className="mt-2" onClick={() => form.setData('values', form.data.values.filter((_, itemIndex) => itemIndex !== index))}>
                                    Hapus
                                </Button>
                            </div>
                        ))}
                    </div>
                </Card>

                <Card className="p-6">
                    <h2 className="mb-4 font-semibold">Galeri</h2>
                    {companyProfile?.full_gallery_urls && companyProfile.full_gallery_urls.length > 0 && (
                        <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                            {companyProfile.full_gallery_urls.map((url, index) => (
                                <div key={index} className="relative">
                                    <img src={url} alt={`Galeri ${index + 1}`} className="h-28 w-full rounded-md object-cover" />
                                    <button
                                        type="button"
                                        onClick={() => {
                                            router.delete(`/admin/company-profile/gallery/${index}`, { onSuccess: () => router.reload() });
                                        }}
                                        className="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs text-white hover:bg-red-600"
                                    >
                                        ×
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                    <div className="mt-3">
                        <FormLabel>Tambah Foto Galeri</FormLabel>
                        <input
                            type="file"
                            accept="image/*"
                            multiple
                            onChange={async (e) => {
                                const files = e.target.files;

                                if (!files?.length) {
                                    return;
                                }

                                const fileArray = Array.from(files);

                                for (let i = 0; i < fileArray.length; i++) {
                                    const file = fileArray[i];

                                    await new Promise<void>((resolve) => {
                                        const formData = new FormData();
                                        formData.append('image', file);
                                        router.post('/admin/company-profile/gallery', formData, {
                                            forceFormData: true,
                                            onFinish: () => resolve(),
                                        });
                                    });
                                }

                                router.reload();
                            }}
                            className="mt-1 block"
                        />
                    </div>
                </Card>

                <Card className="p-6">
                    <h2 className="mb-4 font-semibold">Kontak</h2>
                    <div className="grid gap-4 md:grid-cols-3">
                        <TextInput placeholder="Alamat" value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} />
                        <TextInput placeholder="Email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                        <TextInput placeholder="Nomor Telepon" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />
                    </div>
                </Card>
                <div className="sticky bottom-4 flex justify-end">
                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Menyimpan...' : 'Simpan'}
                    </Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
