import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ employee }: { employee: any }): JSX.Element { return <AuthenticatedLayout header={<h1 className="text-lg font-semibold">Detail Karyawan</h1>}><Head title="Detail Karyawan" /><div className="space-y-4 rounded-lg border bg-white p-4"><p className="text-xl font-semibold">{employee.full_name}</p><p>{employee.employee_id} · {employee.position_name}</p><p>{employee.email} · {employee.phone ?? '-'}</p><div className="flex gap-3"><Link className="text-blue-600" href={`/hr/preboarding/${employee.id}`}>Pre-boarding</Link><Link className="text-blue-600" href={`/hr/probation/${employee.id}`}>Probation</Link></div></div></AuthenticatedLayout>; }
