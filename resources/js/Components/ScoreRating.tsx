interface ScoreRatingProps {
    label: string;
    value: number | null;
    onChange: (value: number) => void;
    readOnly?: boolean;
    description?: string;
}

const labels: Record<number, string> = {
    1: 'Sangat Kurang',
    2: 'Kurang',
    3: 'Cukup',
    4: 'Baik',
    5: 'Sangat Baik',
};

export default function ScoreRating({
    label,
    value,
    onChange,
    readOnly = false,
    description,
}: ScoreRatingProps): JSX.Element {
    if (readOnly) {
        return (
            <div className="rounded-md border border-slate-200 p-3">
                <p className="text-sm font-medium text-slate-700">{label}</p>
                {description && <p className="mt-1 text-xs text-slate-500">{description}</p>}
                <span className="mt-2 inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                    {value ? `${value} - ${labels[value]}` : 'Belum dinilai'}
                </span>
            </div>
        );
    }

    return (
        <div>
            <p className="text-sm font-medium text-slate-700">{label}</p>
            {description && <p className="mt-1 text-xs text-slate-500">{description}</p>}
            <div className="mt-2 flex flex-wrap gap-2">
                {[1, 2, 3, 4, 5].map((score) => (
                    <button
                        key={score}
                        type="button"
                        onClick={() => onChange(score)}
                        className={`rounded-md border px-3 py-2 text-sm font-medium transition ${
                            value === score
                                ? 'border-blue-600 bg-blue-600 text-white'
                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                        }`}
                        aria-label={`${label} ${score} ${labels[score]}`}
                    >
                        {score}
                    </button>
                ))}
            </div>
            <p className="mt-1 text-xs text-slate-500">{value ? labels[value] : 'Pilih nilai 1-5'}</p>
        </div>
    );
}
