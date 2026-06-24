import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, Menu, User, X } from 'lucide-react';
import { PropsWithChildren, useState } from 'react';
import { PageProps } from '@/types';

export default function CandidateLayout({ children }: PropsWithChildren): JSX.Element {
    const { auth } = usePage<PageProps>().props;
    const candidate = auth.candidate;
    const [open, setOpen] = useState(false);
    const [dropdownOpen, setDropdownOpen] = useState(false);

    const links = [
        { label: 'Beranda', href: '/portal' },
        { label: 'Lowongan', href: '/portal/jobs' },
        { label: 'Lamaran Saya', href: '/candidate/applications' },
    ];

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <header className="sticky top-0 z-40 border-b bg-white/95 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <Link href="/candidate/dashboard" className="font-bold text-blue-600">Portal Kandidat</Link>
                    <nav className="hidden items-center gap-6 md:flex">
                        {links.map((link) => <Link key={link.href} href={link.href} className="text-sm font-medium text-slate-600 hover:text-blue-600">{link.label}</Link>)}
                    </nav>
                    <div className="hidden md:block">
                        <button type="button" onClick={() => setDropdownOpen(!dropdownOpen)} className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"><User className="h-4 w-4" />{candidate?.name}</button>
                        {dropdownOpen && <div className="absolute right-8 mt-2 w-44 rounded-md border bg-white p-2 shadow-lg"><Link href="/candidate/profile" className="block rounded px-3 py-2 text-sm hover:bg-slate-100">Profil</Link><button type="button" onClick={() => router.post('/candidate/logout')} className="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"><LogOut className="h-4 w-4" />Logout</button></div>}
                    </div>
                    <button type="button" className="rounded-md border p-2 md:hidden" onClick={() => setOpen(true)}><Menu className="h-5 w-5" /></button>
                </div>
                {open && <div className="fixed inset-0 z-50 bg-white p-4 md:hidden"><div className="flex items-center justify-between"><span className="font-bold text-blue-600">Portal Kandidat</span><button type="button" onClick={() => setOpen(false)}><X className="h-5 w-5" /></button></div><div className="mt-6 flex flex-col gap-3">{links.map((link) => <Link key={link.href} href={link.href} onClick={() => setOpen(false)} className="rounded-md px-3 py-2 hover:bg-slate-100">{link.label}</Link>)}<Link href="/candidate/profile" className="rounded-md px-3 py-2 hover:bg-slate-100">Profil</Link><button type="button" onClick={() => router.post('/candidate/logout')} className="rounded-md px-3 py-2 text-left text-red-600 hover:bg-red-50">Logout</button></div></div>}
            </header>
            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">{children}</main>
            <footer className="border-t bg-white py-6 text-center text-sm text-slate-500">© {new Date().getFullYear()} Portal Kandidat</footer>
        </div>
    );
}
