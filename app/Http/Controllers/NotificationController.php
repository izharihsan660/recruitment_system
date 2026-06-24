<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\InAppNotification;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return NotificationResource::collection(request()->user()->notifications()->latest()->paginate());
    }

    public function read(InAppNotification $notification): Response
    {
        abort_unless((int) $notification->user_id === (int) request()->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return response()->noContent();
    }

    public function readAll(): Response
    {
        request()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->noContent();
    }
}
