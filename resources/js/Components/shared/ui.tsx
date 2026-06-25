import { forwardRef, HTMLAttributes, ReactNode } from 'react';

export function PageHeader({ title, description, actions }: { title: string; description?: string; actions?: ReactNode }): JSX.Element {
    return (
        <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
                {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
            </div>
            {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
        </div>
    );
}

export const Card = forwardRef<HTMLDivElement, HTMLAttributes<HTMLDivElement> & { children: ReactNode }>(
    ({ children, className = '', ...props }, ref) => (
        <div
            ref={ref}
            {...props}
            className={`rounded-lg border border-slate-200 bg-white shadow-sm ${className}`}
        >
            {children}
        </div>
    ),
);

Card.displayName = 'Card';

export function Button({ children, variant = 'primary', className = '', ...props }: React.ButtonHTMLAttributes<HTMLButtonElement> & { variant?: 'primary' | 'secondary' | 'danger' | 'ghost' }): JSX.Element {
    const variants = {
        primary: 'bg-blue-600 text-white hover:bg-blue-700 disabled:bg-blue-300',
        secondary: 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
        danger: 'bg-red-600 text-white hover:bg-red-700 disabled:bg-red-300',
        ghost: 'text-slate-700 hover:bg-slate-100',
    };

    return (
        <button {...props} className={`inline-flex items-center justify-center rounded-md px-3 py-2 text-sm font-medium transition disabled:cursor-not-allowed ${variants[variant]} ${className}`}>
            {children}
        </button>
    );
}

export function Badge({ children, tone = 'slate' }: { children: ReactNode; tone?: 'slate' | 'green' | 'yellow' | 'red' | 'orange' | 'blue' }): JSX.Element {
    const tones = {
        slate: 'bg-slate-100 text-slate-700',
        green: 'bg-green-100 text-green-700',
        yellow: 'bg-yellow-100 text-yellow-800',
        red: 'bg-red-100 text-red-700',
        orange: 'bg-orange-100 text-orange-700',
        blue: 'bg-blue-100 text-blue-700',
    };

    return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tones[tone]}`}>{children}</span>;
}

export function EmptyState({ title, description }: { title: string; description?: string }): JSX.Element {
    return (
        <div className="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
            <p className="font-semibold text-slate-900">{title}</p>
            {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
        </div>
    );
}

export function FieldError({ message }: { message?: string }): JSX.Element | null {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs font-medium text-red-600">{message}</p>;
}

export function TextInput(props: React.InputHTMLAttributes<HTMLInputElement>): JSX.Element {
    return <input {...props} className={`w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 ${props.className ?? ''}`} />;
}

export function TextArea(props: React.TextareaHTMLAttributes<HTMLTextAreaElement>): JSX.Element {
    return <textarea {...props} className={`w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 ${props.className ?? ''}`} />;
}

export function SelectInput(props: React.SelectHTMLAttributes<HTMLSelectElement>): JSX.Element {
    return <select {...props} className={`w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 ${props.className ?? ''}`} />;
}

export function FormLabel({ children, required = false }: { children: ReactNode; required?: boolean }): JSX.Element {
    return (
        <label className="mb-1 block text-sm font-medium text-slate-700">
            {children}
            {required && <span className="ml-1 text-red-600">*</span>}
        </label>
    );
}
