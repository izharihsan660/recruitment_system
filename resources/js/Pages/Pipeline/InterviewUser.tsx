import ScoreRating from '@/Components/ScoreRating';
import { Button, Card, FieldError, FormLabel, GlobalErrorAlert, PageHeader, SelectInput, TextArea, TextInput } from '@/Components/shared/ui';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ApplicationItem, BasicUser, rows } from '@/lib/recruitment';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

interface InterviewRecord { id?: number; scheduled_at?: string; location?: string; interviewer?: BasicUser; score_technical?: number | null; score_experience?: number | null; score_problem_solving?: number | null; score_team_fit?: number | null; recommendation?: 'accepted' | 'considered' | 'rejected' | null; rejection_reason?: string | null; notes?: string | null; }

export default function InterviewUser({ application, interview, interviewers, canSchedule, canScorecard }: { application: ApplicationItem; interview?: InterviewRecord | null; interviewers: BasicUser[] | { data: BasicUser[] }; canSchedule: boolean; canScorecard: boolean }): JSX.Element {
    const { errors } = usePage<PageProps>().props;
    const users = rows(interviewers);
    const scheduleForm = useForm({ scheduled_at: interview?.scheduled_at ?? '', location: interview?.location ?? '', interviewer_id: String(interview?.interviewer?.id ?? users[0]?.id ?? '') });
    const scoreForm = useForm({ score_technical: interview?.score_technical ?? null as number | null, score_experience: interview?.score_experience ?? null as number | null, score_problem_solving: interview?.score_problem_solving ?? null as number | null, score_team_fit: interview?.score_team_fit ?? null as number | null, recommendation: interview?.recommendation ?? 'accepted', rejection_reason: interview?.rejection_reason ?? '', notes: interview?.notes ?? '' });
    const setScore = (field: 'score_technical' | 'score_experience' | 'score_problem_solving' | 'score_team_fit', value: number) => scoreForm.setData(field, value);

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Interview User</h1>}>
            <Head title="Interview User" />
            <PageHeader title="Interview User" description={`${application.candidate?.name ?? 'Kandidat'} - ${application.job_posting?.position_name ?? ''}`} actions={<Link className="text-sm text-blue-600" href="/pipeline">Kembali ke pipeline</Link>} />
            <GlobalErrorAlert errors={errors} />
            <div className="grid gap-4 lg:grid-cols-2">
                <Card className="p-6">
                    <h2 className="mb-4 font-semibold">Jadwal Interview</h2>
                    <form onSubmit={(event) => { event.preventDefault(); const method = interview?.id ? scheduleForm.put : scheduleForm.post; method(`/hr/interview-user/${application.id}/${interview?.id ? 'reschedule' : 'schedule'}`); }} className="space-y-4">
                        <div><FormLabel required>Tanggal & Waktu</FormLabel><TextInput type="datetime-local" disabled={!canSchedule} value={scheduleForm.data.scheduled_at} onChange={(event) => scheduleForm.setData('scheduled_at', event.target.value)} /><FieldError message={scheduleForm.errors.scheduled_at} /></div>
                        <div><FormLabel required>Lokasi</FormLabel><TextInput disabled={!canSchedule} value={scheduleForm.data.location} onChange={(event) => scheduleForm.setData('location', event.target.value)} /><FieldError message={scheduleForm.errors.location} /></div>
                        <div><FormLabel required>Interviewer</FormLabel><SelectInput disabled={!canSchedule} value={scheduleForm.data.interviewer_id} onChange={(event) => scheduleForm.setData('interviewer_id', event.target.value)}>{users.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}</SelectInput><FieldError message={scheduleForm.errors.interviewer_id} /></div>
                        {canSchedule && <Button type="submit" disabled={scheduleForm.processing}>{scheduleForm.processing ? 'Menyimpan...' : 'Simpan Jadwal'}</Button>}
                    </form>
                </Card>
                <Card className="p-6">
                    <h2 className="mb-4 font-semibold">Scorecard Hiring Manager</h2>
                    <form onSubmit={(event) => { event.preventDefault(); scoreForm.post(`/hr/interview-user/${application.id}/scorecard`); }} className="space-y-4">
                        <ScoreRating label="Kemampuan Teknis" required error={scoreForm.errors.score_technical} value={scoreForm.data.score_technical} onChange={(value) => setScore('score_technical', value)} readOnly={!canScorecard} />
                        <ScoreRating label="Pengalaman Kerja" required error={scoreForm.errors.score_experience} value={scoreForm.data.score_experience} onChange={(value) => setScore('score_experience', value)} readOnly={!canScorecard} />
                        <ScoreRating label="Problem Solving" required error={scoreForm.errors.score_problem_solving} value={scoreForm.data.score_problem_solving} onChange={(value) => setScore('score_problem_solving', value)} readOnly={!canScorecard} />
                        <ScoreRating label="Kesesuaian dengan Tim" required error={scoreForm.errors.score_team_fit} value={scoreForm.data.score_team_fit} onChange={(value) => setScore('score_team_fit', value)} readOnly={!canScorecard} />
                        <div><FormLabel required>Rekomendasi</FormLabel><SelectInput disabled={!canScorecard} value={scoreForm.data.recommendation} onChange={(event) => scoreForm.setData('recommendation', event.target.value as never)}><option value="accepted">Diterima</option><option value="considered">Dipertimbangkan</option><option value="rejected">Ditolak</option></SelectInput><FieldError message={scoreForm.errors.recommendation} /></div>
                        {scoreForm.data.recommendation === 'rejected' && <div><FormLabel required>Alasan Penolakan</FormLabel><TextArea disabled={!canScorecard} rows={3} value={scoreForm.data.rejection_reason} onChange={(event) => scoreForm.setData('rejection_reason', event.target.value)} /><FieldError message={scoreForm.errors.rejection_reason} /></div>}
                        <div><FormLabel>Catatan</FormLabel><TextArea disabled={!canScorecard} rows={3} value={scoreForm.data.notes} onChange={(event) => scoreForm.setData('notes', event.target.value)} /><FieldError message={scoreForm.errors.notes} /></div>
                        {canScorecard && <Button type="submit" disabled={!interview?.id || scoreForm.processing}>{scoreForm.processing ? 'Menyimpan...' : 'Submit Scorecard'}</Button>}
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
