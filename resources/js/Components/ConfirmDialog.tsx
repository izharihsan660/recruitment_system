import { Button, Card } from '@/Components/shared/ui';

interface ConfirmDialogProps {
    open: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'danger' | 'warning';
    onConfirm: () => void;
    onCancel: () => void;
}

export default function ConfirmDialog({
    open,
    title,
    message,
    confirmLabel = 'Ya, Lanjutkan',
    cancelLabel = 'Batal',
    variant = 'danger',
    onConfirm,
    onCancel,
}: ConfirmDialogProps): JSX.Element | null {
    if (!open) {
        return null;
    }

    const confirmVariant = variant === 'danger' ? 'danger' : 'primary';
    const iconClass = variant === 'danger' ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600';

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <Card className="w-full max-w-md p-6">
                <div className="flex gap-4">
                    <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${iconClass}`}>
                        !
                    </div>
                    <div>
                        <h2 className="text-lg font-semibold text-slate-900">{title}</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-600">{message}</p>
                    </div>
                </div>
                <div className="mt-6 flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={onCancel}>
                        {cancelLabel}
                    </Button>
                    <Button type="button" variant={confirmVariant} onClick={onConfirm}>
                        {confirmLabel}
                    </Button>
                </div>
            </Card>
        </div>
    );
}
