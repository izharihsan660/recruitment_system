import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, EmptyState, PageHeader } from '@/Components/shared/ui';
import { Head } from '@inertiajs/react';
export default function PreboardingIndex(): JSX.Element { return <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Pre-boarding Tasks</h1>}><Head title="Pre-boarding Tasks" /><PageHeader title="Pre-boarding Tasks" description="Daftar tugas pre-boarding untuk PIC." /><Card className="p-4"><EmptyState title="Belum ada task pre-boarding" description="Task akan muncul ketika kandidat masuk proses pre-boarding." /></Card></AuthenticatedLayout>; }
