import { Badge, Button, Card, EmptyState, PageHeader, SelectInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmailIntake, formatDate, humanize, Paginated, rows } from '@/lib/recruitment';
import { Head, Link, router } from '@inertiajs/react';

interface EmailIntakeIndexProps {
    emails: Paginated<EmailIntake>;
    filters: { status: string };
}

export default function EmailIntakeIndex({ emails, filters }: EmailIntakeIndexProps): JSX.Element {
    const emailRows = rows(emails);

    function changeStatus(status: string): void {
        router.get('/hr/email-intake', { status }, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Email Applicant Inbox</h1>}>
            <Head title="Email Applicant Inbox" />
            <PageHeader title="Email Applicant Inbox" description="Email baru selalu masuk Need Review sampai HR menjalankan action manual." actions={<Button onClick={() => router.post('/hr/email-intake/fetch')}>Ambil Email Terbaru</Button>} />
            <Card className="mb-4 p-4">
                <SelectInput className="max-w-xs" value={filters.status} onChange={(event) => changeStatus(event.target.value)}>
                    <option value="need_review">Need Review</option>
                    <option value="assigned_to_job">Assigned to Job</option>
                    <option value="moved_to_talent_pool">Moved to Talent Pool</option>
                    <option value="rejected">Rejected</option>
                    <option value="ignored">Ignored</option>
                    <option value="spam">Spam</option>
                    <option value="all">Semua Status</option>
                </SelectInput>
            </Card>
            <Card className="overflow-hidden">
                <div className="divide-y">
                    {emailRows.map((email) => (
                        <Link href={`/hr/email-intake/${email.id}`} key={email.id} className="block p-4 hover:bg-slate-50">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div><p className="font-semibold">{email.sender_name ?? email.sender_email}</p><p className="text-sm text-slate-500">{email.sender_email}</p></div>
                                <div className="flex gap-2">{email.is_duplicate && <Badge tone="red">Duplicate</Badge>}<Badge tone={email.status === 'need_review' ? 'red' : 'slate'}>{humanize(email.status)}</Badge></div>
                            </div>
                            <p className="mt-2 font-medium">{email.subject}</p>
                            <div className="mt-2 flex flex-wrap gap-4 text-xs text-slate-500">
                                <span>{formatDate(email.received_at)}</span>
                                <span>Suggestion: {email.suggested_job?.position_name ?? '-'}</span>
                                <span>{email.attachment_url ? 'CV tersedia' : 'Tanpa attachment CV'}</span>
                            </div>
                        </Link>
                    ))}
                </div>
                {emailRows.length === 0 && <div className="p-6"><EmptyState title="Tidak ada email" description={`Tidak ada email dengan status ${humanize(filters.status)}.`} /></div>}
            </Card>
        </AuthenticatedLayout>
    );
}
