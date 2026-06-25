import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';

import { PageProps } from '@/types';

export default function FlashMessage(): JSX.Element | null {
    const { flash } = usePage<PageProps>().props;
    const [message, setMessage] = useState<{
        success?: string | null;
        error?: string | null;
    }>({});

    useEffect(() => {
        if (flash?.success || flash?.error) {
            setMessage({ success: flash.success, error: flash.error });

            const timer = setTimeout(() => setMessage({}), 4000);

            return () => clearTimeout(timer);
        }
    }, [flash?.success, flash?.error]);

    if (!message.success && !message.error) {
        return null;
    }

    return (
        <div className="mb-4 space-y-2">
            {message.success && (
                <div className="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {message.success}
                </div>
            )}
            {message.error && (
                <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {message.error}
                </div>
            )}
        </div>
    );
}
