<?php

namespace App\Http\Middleware;

use App\Models\InAppNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user('web');
        $candidate = $request->user('candidate');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department_id' => $user->department_id,
                    'roles' => $user->getRoleNames()->values()->all(),
                ] : null,
                'candidate' => $candidate ? [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'email' => $candidate->email,
                    'phone' => $candidate->phone,
                    'has_cv' => $candidate->hasCv(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => session('success'),
                'error' => fn () => session('error'),
            ],
            'created_fpk_id' => fn () => session('created_fpk_id'),
            'unread_notifications_count' => fn (): int => $user
                ? InAppNotification::query()
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count()
                : 0,
            'latest_notifications' => fn (): array => $user
                ? InAppNotification::query()
                    ->where('user_id', $user->id)
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(fn (InAppNotification $notification): array => [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'body' => $notification->body,
                        'data' => $notification->data,
                        'read_at' => $notification->read_at,
                        'created_at' => $notification->created_at,
                    ])
                    ->values()
                    ->all()
                : [],
        ];
    }
}
