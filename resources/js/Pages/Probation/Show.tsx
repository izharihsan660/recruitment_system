import ConfirmDialog from '@/Components/ConfirmDialog';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface EmployeePayload { full_name: string; position_name: string }
interface ProbationEvaluationPayload { milestone: string }
interface ProbationPayload {
    id: number;
    status: string;
    day30_due: string;
    day60_due: string;
    day90_due: string;
    extended_until?: string | null;
    evaluations?: ProbationEvaluationPayload[];
}

export default function Show({ employee, probation }: { employee: EmployeePayload; probation: ProbationPayload }): JSX.Element {
    const activeMilestone = currentMilestone(probation.status);
    const evalForm = useForm({ milestone: activeMilestone, performance_notes: '', recommendation: 'permanent' });
    const outcomeForm = useForm({ outcome: 'permanent', extended_until: '' });
    const { errors } = usePage<PageProps>().props;
    const [confirmTerminate, setConfirmTerminate] = useState(false);
    const evaluatedMilestones = probation.evaluations?.map((evaluation) => evaluation.milestone) ?? [];
    const canEvaluate = Boolean(activeMilestone) && !evaluatedMilestones.includes(activeMilestone);
    const canSubmitOutcome = ['90_day_review', 'day90_review', 'extended'].includes(probation.status);

    function submitOutcome(event?: FormEvent): void {
        event?.preventDefault();
        outcomeForm.post(`/hr/probation/${probation.id}/outcome`, { onFinish: () => setConfirmTerminate(false) });
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Detail Probation</h1>}>
            <Head title="Detail Probation" />
            {Object.keys(errors).length > 0 && (
                <div className="mb-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {Object.values(errors).map((error, index) => <p key={index}>{error}</p>)}
                </div>
            )}
            <div className="space-y-4">
                <div className="rounded-lg border bg-white p-4">
                    <p className="font-semibold">{employee.full_name}</p>
                    <p>{employee.position_name} · {probation.status}</p>
                    <p>Day 30: {probation.day30_due} · Day 60: {probation.day60_due} · Day 90: {probation.day90_due}</p>
                    {probation.extended_until && <p>Extended sampai {probation.extended_until}</p>}
                </div>
                {canEvaluate && (
                    <form onSubmit={(event) => { event.preventDefault(); evalForm.post(`/hr/probation/${probation.id}/evaluate`); }} className="space-y-2 rounded-lg border bg-white p-4">
                        <select className="rounded border p-2" value={evalForm.data.milestone} onChange={(event) => evalForm.setData('milestone', event.target.value)}>
                            <option value="day30">Day 30</option>
                            <option value="day60">Day 60</option>
                            <option value="day90">Day 90</option>
                            <option value="extended">Extended</option>
                        </select>
                        <textarea className="w-full rounded border p-2" placeholder="Performance notes" onChange={(event) => evalForm.setData('performance_notes', event.target.value)} />
                        <select className="rounded border p-2" onChange={(event) => evalForm.setData('recommendation', event.target.value)}>
                            <option value="permanent">Permanen</option>
                            <option value="extended">Perpanjang</option>
                            <option value="terminated">Terminasi</option>
                        </select>
                        <button className="rounded bg-blue-600 px-3 py-2 text-white">Isi Evaluasi</button>
                    </form>
                )}
                {canSubmitOutcome && (
                    <form onSubmit={(event) => { event.preventDefault(); outcomeForm.data.outcome === 'terminated' ? setConfirmTerminate(true) : submitOutcome(); }} className="space-y-2 rounded-lg border bg-white p-4">
                        <select className="rounded border p-2" onChange={(event) => outcomeForm.setData('outcome', event.target.value)}>
                            <option value="permanent">Permanen</option>
                            <option value="extended">Perpanjang</option>
                            <option value="terminated">Terminasi</option>
                        </select>
                        {outcomeForm.data.outcome === 'extended' && <input type="date" className="rounded border p-2" onChange={(event) => outcomeForm.setData('extended_until', event.target.value)} />}
                        <button className="rounded bg-green-600 px-3 py-2 text-white">Submit Outcome</button>
                    </form>
                )}
            </div>
            <ConfirmDialog open={confirmTerminate} title="Terminate probation?" message="Karyawan akan ditandai terminasi dari proses probation." confirmLabel="Ya, Terminasi" onConfirm={() => submitOutcome()} onCancel={() => setConfirmTerminate(false)} />
        </AuthenticatedLayout>
    );
}

function currentMilestone(status: string): string {
    const milestones: Record<string, string> = {
        in_progress: 'day30',
        day30_review: 'day30',
        day60_review: 'day60',
        day90_review: 'day90',
        '90_day_review': 'day90',
        extended: 'extended',
    };

    return milestones[status] ?? '';
}
