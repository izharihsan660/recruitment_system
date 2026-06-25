import { Button, Card } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

interface ErrorPageProps {
    status: number;
    message?: string;
}

const defaultMessages: Record<number, string> = {
    403: 'Akses ditolak.',
    404: 'Halaman tidak ditemukan.',
    500: 'Terjadi kesalahan server.',
};

export default function Error({ status, message }: ErrorPageProps): JSX.Element {
    const displayMessage = message || defaultMessages[status] || 'Terjadi kesalahan.';

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Terjadi Kesalahan</h1>}>
            <Head title={`${status} Error`} />
            <div className="mx-auto max-w-2xl py-12">
                <Card className="p-8 text-center">
                    <p className="text-sm font-semibold uppercase tracking-wide text-slate-500">Kode Error</p>
                    <h2 className="mt-2 text-6xl font-bold text-slate-900">{status}</h2>
                    <p className="mt-4 text-base text-slate-600">{displayMessage}</p>
                    <div className="mt-6">
                        <Link href="/dashboard">
                            <Button>Kembali ke Dashboard</Button>
                        </Link>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
